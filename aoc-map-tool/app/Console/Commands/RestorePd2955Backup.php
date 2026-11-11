<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Undoes a run of pd2955:cleanup-duplicates from the JSON backup manifest it wrote.
 * Re-inserts the hard-deleted mssql_patients row with its original id, then reverts every
 * repointed dependent row's FK value back to that id.
 */
class RestorePd2955Backup extends Command
{
    protected $signature = 'pd2955:restore-backup
        {file : Path to the backup manifest JSON written by pd2955:cleanup-duplicates}
        {--execute : Actually write changes. Without this flag, only reports what would happen.}
        {--pair= : Only restore the pair at this index in the manifest (0-based, for spot fixes).}';

    protected $description = 'Restore mssql_patients rows and repointed dependent rows from a pd2955:cleanup-duplicates backup manifest.';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (!is_file($path)) {
            $this->error("Backup file not found: {$path}");
            return self::FAILURE;
        }

        $manifest = json_decode(file_get_contents($path), true);
        if (!is_array($manifest)) {
            $this->error('Backup file is not valid JSON.');
            return self::FAILURE;
        }

        $execute = (bool) $this->option('execute');
        $onlyPair = $this->option('pair') !== null ? (int) $this->option('pair') : null;

        foreach ($manifest as $index => $pair) {
            if ($onlyPair !== null && $index !== $onlyPair) {
                continue;
            }

            $label = "pair #{$index} ({$pair['patient_account']}, loser id {$pair['loser_id']})";

            if (!$execute) {
                $count = count($pair['repointed_rows']);
                $this->line("WOULD RESTORE {$label}: re-insert the deleted loser row, revert {$count} repointed row(s).");
                continue;
            }

            $skipped = [];
            DB::transaction(function () use ($pair, &$skipped) {
                // Re-insert first - Tier 1/2 tables' FK columns are plain ints with no
                // enforced constraint, but restoring in parent-then-children order is correct
                // regardless of whether a constraint exists.
                DB::table('mssql_patients')->insert($pair['loser_row_before']);

                foreach ($pair['repointed_rows'] as $entry) {
                    // Only revert if the column still holds the value this cleanup run wrote
                    // into it - if something else has changed it since, blindly overwriting
                    // would clobber a legitimate later edit instead of undoing this tool's own
                    // change.
                    $current = DB::table($entry['table'])->where('id', $entry['id'])->value($entry['column']);
                    if ((string) $current !== (string) $entry['written_value']) {
                        $skipped[] = "{$entry['table']}.{$entry['column']} (id {$entry['id']}): current value '{$current}' != expected '{$entry['written_value']}', left as-is";
                        continue;
                    }
                    DB::table($entry['table'])->where('id', $entry['id'])
                        ->update([$entry['column'] => $entry['row_before'][$entry['column']]]);
                }
            });

            $this->line("RESTORED {$label}.");
            foreach ($skipped as $warning) {
                $this->warn("  SKIPPED revert - {$warning}");
            }
        }

        if ($execute) {
            $this->info('Restore complete. Recalculate IsLatestAdmit afterwards if any restored patient should become the active admit again: php artisan tinker --execute="app(\App\Kantime\Repository\MariaDB\AocPatientsRepository::class)->recalculateLatestAdmits();"');
        }

        return self::SUCCESS;
    }
}
