<?php

namespace App\Console\Commands;

use App\Models\ServiceContract;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Phase 20 — brings each contract's cached `status` back in line with what
 * its period and balance actually say.
 *
 * The booking gate never reads `status`: a contract lapses at midnight and
 * this runs later, so between the two the column is stale by design. The
 * column exists for list filters and badges, and this is what keeps it
 * roughly honest.
 *
 * A cancelled contract is an admin's decision and is never recomputed.
 */
#[Signature('contracts:sweep')]
#[Description('Mark service contracts expired or exhausted once their period or hours run out')]
class SweepServiceContractsCommand extends Command
{
    public function handle(): int
    {
        $changed = 0;

        ServiceContract::query()
            ->where('status', '!=', ServiceContract::STATUS_CANCELLED)
            ->orderBy('id')
            ->chunkById(200, function ($contracts) use (&$changed): void {
                foreach ($contracts as $contract) {
                    $derived = $contract->derivedStatus();

                    if ($derived === $contract->status) {
                        continue;
                    }

                    $contract->update(['status' => $derived]);
                    $changed++;
                }
            });

        $this->info("Updated {$changed} service contract status(es).");

        return self::SUCCESS;
    }
}
