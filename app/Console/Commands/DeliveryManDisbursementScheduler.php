<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeliveryManDisbursementScheduler extends Command
{
    protected $signature = 'dm:disbursement';
    protected $description = 'DeliveryMan disbursement scheduling based on business settings';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        app('App\Http\Controllers\Admin\DeliveryManDisbursementController')->generate_disbursement();

        if (addon_published_status('RideShare') ) {
           app(\Modules\RideShare\Http\Controllers\Web\Admin\RiderManagement\RiderDisbursementController::class)->generate_disbursement();
        }

        $this->info('DeliveryMan disbursement scheduler executed successfully.');
    }
}
