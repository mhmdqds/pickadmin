<?php

namespace Modules\Rental\Http\Controllers\Web\Admin;

use App\Models\Store;
use App\Models\SubscriptionTransaction;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Rental\Entities\Trips;
use Modules\Rental\Entities\TripTransaction;

class DashboardController extends Controller
{

    public function __construct()
    {
        DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
    }

    /**
     * @param Request $request
     * @return Application|Factory|View|RedirectResponse|JsonResponse
     */
    public function dashboard(Request $request): Application|Factory|View|RedirectResponse|JsonResponse
    {
//        dd($request->all(), auth('admin')->user()->email);
        $zone_id = $request->get('zone_id', 'all');
        $statistics_type = $request->get('statistics_type', 'all');
        $statistics_chart_type = $request->get('statistics_chart_type', 'all');

        $tripQuery = Trips::when($zone_id != 'all', function ($query) use ($zone_id) {
            return $query->Zone($zone_id);
        });

        if ($statistics_type == 'this_year') {
            $tripQuery->whereYear('created_at', now()->year);
        } elseif ($statistics_type == 'this_month') {
            $tripQuery->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
        } elseif ($statistics_type == 'this_week') {
            $tripQuery->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        }

        if ($statistics_chart_type == 'this_year') {
            $tripQuery->whereYear('created_at', now()->year);
        } elseif ($statistics_chart_type == 'this_month') {
            $tripQuery->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
        } elseif ($statistics_chart_type == 'this_week') {
            $tripQuery->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        }

        $distanceWiseCount = (clone $tripQuery)->where('trip_type', 'distance_wise')->count();
        $hourlyCount = (clone $tripQuery)->where('trip_type', 'hourly')->count();

        $totalCount = (clone $tripQuery)->count();
        $pendingCount = (clone $tripQuery)->pending()->count();
        $confirmedCount = (clone $tripQuery)->confirmed()->count();
        $ongoingCount = (clone $tripQuery)->ongoing()->count();
        $completedCount = (clone $tripQuery)->completed()->count();
        $canceledCount = (clone $tripQuery)->canceled()->count();
        $zoneName = $zone_id == 'all' ? 'All' : Zone::where('id', $zone_id)->value('name');

        $module_type = Config::get('module.current_module_type');
        if ($module_type == 'settings') {
            return redirect()->route('admin.business-settings.business-setup');
        }

        $data = self::dashboard_data($request);
        $total_sell = $data['total_sell'];
        $commission = $data['commission'];
        $total_subs = $data['total_subs'];
        $topCustomers = $data['top_customers'];
        $topProviders = $data['top_providers'];
        $label = $data['label'];
        $grossEarning = collect($total_sell)->sum();
        if ($request->ajax()) {
            return response()->json([
                'delivery_statistics' => view('rental::admin.partials.delivery-statistics', compact('pendingCount', 'confirmedCount', 'ongoingCount', 'completedCount', 'canceledCount', 'totalCount'))->render(),
                'top_providers' => view('rental::admin.partials.top-providers', compact('topProviders'))->render(),
                'top_customers' => view('rental::admin.partials.top-customers', compact('topCustomers'))->render(),
                'sale_chart' => view('rental::admin.partials.sale-chart', compact('total_sell', 'commission', 'total_subs','label'))->render(),
                'by_trip_type' => view('rental::admin.partials.by-trip-type', compact('hourlyCount', 'distanceWiseCount', 'totalCount'))->render(),
                'zoneName' => $zoneName,
                'hourlyCount' => $hourlyCount,
                'distanceWiseCount' => $distanceWiseCount,
                'totalCount' => $totalCount,
                'total_sell' => array_map(function($val) {
                    return number_format((float)$val, 2, '.', '');
                }, array_values($total_sell)),
                'commission' => array_map(function($val) {
                    return number_format((float)$val, 2, '.', '');
                }, array_values($commission)),
                'total_subs' => array_map(function($val) {
                    return number_format((float)$val, 2, '.', '');
                }, array_values($total_subs)),
            'labels' => array_map(function($val) {
                    return trim($val, '"');
                }, $label),
                'grossEarning' => number_format((float)$grossEarning, 2, '.', '')
            ], 200);
        }

        return view("rental::admin.dashboard-{$module_type}", compact(
            'pendingCount',
            'confirmedCount',
            'ongoingCount',
            'completedCount',
            'canceledCount',
            'totalCount',
            'zoneName',
            'total_sell', 'commission', 'total_subs', 'topCustomers', 'topProviders', 'label', 'distanceWiseCount', 'hourlyCount'
        ));
    }

    /**
     * @param Request $request
     * @return Application|Factory|View|RedirectResponse|JsonResponse
     */
    public function byTripType(Request $request): Application|Factory|View|RedirectResponse|JsonResponse
    {
        $zone_id = $request->get('zone_id', 'all');
        $type = $request->get('trip_overview', 'all');

        $tripQuery = Trips::when($zone_id != 'all', function ($query) use ($zone_id) {
            return $query->Zone($zone_id);
        });

        if ($type == 'this_year') {
            $tripQuery->whereYear('created_at', now()->year);
        } elseif ($type == 'this_month') {
            $tripQuery->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
        } elseif ($type == 'this_week') {
            $tripQuery->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        }

        $totalCount = $tripQuery->count();
        $distanceWiseCount = (clone $tripQuery)->where('trip_type', 'distance_wise')->count();
        $hourlyCount = (clone $tripQuery)->where('trip_type', 'hourly')->count();

        return response()->json([
            'view' => view('rental::admin.partials.by-trip-type', compact('hourlyCount', 'distanceWiseCount', 'totalCount'))->render(),
            'hourlyCount' => $hourlyCount,
            'distanceWiseCount' => $distanceWiseCount,
            'totalCount' => $totalCount
        ], 200);
    }

    /**
     * @param Request $request
     * @return Application|Factory|View|RedirectResponse|JsonResponse
     */
    public function commissionOverview(Request $request): Application|Factory|View|RedirectResponse|JsonResponse
    {
        $request->get('zone_id', 'all');
        $request->get('commission_overview', 'all');

        $data = self::dashboard_data($request);
        $total_sell = $data['total_sell'];
        $commission = $data['commission'];
        $total_subs = $data['total_subs'];
        $label = $data['label'];
        $grossEarning = collect($total_sell)->sum();

        return response()->json([
            'view' => view('rental::admin.partials.sale-chart', compact('total_sell', 'commission', 'total_subs', 'label', 'grossEarning'))->render(),
            'grossEarning' => $grossEarning,
            'total_sell' => array_map(function($val) {
                return number_format((float)$val, 2, '.', '');
            }, array_values($total_sell)),
            'commission' => array_map(function($val) {
                return number_format((float)$val, 2, '.', '');
            }, array_values($commission)),
            'total_subs' => array_map(function($val) {
                return number_format((float)$val, 2, '.', '');
            }, array_values($total_subs)),
           'labels' => array_map(function($val) {
                return trim($val, '"');
            }, $label)
                ], 200);
    }
    public function dashboard_data($request)
    {
        $topCustomers = User::select('users.*')
            ->selectRaw('COUNT(trips.id) as trip_count')
            ->join('trips', 'trips.user_id', '=', 'users.id')
            ->when(is_numeric($request['zone_id']), function ($q) use ($request) {
                return $q->where('users.zone_id', $request['zone_id']);
            })
            ->groupBy('users.id')
            ->having('trip_count', '>', 0)
            ->orderBy('trip_count', 'desc')
            ->take(5)
            ->get();


        $topProvider = Store::withcount('trips')
            ->when(is_numeric($request['module_id']), function ($q) use ($request) {
                return $q->where('module_id', $request['module_id']);
            })
            ->when(is_numeric($request['zone_id']), function ($q) use ($request) {
                return $q->where('zone_id', $request['zone_id']);
            })
            ->having('trips_count', '>', 0)
            ->orderBy('trips_count', 'desc')
            ->take(5)
            ->get();



        // custom filtering for bar chart
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
        $total_sell = [];
        $commission = [];

        switch ($request['commission_overview']) {
            case "this_year":
                for ($i = 1; $i <= 12; $i++) {
                    $total_sell[$i] = TripTransaction::when(is_numeric($request['module_id']), function ($q) use ($request) {
                            return $q->where('module_id', $request['module_id']);
                        })
                        ->when(is_numeric($request['zone_id']), function ($q) use ($request) {
                            return $q->where('zone_id', $request['zone_id']);
                        })
                        ->whereMonth('created_at', $i)->whereYear('created_at', now()->format('Y'))
                        ->sum('trip_amount');

                    $commission[$i] = TripTransaction::when(is_numeric($request['module_id']), function ($q) use ($request) {
                            return $q->where('module_id', $request['module_id']);
                        })
                        ->when(is_numeric($request['zone_id']), function ($q) use ($request) {
                            return $q->where('zone_id', $request['zone_id']);
                        })
                        ->whereMonth('created_at', $i)->whereYear('created_at', now()->format('Y'))
                        ->sum(DB::raw('admin_commission + admin_expense'));

                    $total_subs[$i] = SubscriptionTransaction::when(is_numeric($request['zone_id']),function($q)use($request){
                        return $q->whereHas('store', function($query)use($request){
                            return $query->where('zone_id', $request['zone_id'])->where('module_id', $request['module_id']);
                        });
                    })
                    ->whereMonth('created_at', $i)->whereYear('created_at', now()->format('Y'))
                    ->sum('paid_amount');
                }
                $label = $months;

                break;

            case "this_week":
                $weekStartDate = now()->startOfWeek(); // Start from Monday

                for ($i = 0; $i < 7; $i++) { // Loop through each day of the week
                    $currentDate = $weekStartDate->copy()->addDays($i); // Get the date for the current day in the loop

                    $total_sell[$i] = TripTransaction::when(is_numeric($request['module_id']), function ($q) use ($request) {
                            return $q->where('module_id', $request['module_id']);
                        })
                        ->when(is_numeric($request['zone_id']), function ($q) use ($request) {
                            return $q->where('zone_id', $request['zone_id']);
                        })
                        ->whereDate('created_at', $currentDate->format('Y-m-d'))
                        ->sum('trip_amount');

                    $commission[$i] = TripTransaction::when(is_numeric($request['module_id']), function ($q) use ($request) {
                            return $q->where('module_id', $request['module_id']);
                        })
                        ->when(is_numeric($request['zone_id']), function ($q) use ($request) {
                            return $q->where('zone_id', $request['zone_id']);
                        })
                        ->whereDate('created_at', $currentDate->format('Y-m-d'))
                        ->sum(DB::raw('admin_commission + admin_expense'));

                    $total_subs[$i] = SubscriptionTransaction::when(is_numeric($request['zone_id']),function($q)use($request){
                        return $q->whereHas('store', function($query)use($request){
                            return $query->where('zone_id', $request['zone_id'])->where('module_id', $request['module_id']);
                        });
                    })
                    ->whereDate('created_at', $currentDate->format('Y-m-d'))
                    ->sum('paid_amount');
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

                    $total_sell[$i] = TripTransaction::when(is_numeric($request['module_id']), function ($q) use ($request) {
                            return $q->where('module_id', $request['module_id']);
                        })
                        ->when(is_numeric($request['zone_id']), function ($q) use ($request) {
                            return $q->where('zone_id', $request['zone_id']);
                        })
                        ->whereBetween('created_at', ["{$start->format('Y-m-d')} 00:00:00", "{$end->format('Y-m-d')} 23:59:59"])
                        ->sum('trip_amount');

                    $commission[$i] = TripTransaction::when(is_numeric($request['module_id']), function ($q) use ($request) {
                            return $q->where('module_id', $request['module_id']);
                        })
                        ->when(is_numeric($request['zone_id']), function ($q) use ($request) {
                            return $q->where('zone_id', $request['zone_id']);
                        })
                        ->whereBetween('created_at', ["{$start->format('Y-m-d')} 00:00:00", "{$end->format('Y-m-d')} 23:59:59"])
                        ->sum(DB::raw('admin_commission + admin_expense'));


                    $total_subs[$i] = SubscriptionTransaction::when(is_numeric($request['zone_id']),function($q)use($request){
                        return $q->whereHas('store', function($query)use($request){
                            return $query->where('zone_id', $request['zone_id'])->where('module_id', $request['module_id']);
                        });
                    })
                    ->whereBetween('created_at', ["{$start->format('Y-m-d')} 00:00:00", "{$end->format('Y-m-d')} 23:59:59"])
                    ->sum('paid_amount');

                    $start = $end->copy()->addDay();
                }

                $label = $weeks;

                break;

            default:
                for ($i = 1; $i <= 12; $i++) {
                    $total_sell[$i] = TripTransaction::when(is_numeric($request['module_id']), function ($q) use ($request) {
                            return $q->where('module_id', $request['module_id']);
                        })
                        ->when(is_numeric($request['zone_id']), function ($q) use ($request) {
                            return $q->where('zone_id', $request['zone_id']);
                        })
                        ->whereMonth('created_at', $i)->whereYear('created_at', now()->format('Y'))
                        ->sum('trip_amount');

                    $commission[$i] = TripTransaction::when(is_numeric($request['module_id']), function ($q) use ($request) {
                            return $q->where('module_id', $request['module_id']);
                        })
                        ->when(is_numeric($request['zone_id']), function ($q) use ($request) {
                            return $q->where('zone_id', $request['zone_id']);
                        })
                        ->whereMonth('created_at', $i)->whereYear('created_at', now()->format('Y'))
                        ->sum(DB::raw('admin_commission + admin_expense'));

                    $total_subs[$i] = SubscriptionTransaction::when(is_numeric($request['zone_id']),function($q)use($request){
                        return $q->whereHas('store', function($query)use($request){
                            return $query->where('zone_id', $request['zone_id'])->where('module_id', $request['module_id']);
                        });
                    })
                    ->whereMonth('created_at', $i)->whereYear('created_at', now()->format('Y'))
                    ->sum('paid_amount');
                }
                $label = $months;

        }

        $dash_data['top_providers'] = $topProvider;
        $dash_data['top_customers'] = $topCustomers;
        $dash_data['total_sell'] = $total_sell;
        $dash_data['commission'] = $commission;
        $dash_data['total_subs'] = $total_subs;
        $dash_data['label'] = $label;

        return $dash_data;
    }

}
