<?php

namespace App\Console\Commands;

use App\Http\Controllers\Rajal\RajalBundleController;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RajalGetReg extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rajal:get-reg';

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
        $initialDate = Carbon::parse('2024-06-01');
        $currentDate = Carbon::now();
        while ($initialDate->lte($currentDate)) {
            $date = $initialDate->toDateString();

            $data = RajalBundleController::getRegTgl($date);

            if ($data->status() == 200) {
                $body = $data->getContent();
                // $this->info("{$body['data']}");
                if (
                    isset($body['data']['data_store_count']) &&
                    $body['data']['data_store_count'] == 0 &&
                    isset($body['data']['data_store']) &&
                    $body['data']['data_store'] == 0
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
