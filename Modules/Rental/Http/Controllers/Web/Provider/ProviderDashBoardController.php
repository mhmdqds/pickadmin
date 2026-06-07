<?php

namespace Modules\Rental\Http\Controllers\Web\Provider;

use App\Models\User;
use App\Models\Store;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Modules\Rental\Entities\Trips;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionTransaction;
use Modules\Rental\Entities\TripTransaction;


class ProviderDashBoardController extends Controller
{
    public function __construct()
    {
        DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
    }


    public function providerDashboard(Request $request)
    {
        $deliveryStatistics= $this->getTripData($request);
        $data = self::dashboard_data($request);
        // $total_sell = $data['total_sell'];
        $commission = $data['commission'];
        $label = $data['label'];
        return view("rental::provider.dashboard.dashboard", [
            'pendingCount' => $deliveryStatistics['pendingCount'],
            'confirmedCount' => $deliveryStatistics['confirmedCount'],
            'ongoingCount' => $deliveryStatistics['ongoingCount'],
            'completedCount' => $deliveryStatistics['completedCount'],
            'canceledCount' => $deliveryStatistics['canceledCount'],
            'totalCount' => $deliveryStatistics['totalCount'],
            'scheduledCount' => $deliveryStatistics['scheduledCount'],
            'instantCount' => $deliveryStatistics['instantCount'],
            // 'total_sell' => $total_sell,
            'commission' => $commission,
            'label' => $label,
        ]);

    }


    public function deliveryStatistics(Request $request)
    {
        $data= $this->getTripData($request);
        return response()->json([
            'delivery_statistics' => view('rental::provider.dashboard._delivery-statistics', [
                'pendingCount' => $data['pendingCount'],
                'confirmedCount' => $data['confirmedCount'],
                'ongoingCount' => $data['ongoingCount'],
                'completedCount' => $data['completedCount'],
                'canceledCount' => $data['canceledCount'],
                'totalCount' => $data['totalCount'],
                'scheduledCount' => $data['scheduledCount'],
                'instantCount' => $data['instantCount'],
                ])->render(),
        ], 200);
    }


    private function getTripData($request){
        $statistics_type = $request->get('statistics_type', 'all');

        $tripQuery = Trips::where('provider_id',Helpers::get_store_id());
        if ($statistics_type == 'this_year') {
            $tripQuery->whereYear('created_at', now()->year);
        } elseif ($statistics_type == 'this_month') {
            $tripQuery->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
        } elseif ($statistics_type == 'this_week') {
            $tripQuery->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        }
        return [
            'pendingCount' => (clone $tripQuery)->pending()->count(),
            'confirmedCount' => (clone $tripQuery)->confirmed()->count(),
            'ongoingCount' =>  (clone $tripQuery)->ongoing()->count(),
            'completedCount' => (clone $tripQuery)->completed()->count(),
            'canceledCount' => (clone $tripQuery)->canceled()->count(),
            'scheduledCount' => (clone $tripQuery)->scheduled()->count(),
            'instantCount' => (clone $tripQuery)->instant()->count(),
            'totalCount' => (clone $tripQuery)->count(),
        ];
    }



    public function commissionOverview(Request $request)
    {
        $request->get('commission_overview', 'all');
        $data = self::dashboard_data($request);
        // $total_sell = $data['total_sell'];
        $commission = $data['commission'];
        $label = $data['label'];
        $grossEarning = collect($commission)->sum();

        return response()->json([
            'view' => view('rental::provider.dashboard._sale-chart', compact('commission', 'label', 'grossEarning'))->render(),
            'grossEarning' => $grossEarning,
            'commission' => array_map(function($val) {
                    return number_format((float)$val, 2, '.', '');
                }, array_values($commission)),
            'labels' => array_map(function($val) {
                return trim($val, '"');
            }, $label),
        ], 200);
    }

    public function dashboard_data($request)
    {
        $months = array(
            '"'.translate('Jan').'"',
            '"'.translate('Feb').'"',
            '"'.translate('Mar').'"',
            '"'.translate('Apr').'"',
            '"'.translate('May').'"',
            '"'.translate('Jun').'"',
            '"'.translate('Jul').'"',
            '"'.translate('Aug').'"',
            '"'.translate('Sep').'"',
            '"'.translate('Oct').'"',
            '"'.translate('Nov').'"',
            '"'.translate('Dec').'"'
        );
        $days = array(
            '"'.translate('Mon').'"',
            '"'.translate('Tue').'"',
            '"'.translate('Wed').'"',
            '"'.translate('Thu').'"',
            '"'.translate('Fri').'"',
            '"'.translate('Sat').'"',
            '"'.translate('Sun').'"',
        );
        // $total_sell = [];
        $commission = [];

        switch ($request['commission_overview']) {
            case "this_year":
                for ($i = 1; $i <= 12; $i++) {
                    // $total_sell[$i] = TripTransaction::where('provider_id',Helpers::get_store_id())
                    //     ->whereMonth('created_at', $i)->whereYear('created_at', now()->format('Y'))
                    //     ->sum('trip_amount');

                    $commission[$i] = TripTransaction::where('provider_id',Helpers::get_store_id())
                        ->whereMonth('created_at', $i)->whereYear('created_at', now()->format('Y'))
                        ->sum(DB::raw('store_amount - tax'));
                }
                $label = $months;

                break;

            case "this_week":
                $weekStartDate = now()->startOfWeek(); // Start from Monday

                for ($i = 0; $i < 7; $i++) { // Loop through each day of the week
                    $currentDate = $weekStartDate->copy()->addDays($i); // Get the date for the current day in the loop

                    // $total_sell[$i] = TripTransaction::where('provider_id',Helpers::get_store_id())
                    //     ->whereDate('created_at', $currentDate->format('Y-m-d'))
                    //     ->sum('trip_amount');

                    $commission[$i] = TripTransaction::where('provider_id',Helpers::get_store_id())
                        ->whereDate('created_at', $currentDate->format('Y-m-d'))
                        ->sum(DB::raw('store_amount - tax'));

                }

                $label = $days;

                break;

            case "this_month":
                $start = now()->startOfMonth();
                $total_days = now()->daysInMonth;
                $weeks = array(
                    '"Day 1-7"',
                    '"Day 8-14"',
                    '"Day 15-21"',
                    '"Day 22-' . $total_days . '"',
                );

                for ($i = 1; $i <= 4; $i++) {
                    $end = $start->copy()->addDays(6); // Set the end date for each week

                    // Adjust for the last week of the month
                    if ($i == 4) {
                        $end = now()->endOfMonth();
                    }

                    // $total_sell[$i] = TripTransaction::where('provider_id',Helpers::get_store_id())
                    //     ->whereBetween('created_at', ["{$start->format('Y-m-d')} 00:00:00", "{$end->format('Y-m-d')} 23:59:59"])
                    //     ->sum('trip_amount');

                    $commission[$i] = TripTransaction::where('provider_id',Helpers::get_store_id())
                        ->whereBetween('created_at', ["{$start->format('Y-m-d')} 00:00:00", "{$end->format('Y-m-d')} 23:59:59"])
                        ->sum(DB::raw('store_amount - tax'));

                    $start = $end->copy()->addDay();
                }

                $label = $weeks;

                break;

            default:
                for ($i = 1; $i <= 12; $i++) {
                    // $total_sell[$i] = TripTransaction::where('provider_id',Helpers::get_store_id())
                    //     ->whereMonth('created_at', $i)->whereYear('created_at', now()->format('Y'))
                    //     ->sum('trip_amount');

                    $commission[$i] = TripTransaction::where('provider_id',Helpers::get_store_id())
                        ->whereMonth('created_at', $i)->whereYear('created_at', now()->format('Y'))
                        ->sum(DB::raw('store_amount - tax'));
                }
                $label = $months;
        }


        // $dash_data['total_sell'] = $total_sell;
        $dash_data['commission'] = $commission;
        $dash_data['label'] = $label;

        return $dash_data;
    }

}
