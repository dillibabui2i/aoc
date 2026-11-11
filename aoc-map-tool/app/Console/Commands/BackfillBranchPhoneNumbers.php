<?php

namespace App\Console\Commands;

use App\BranchLocations;
use Illuminate\Console\Command;

/**
 * Usage:
 *   php artisan branches:backfill-phone-numbers            — fill phone_number for branches that have none
 *   php artisan branches:backfill-phone-numbers --dry-run  — report only, no DB changes
 *
 * The numbers below were collected by hand from each branch's IPEA email_body
 * (the cell after "Contact Number"). Branches are matched by name; only rows with an
 * empty phone_number are written, existing numbers are never overwritten and no other
 * column is touched.
 *
 * Not in the map (no number in email_body, enter manually in Manage Branch):
 * McKinney (Corporate), Richmond.
 */
class BackfillBranchPhoneNumbers extends Command
{
    protected $signature = 'branches:backfill-phone-numbers
                            {--dry-run : Report only, do not update records}';

    protected $description = 'Backfill branch_locations.phone_number (E.164) from numbers collected from IPEA email bodies';

    private const NUMBERS = [
        'Phoenix' => '602-331-1100',
        'Charlotte' => '704-295-0822',
        'Raleigh' => '919-400-4099',
        'Wilmington' => '910-338-4883',
        'Winston-Salem' => '336-568-2263',
        'Denver' => '720-465-5754',
        'Colorado Springs' => '719-792-0260',
        'Pueblo' => '719-299-0244',
        'Fort Collins' => '970-682-2632',
        'Jacksonville' => '904-683-8666',
        'North Jacksonville PPEC' => '904-379-4021',
        'Orange Park PPEC' => '904-375-1197',
        'Tampa' => '813-551-3040',
        'Orlando' => '321-344-1220',
        'DuBois' => '814-554-3983',
        'Reading' => '814-554-3982',
        'Allentown' => '484-443-0606',
        'Harrisburg' => '717-614-8866',
        'Abilene' => '325-690-0583',
        'Amarillo' => '806-353-2700',
        'Austin' => '512-996-9559',
        'Corpus Christi' => '361-459-0233',
        'Dallas' => '972-702-0300',
        'El Paso' => '915-213-1289',
        'Fort Worth' => '817-810-0660',
        'Houston' => '713-799-2200',
        'Houston Admin' => '713-799-2200',
        'Lubbock' => '806-744-8999',
        'Odessa' => '325-690-0583',
        'San Antonio' => '210-731-9570',
        'Sherman' => '903-551-3300',
        'Temple' => '254-252-5757',
        'Tyler' => '903-534-4684',
        'Wichita Falls' => '940-761-9986',
        'Columbia' => '803-741-5811',
        'Pittsburgh' => '412-474-0553',
        'Gainesville' => '352-418-0680',
        'Port St Lucie' => '772-226-7930',
        'Asheville' => '828-341-2144',
        'Springfield' => '267-627-6698',
        'Saint Petersburg' => '727-472-0611',
        'Pittsburgh South' => '412-625-7799',
        'Cape Coral (Sarasota)' => '941-538-5033',
        'Tallahassee' => '448-216-0011',
        'Fort Lauderdale' => '754-289-5344',
        'Pensacola' => '448-216-0033',
        'Miami' => '786-809-1455',
        'Rio Grande Valley' => '956-646-1331',
        'Albuquerque' => '505-257-1230',
        'Charleston' => '803-741-5811',
        'Greenville' => '864-519-8228',
        'Wichita' => '316-374-5080',
        'Memphis' => '901-656-8996',
        'Atlanta' => '404-335-3512',
        'Northeast' => '717-373-9745',
        'Bartow PPEC' => '863-378-0783',
        'Fort Pierce PPEC' => '772-710-4840',
        'Titusville PPEC' => '321-346-4502',
        'Port St Lucie PPEC' => '772-408-9588',
        'Jacksonville PPEC' => '904-450-5309',
    ];

    public function handle(): int
    {
        $dryRun = (bool)$this->option('dry-run');
        $rows = [];
        $counts = ['Filled' => 0, 'Already had a number' => 0, 'Branch not found' => 0, 'No number in map' => 0];

        foreach (self::NUMBERS as $name => $number) {
            $branch = BranchLocations::where('branch', $name)->first();

            if ($branch === null) {
                $action = 'Branch not found';
            } elseif (!empty($branch->phone_number)) {
                $action = 'Already had a number';
            } else {
                $action = 'Filled';
                if (!$dryRun) {
                    BranchLocations::where('id', $branch->id)
                        ->toBase()
                        ->update(['phone_number' => toE164($number)]);
                }
            }

            $counts[$action]++;
            $rows[] = [$name, $number, $action];
        }

        $mapped = array_keys(self::NUMBERS);
        foreach (BranchLocations::whereNotIn('branch', $mapped)->orderBy('id')->pluck('branch') as $name) {
            $counts['No number in map']++;
            $rows[] = [$name, '-', 'No number in map'];
        }

        $this->table(['Branch', 'Number', 'Action'], $rows);
        $this->info(($dryRun ? 'DRY RUN. ' : '') . json_encode($counts));

        return self::SUCCESS;
    }
}
