<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GsuiteJobOrderApi;
use App\Models\SalesOrder;

class SalesSyncCommand extends Command
{
    protected $signature = 'sales:sync
        {--limit=150 : How many records per batch}
        {--offset=0 : Offset for API pagination}
        {--q=INSTALLATION : Search keyword}
        {--status=Released : Status filter}
        {--sub=Approved : Sub-status filter}
    ';

    protected $description = 'Sync Job Orders (JO) from GSuite API into local sales_orders table';

    public function handle(GsuiteJobOrderApi $api)
    {
        $limit  = (int) $this->option('limit');
        $offset = (int) $this->option('offset');

        $q      = (string) $this->option('q');
        $status = (string) $this->option('status');
        $sub    = (string) $this->option('sub');

        $this->info("Fetching JO list... q='{$q}', status={$status}, sub={$sub}, limit={$limit}, offset={$offset}");

        // Build payload for JO endpoint
        $payload = $api->buildDefaultPayload([
            'searchKey'        => $q,
            'limit'            => $limit,
            'offset'           => $offset,
            'Status_TransH'    => $status,
            'SubStatus_TransH' => $sub,
        ]);

       $resp = $api->getJobOrders($payload, false); // no cache for CLI sync

        if (!($resp['ok'] ?? false)) {
            $this->error("API failed: " . ($resp['message'] ?? 'unknown error'));
            return Command::FAILURE;
        }

        $rows = $resp['rows'] ?? [];
        if (!is_array($rows) || count($rows) === 0) {
            $this->warn("No job orders returned.");
            return Command::SUCCESS;
        }

        $saved = 0;
        foreach ($rows as $row) {
            // Safety filter (even if API already filtered)
            if (($row['Status_TransH'] ?? '') !== $status) continue;
            if (($row['SubStatus_TransH'] ?? '') !== $sub) continue;

            $joPK = $row['jo_pk'] ?? null;
            if (!$joPK) continue;

            SalesOrder::updateOrCreate(
                ['external_id' => $joPK], // UNIQUE
                [
                    // NEW columns (add migration I gave earlier)
                    'jo_no'         => $row['jo_upk'] ?? null,
                    'so_no'         => $row['so_upk'] ?? null,

                    'customer_name' => $row['Name_Cust'] ?? null,
                    'prepared_by'   => $row['PreparedBy_TransH'] ?? null,
                    'description'   => $row['JobDescription_TransH'] ?? null,

                    'order_date'    => $row['DateCreated_TransH'] ?? null,
                    'delivery_date' => $row['DeliveryDate_TransH'] ?? null,

                    'status'        => $row['Status_TransH'] ?? null,
                    'sub_status'    => $row['SubStatus_TransH'] ?? null,

                    // If you still keep "total" but JO list doesn’t have it, set null
                    'total'         => null,
                    'gp_rate'       => isset($row['gpRate']) ? (float) $row['gpRate'] : null,

                    // Keep full raw row for audit/debug
                    'payload'       => $row,
                    'fetched_at'    => now(),
                ]
            );

            $saved++;
        }

        $this->info("Done. Saved/updated: {$saved} job orders.");

        return Command::SUCCESS;
    }
}