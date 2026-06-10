<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PruneIdempotencyRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'idempotency:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune expired idempotency records from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deletedCount = \App\Models\IdempotencyRecord::where('expires_at', '<', now())->delete();
        $this->info("Pruned {$deletedCount} expired idempotency records.");
    }
}
