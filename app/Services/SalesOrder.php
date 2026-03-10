<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class GsuiteJobOrderApi
{
    /**
     * Build a default payload for get_job_orders API.
     * You can override any key by passing it in $overrides.
     */
    public function buildDefaultPayload(array $overrides = []): array
    {
        $defaults = [
            // search keyword (your UI default)
            'searchKey' => 'INSTALLATION',

            // pagination
            'limit'  => 10,
            'offset' => 0,

            // filters requested
            'Status_TransH'    => 'Released',
            'SubStatus_TransH' => 'Approved',

            // optional (kept if your API uses them; removed if null/empty)
            'employee_pk' => env('GSUITE_EMPLOYEE_PK'),
            'prepared_by' => env('GSUITE_PREPARED_BY'),
        ];

        $payload = array_merge($defaults, $overrides);

        // remove null/empty strings so we don’t send useless keys
        return array_filter($payload, fn ($v) => !is_null($v) && $v !== '');
    }

    /**
     * Fetch job orders from GSuite API.
     * Always returns a consistent array shape:
     *  - ok (bool)
     *  - message (string)
     *  - rows (array)
     *  - total (int)
     *  - raw (mixed)
     */
    public function getJobOrders(array $payload = []): array
    {
        $base    = rtrim((string) config('services.gsuite.base_url'), '/');
        $token   = (string) config('services.gsuite.token');
        $timeout = (int) (config('services.gsuite.timeout') ?? 60);

        if (!$base || !Str::startsWith($base, ['http://', 'https://'])) {
            return $this->fail("Invalid GSUITE base_url: '{$base}' (set GSUITE_API_BASE_URL to http(s)://...)");
        }

        if (!$token) {
            return $this->fail("Missing GSUITE token (set GSUITE_API_TOKEN).");
        }

        // If .env is: http://gsuite.graphicstar.com.ph (recommended)
        // final endpoint becomes: http://gsuite.graphicstar.com.ph/api/get_job_orders
        $url = $base . '/api/get_job_orders';

        // Cache results for a short time to reduce timeouts / repeated hits
        $cacheKey = 'gsuite_job_orders:' . md5(json_encode([
            'url' => $url,
            'payload' => $payload,
        ]));

        return cache()->remember($cacheKey, 60, function () use ($url, $token, $timeout, $payload) {
            logger()->info('GSUITE get_job_orders', [
                'url' => $url,
                'timeout' => $timeout,
                'payload' => $payload,
            ]);

            try {
                $res = Http::withToken($token)
                    ->acceptJson()
                    ->asJson()
                    ->timeout($timeout)
                    ->retry(2, 500, throw: false) // retry twice, don't throw on retry failures
                    ->post($url, $payload);
            } catch (ConnectionException $e) {
                return $this->fail('Connection timeout / network error: ' . $e->getMessage());
            } catch (RequestException $e) {
                return $this->fail('HTTP request error: ' . $e->getMessage());
            } catch (\Throwable $e) {
                return $this->fail('Unexpected error: ' . $e->getMessage());
            }

            if (!$res->ok()) {
                return [
                    'ok' => false,
                    'message' => 'API error: HTTP ' . $res->status(),
                    'rows' => [],
                    'total' => 0,
                    'raw' => $res->json(),
                ];
            }

            $json = $res->json();

            // API shape you showed: { "data": [ [ {..}, {..} ] ] }
            $rows = $json['data'] ?? [];

            // flatten: data -> [ [ objects ] ]
            if (is_array($rows) && isset($rows[0]) && is_array($rows[0])) {
                $rows = $rows[0];
            }

            // Safety: ensure array
            if (!is_array($rows)) {
                $rows = [];
            }

            // Force filter Released + Approved (even if API returns other statuses)
            $rows = array_values(array_filter($rows, function ($row) {
                return ($row['Status_TransH'] ?? '') === 'Released'
                    && ($row['SubStatus_TransH'] ?? '') === 'Approved';
            }));

            return [
                'ok' => true,
                'message' => 'OK',
                'rows' => $rows,
                'total' => count($rows),
                'raw' => $json,
            ];
        });
    }

    private function fail(string $message): array
    {
        return [
            'ok' => false,
            'message' => $message,
            'rows' => [],
            'total' => 0,
            'raw' => null,
        ];
    }
}