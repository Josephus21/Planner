<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GsuiteJobOrderApi
{
    public function buildDefaultPayload(array $overrides = []): array
    {
        $defaults = [
            'searchKey' => 'INSTALLATION',
            'limit'     => 10,
            'offset'    => 0,
            'module'    => 'JOBORDER',
            'viewAll'   => 1,
        ];

        return array_merge($defaults, $overrides);
    }

    public function getJobOrders(array $payload = []): array
    {
        $base  = rtrim((string) config('services.gsuite.base_url'), '/'); // already ends with /api
        $token = (string) config('services.gsuite.token');

        $connectTimeout = (int) (config('services.gsuite.connect_timeout') ?? 5);
        $timeout        = (int) (config('services.gsuite.timeout') ?? 15);

        if (!$base || !Str::startsWith($base, ['http://', 'https://'])) {
            return ['ok'=>false,'message'=>"Invalid base_url: '{$base}'",'rows'=>[],'total'=>0,'page_count'=>0,'raw'=>null];
        }

        if (!$token) {
            return ['ok'=>false,'message'=>"Missing token",'rows'=>[],'total'=>0,'page_count'=>0,'raw'=>null];
        }

        // ? base already includes /api
        $url = $base . '/get_job_orders';

        try {
            $res = Http::retry(2, 300)
                ->connectTimeout($connectTimeout)
                ->timeout($timeout)
                ->acceptJson()
                ->withToken($token)
                ->post($url, $payload);

            if (!$res->ok()) {
                return [
                    'ok' => false,
                    'message' => "HTTP {$res->status()}",
                    'rows' => [],
                    'total' => 0,
                    'page_count' => 0,
                    'raw' => $res->body(),
                ];
            }

            $json = $res->json();

            // ? Expect: { data: [ [rows...], total ] }
            $data = $json['data'] ?? null;

            $rows  = (is_array($data) && isset($data[0]) && is_array($data[0])) ? $data[0] : [];
            $total = (is_array($data) && isset($data[1])) ? (int) $data[1] : (is_array($rows) ? count($rows) : 0);

            $pageCount = is_array($rows) ? count($rows) : 0;

            return [
                'ok' => true,
                'message' => 'OK',
                'rows' => $rows,
                'total' => $total,
                'page_count' => $pageCount, // ? important for count loop
                'raw' => $json,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'API error: ' . $e->getMessage(),
                'rows' => [],
                'total' => 0,
                'page_count' => 0,
                'raw' => null,
            ];
        }
    }

    public function countAllowedJobOrders(string $searchKey = 'INSTALLATION', int $cacheMinutes = 5): array
    {
        $allowedList = (array) config('services.gsuite.allowed_syspk_job', []);
        $cacheKey = 'gsuite_jo_count_' . md5($searchKey . '|' . implode(',', $allowedList));

        return Cache::remember($cacheKey, now()->addMinutes($cacheMinutes), function () use ($searchKey, $allowedList) {

            $limit = 200;
            $offset = 0;
            $maxFetches = 30; // 6000 scanned max
            $count = 0;

            for ($i = 0; $i < $maxFetches; $i++) {

                $payload = $this->buildDefaultPayload([
                    'searchKey' => $searchKey,
                    'limit'     => $limit,
                    'offset'    => $offset,
                ]);

                $res = $this->getJobOrders($payload);

                if (!($res['ok'] ?? false)) {
                    return [
                        'ok' => false,
                        'message' => (string) ($res['message'] ?? 'API error'),
                        'count' => $count,
                    ];
                }

                $rows = $res['rows'] ?? [];
                if (!is_array($rows) || count($rows) === 0) {
                    break;
                }

                // filter by SysPK_Job (allowed list)
                if (count($allowedList) > 0) {
                    $rows = array_filter($rows, function ($r) use ($allowedList) {
                        $jobPk = $r['SysPK_Job'] ?? null;
                        return $jobPk && in_array($jobPk, $allowedList, true);
                    });
                }

                $count += count($rows);

                $offset += $limit;

                // ? use page_count (raw returned rows), not filtered rows
                if ((int) ($res['page_count'] ?? 0) < $limit) {
                    break;
                }
            }

            return [
                'ok' => true,
                'message' => 'OK',
                'count' => $count,
            ];
        });
    }
}