<?php

namespace App\Console\Commands;

use App\Http\Controllers\Rajal\RajalBundleController;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RajalGetIhsPatient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rajal:getIhsPatient';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $initialDate = Carbon::parse('2024-01-01');
        $currentDate = Carbon::now();
        while ($initialDate->lte($currentDate)) {
            $date = $initialDate->toDateString();

            $data = RajalBundleController::getIhsPasienTgl($date);

            if ($data->status() == 200) {
                $body = $data->getContent();
                // $this->info("{$body['data']}");
                if (
                    isset($body['data']['data_updated_count']) &&
                    $body['data']['data_updated_count'] == 0 &&
                    isset($body['data']['data_updated']) &&
                    $body['data']['data_updated'] == 0
                ) {
                    $this->info("Processed data for date: {$date}");
                } else {
                    $this->info("Data found for date: {$date}");
                }
            } else {
                $this->error("Failed to process data for date: {$date}");
            }

            $initialDate->addDay();
            // $this->info($initialDate);
        }
        // return 0;

        $this->info('Processing complete.');
        return 0;
    }
}
