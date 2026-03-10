<?php

namespace App\Console\Commands;

use App\Models\SalesOrder;
use App\Services\GsuiteJobOrderApi;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SalesSync extends Command
{
    protected $signature = 'sales:sync
    {--searchKey=INSTALLATION : Search key for API}
    {--limit=200 : Page size per fetch}
    {--maxPages=30 : Safety cap pages}
    {--startOffset=0 : Starting offset}';

    protected $description = 'Sync Job Orders from GSuite API into local sales_orders table';

    public function handle(GsuiteJobOrderApi $api): int
    {
        $searchKey = (string) $this->option('searchKey');
        $limit = max(1, (int) $this->option('limit'));
        $maxPages = max(1, (int) $this->option('maxPages'));
        $offset = max(0, (int) $this->option('startOffset'));

        $allowedJobs = (array) config('services.gsuite.allowed_syspk_job', []);

        $this->info("Syncing JO searchKey={$searchKey}, limit={$limit}, maxPages={$maxPages}, offset={$offset}");
        $this->info("Allowed SysPK_Job: " . (count($allowedJobs) ? implode(', ', $allowedJobs) : '(none)'));

        $synced = 0;
        $seen = 0;

        for ($page = 1; $page <= $maxPages; $page++) {

            $payload = $api->buildDefaultPayload([
                'searchKey' => $searchKey,
                'limit'     => $limit,
                'offset'    => $offset,
            ]);

            $res = $api->getJobOrders($payload);

            if (!($res['ok'] ?? false)) {
                $this->error("API failed: " . ($res['message'] ?? 'unknown'));
                return self::FAILURE;
            }

            $rows = $res['rows'] ?? [];
            if (!is_array($rows) || count($rows) === 0) {
                $this->info("No more rows. Stopping.");
                break;
            }

            $pageCount = count($rows);
            $seen += $pageCount;

            // Filter by SysPK_Job if configured
            if (count($allowedJobs)) {
                $rows = array_values(array_filter($rows, function ($r) use ($allowedJobs) {
                    $jobPk = $r['SysPK_Job'] ?? null;
                    return $jobPk && in_array($jobPk, $allowedJobs, true);
                }));
            }

            $now = now();

            foreach ($rows as $r) {
                // Unique external id: use jo_pk (best)
                $externalId = (string) ($r['jo_pk'] ?? $r['jo_upk'] ?? '');
                if ($externalId === '') {
                    continue;
                }

                $data = [
                    'external_id'   => $externalId,
                    'jo_no'         => $r['jo_upk'] ?? null,
                    'so_no'         => $r['so_upk'] ?? null,
                    'customer_name' => $r['Name_Cust'] ?? null,
                    'prepared_by'   => $r['PreparedBy_TransH'] ?? null,
                    'description'   => $r['JobDescription_TransH'] ?? null,
                    'order_date'    => $this->toDate($r['DateCreated_TransH'] ?? null),
                    'delivery_date' => $this->toDate($r['DeliveryDate_TransH'] ?? null),
                    'status'        => $r['Status_TransH'] ?? null,
                    'sub_status'    => $r['SubStatus_TransH'] ?? null,
                    'gp_rate'       => is_numeric($r['gpRate'] ?? null) ? (float) $r['gpRate'] : null,
                    'payload'       => $r,
                    'fetched_at'    => $now,
                ];

                // Upsert by external_id (unique)
                SalesOrder::updateOrCreate(
                    ['external_id' => $externalId],
                    $data
                );

                $synced++;
            }

            $this->info("Page {$page}: apiRows={$pageCount}, keptAfterFilter=" . count($rows) . ", totalSynced={$synced}");

            // Next offset
            $offset += $limit;

            // Stop if API returned less than requested (end of dataset)
            if ($pageCount < $limit) {
                $this->info("Reached end (apiRows < limit).");
                break;
            }
        }

        $this->info("Done. Seen={$seen}, Synced={$synced}");
        return self::SUCCESS;
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