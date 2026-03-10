<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GsuiteJobOrderApi;
use App\Models\SalesOrder;
use Carbon\Carbon;

class SalesOrderController extends Controller
{
    public function index(Request $request, GsuiteJobOrderApi $api)
    {
        $perPage = max(1, (int) $request->input('limit', 10));
        $page    = max(1, (int) $request->input('page', 1));
        $searchKey = (string) $request->input('q', 'INSTALLATION');

        // Only allow these SysPK_Job values (from config/services.php)
        $allowedJobs = config('services.gsuite.allowed_syspk_job', []);
        $allowedJobs = is_array($allowedJobs) ? $allowedJobs : [];

        $apiLimit   = max($perPage, 50);
        $maxFetches = 12;

        $needToSkip = ($page - 1) * $perPage;
        $collected  = [];
        $skipped    = 0;

        $apiOffset = 0;
        $apiOk = true;
        $apiMessage = 'OK';
        $hasMore = false;

        // ? to avoid writing DB repeatedly if the same JO appears across fetch loops
        $savedExternalIds = [];

        for ($i = 0; $i < $maxFetches; $i++) {

            $payload = $api->buildDefaultPayload([
                'searchKey' => $searchKey,
                'limit'     => $apiLimit,
                'offset'    => $apiOffset,
            ]);

            $result = $api->getJobOrders($payload);

            $apiOk = (bool) ($result['ok'] ?? false);
            $apiMessage = (string) ($result['message'] ?? '');

            if (!$apiOk) {
                break;
            }

            $rows = $result['rows'] ?? [];
            if (!is_array($rows) || count($rows) === 0) {
                break;
            }

            // Filter by SysPK_Job
            if (count($allowedJobs) > 0) {
                $rows = array_values(array_filter($rows, function ($r) use ($allowedJobs) {
                    $jobPk = $r['SysPK_Job'] ?? null;
                    return $jobPk && in_array($jobPk, $allowedJobs, true);
                }));
            }

            // ? SAVE rows to DB (upsert)
            $this->upsertSalesOrders($rows, $savedExternalIds);

            // Consume rows for the current page
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

            // NOTE: this "end detection" is best-effort because rows are filtered.
            // It's still ok for UI; DB sync is the real source of truth.
            if (count($result['rows'] ?? []) < $apiLimit) {
                $hasMore = false;
                break;
            }
        }

        $rows = $collected;

        $lastPage = $hasMore ? ($page + 1) : $page;
        $total = ($page - 1) * $perPage + count($rows);

        return view('sales_orders.index', [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'lastPage' => max(1, $lastPage),
            'limit' => $perPage,
            'q' => $searchKey,
            'apiOk' => $apiOk,
            'apiMessage' => $apiMessage ?: ($apiOk ? 'OK' : 'API error'),
        ]);
    }

    private function upsertSalesOrders(array $rows, array &$savedExternalIds = []): void
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

            SalesOrder::updateOrCreate(
                ['external_id' => $externalId],
                [
                    'jo_no'         => $r['jo_upk'] ?? null,
                    'so_no'         => $r['so_upk'] ?? null,
                    'customer_name' => $r['Name_Cust'] ?? null,
                    'prepared_by'   => $r['PreparedBy_TransH'] ?? ($r['empl_name'] ?? null),
                    'description'   => $r['JobDescription_TransH'] ?? null,
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
        if (!$value) return null;

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}