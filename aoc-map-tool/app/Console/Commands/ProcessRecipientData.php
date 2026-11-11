<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use DB;

class ProcessRecipientData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:recipientdata {start_date} {end_date}';
    protected $description = 'Export phone numbers with names to a CSV file based on date range';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $startDate = $this->argument('start_date');
        $endDate = $this->argument('end_date');

        // Validate the date parameters
        if (!strtotime($startDate) || !strtotime($endDate)) {
            $this->error('Invalid date format. Please use Y-m-d.');
            return;
        }

        if ($startDate > $endDate) {
            $this->error('End date must be after or equal to the start date.');
            return;
        }

        $fileName = 'text_recipient_' . $startDate .' - '. date('Y-m-d_H-i-s') . '.csv';
        $filePath = "text_recipient_exports/$fileName";

        // Ensure the directory exists
        if (!Storage::exists('text_recipient_exports')) {
            Storage::makeDirectory('text_recipient_exports');
        }

        // Create CSV content
        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, ['Number','Message','Sender','Date']);

        // Step 1: Chunk through Mass entries within the date range
        DB::table('mass_message')
            ->whereRaw('DATE(created_at) >= ? AND DATE(created_at) <= ?',[$startDate,$endDate])
            ->orderBy('completed_at','ASC')
            ->chunk(1000, function ($massEntries) use ($handle) {
                $phoneNumbers = [];

                foreach ($massEntries as $entry) {
                    $sendersData = DB::table('users')->where('id',$entry->user_id)->first();
                    $phoneNumbers = array_map('trim', explode(',', $entry->receivers));
                    $sentAt = '';
                    $sentAt = date('m/d/Y', strtotime($entry->created_at));
                    // Step 2: Fetch names from customer and teacher tables
                    foreach ($phoneNumbers as $phoneNumber) {
                        print_r($phoneNumber);echo "\n";
                        fputcsv($handle, [$phoneNumber, $entry->message, $sendersData->name,$sentAt]);
                    }
                }
            });
             // Move the file pointer to the beginning of the stream
        rewind($handle);

        // Write the stream content to the CSV file
        Storage::put($filePath, stream_get_contents($handle), 'public');

        fclose($handle);

        $this->info("CSV file created successfully: $filePath");
    }
}
