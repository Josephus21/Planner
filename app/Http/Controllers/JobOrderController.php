<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GsuiteJobOrderApi;
use App\Models\JobOrder;
use Carbon\Carbon;

class JobOrderController extends Controller
{
    public function index(Request $request, GsuiteJobOrderApi $api)
    {
        $perPage = max(1, (int) $request->input('limit', 10));
        $page    = max(1, (int) $request->input('page', 1));

        // Optional filter: all | lfp | dpod
        $type = strtolower((string) $request->input('type', 'all'));

        $apiLimit   = max($perPage, 50);
        $maxFetches = 12;

        $needToSkip = ($page - 1) * $perPage;
        $collected  = [];
        $skipped    = 0;

        $apiOffset  = 0;
        $apiOk      = true;
        $apiMessage = 'OK';
        $hasMore    = false;

        // avoid repeated DB writes in the same request
        $savedExternalIds = [];

        for ($i = 0; $i < $maxFetches; $i++) {
            $payload = $api->buildDefaultPayload([
                // broad search, then filter by joloc_name
                'searchKey' => '',
                'limit'     => $apiLimit,
                'offset'    => $apiOffset,
            ]);

            $result = $api->getJobOrders($payload);

            $apiOk      = (bool) ($result['ok'] ?? false);
            $apiMessage = (string) ($result['message'] ?? '');

            if (!$apiOk) {
                break;
            }

            $rows = $result['rows'] ?? [];
            if (!is_array($rows) || count($rows) === 0) {
                break;
            }

            /*
            |--------------------------------------------------------------------------
            | FILTER ONLY joloc_name = Warehouse - LFP / Warehouse - DPOD
            |--------------------------------------------------------------------------
            */
            $rows = array_values(array_filter($rows, function ($r) use ($type) {
                $location = strtoupper(trim((string) ($r['joloc_name'] ?? '')));

                $allowed = [
                    'WAREHOUSE - LFP',
                    'WAREHOUSE - DPOD',
                ];

                if (!in_array($location, $allowed, true)) {
                    return false;
                }

                if ($type === 'lfp') {
                    return $location === 'WAREHOUSE - LFP';
                }

                if ($type === 'dpod') {
                    return $location === 'WAREHOUSE - DPOD';
                }

                return true;
            }));

            /*
            |--------------------------------------------------------------------------
            | SAVE filtered rows to DB
            |--------------------------------------------------------------------------
            */
            $this->upsertJobOrders($rows, $savedExternalIds);

            /*
            |--------------------------------------------------------------------------
            | MANUAL PAGINATION AFTER FILTER
            |--------------------------------------------------------------------------
            */
            foreach ($rows as $r) {
                if ($skipped < $needToSkip) {
                    $skipped++;
                    continue;
                }

                $collected[] = $r;

                if (count($collected) >= $perPage) {
                    $hasMore = true;
                    break 2;
                }
            }

            $apiOffset += $apiLimit;

            // stop if API returned less than requested
            if ((int) ($result['page_count'] ?? 0) < $apiLimit) {
                $hasMore = false;
                break;
            }
        }

        $rows = $collected;

        $lastPage = $hasMore ? ($page + 1) : $page;
        $total    = ($page - 1) * $perPage + count($rows);

        return view('job_orders.index', [
            'rows'       => $rows,
            'total'      => $total,
            'page'       => $page,
            'lastPage'   => max(1, $lastPage),
            'limit'      => $perPage,
            'type'       => $type,
            'apiOk'      => $apiOk,
            'apiMessage' => $apiMessage ?: ($apiOk ? 'OK' : 'API error'),
        ]);
    }

    private function upsertJobOrders(array $rows, array &$savedExternalIds = []): void
    {
        $now = now();

        foreach ($rows as $r) {
            $externalId = (string) ($r['jo_pk'] ?? $r['jo_upk'] ?? '');
            if ($externalId === '') {
                continue;
            }

            // prevent duplicate writes in same request loop
            if (isset($savedExternalIds[$externalId])) {
                continue;
            }
            $savedExternalIds[$externalId] = true;

            $location = (string) ($r['joloc_name'] ?? '');

            $jobType = null;
            if (strcasecmp($location, 'Warehouse - LFP') === 0) {
                $jobType = 'LFP';
            } elseif (strcasecmp($location, 'Warehouse - DPOD') === 0) {
                $jobType = 'DPOD';
            }

            JobOrder::updateOrCreate(
                ['external_id' => $externalId],
                [
                    'jo_no'         => $r['jo_upk'] ?? null,
                    'so_no'         => $r['so_upk'] ?? null,
                    'customer_name' => $r['Name_Cust'] ?? null,
                    'prepared_by'   => $r['PreparedBy_TransH'] ?? ($r['empl_name'] ?? null),
                    'description'   => $r['JobDescription_TransH'] ?? null,
                    'location'      => $location,
                    'job_type'      => $jobType,
                    'order_date'    => $this->toDate($r['DateCreated_TransH'] ?? null),
                    'delivery_date' => $this->toDate($r['DeliveryDate_TransH'] ?? null),
                    'status'        => $r['Status_TransH'] ?? null,
                    'sub_status'    => $r['SubStatus_TransH'] ?? null,
                    'gp_rate'       => is_numeric($r['gpRate'] ?? null) ? (float) $r['gpRate'] : null,
                    'payload'       => $r,
                    'fetched_at'    => $now,
                ]
            );
        }
    }

    private function toDate($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}