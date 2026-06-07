<?php

namespace Modules\Rental\Http\Controllers\Web\Admin;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Zone;
use App\Models\Store;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Rental\Entities\Trips;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Rental\Entities\Vehicle;
use Modules\Rental\Entities\TripTransaction;
use Modules\Rental\Entities\VehicleCategory;
use Modules\Rental\Exports\TripReportExport;
use Modules\Rental\Exports\VehicleReportExport;
use Modules\Rental\Exports\TransactionReportExport;
use Modules\Rental\Exports\ProviderTripReportExport;
use Modules\Rental\Exports\ProviderSalesReportExport;
use Modules\Rental\Exports\ProviderSummaryReportExport;

class ReportController extends Controller
{
    public function transactionReport(Request $request)
    {

        if (session()->has('from_date') == false) {
            session()->put('from_date', date('Y-m-01'));
            session()->put('to_date', date('Y-m-30'));
        }
        $key = explode(' ', $request['search']);
        $from = session('from_date');
        $to = session('to_date');
        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $provider_id = $request->query('provider_id', 'all');
        $provider = is_numeric($provider_id) ? Store::findOrFail($provider_id) : null;
        $filter = $request->query('filter', 'all_time');


        $data=$this->getTransactionData($request);
        $tripTransactions = $data['tripTransactions']->paginate(config('default_pagination'))->withQueryString();

        $adminEarned = $data['earnings']->admin_earned;
        $providerEarned =  $data['earnings']->provider_earned;
        $totalAmount =  $data['earnings']->total_amount;

        return view('rental::admin.report.transaction-report', compact('tripTransactions', 'zone', 'provider', 'filter', 'adminEarned', 'providerEarned','key','totalAmount'));
    }


        private function getTransactionData($request){

            if (session()->has('from_date') == false) {
                session()->put('from_date', date('Y-m-01'));
                session()->put('to_date', date('Y-m-30'));
            }
            $key = explode(' ', $request['search']);
            $from = session('from_date');
            $to = session('to_date');
            $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
            $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
            $provider_id = $request->query('provider_id', 'all');
            $provider = is_numeric($provider_id) ? Store::findOrFail($provider_id) : null;
            $filter = $request->query('filter', 'all_time');


            $tripTransactions = TripTransaction::with('trip', 'trip.trip_details', 'trip.customer', 'trip.provider')->when(isset($zone), function ($query) use ($zone) {
                return $query->where('zone_id', $zone->id);
            })
                ->when(isset($key), function ($query) use ($key) {
                    return $query->where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->orWhere('trip_id', 'like', "%{$value}%");
                        }
                    });
                })
                ->when(isset($provider), function ($query) use ($provider) {
                    return $query->where('provider_id', $provider->id);
                })
                ->when(request('module_id'), function ($query) {
                    return $query->module(request('module_id'));
                })
                ->applyDateFilter($filter, $from, $to)
                ->orderBy('created_at', 'desc');


            $earnings = TripTransaction::when(isset($zone), function ($query) use ($zone) {
                return $query->where('zone_id', $zone->id);
            })
            ->when(isset($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('trip_id', 'like', "%{$value}%");
                    }
                });
            })
            ->when(isset($provider), function ($query) use ($provider) {
                return $query->where('provider_id', $provider->id);
            })
            ->when(request('module_id'), function ($query) {
                return $query->module(request('module_id'));
            })
            ->applyDateFilter($filter, $from, $to)
            ->select(
                DB::raw('SUM(admin_net_income) as admin_earned'),
                DB::raw('SUM(store_amount) as provider_earned'),
                DB::raw('SUM(trip_amount) as total_amount')
            )
            ->first();

            return [ 'tripTransactions'=>$tripTransactions, 'earnings'=> $earnings];
        }


    public function transactionExport(Request $request)
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', date('Y-m-01'));
            session()->put('to_date', date('Y-m-30'));
        }
        $from = session('from_date');
        $to = session('to_date');
        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $provider_id = $request->query('provider_id', 'all');
        $filter = $request->query('filter', 'all_time');

        $data=$this->getTransactionData($request);
        $tripTransactions = $data['tripTransactions']->get();

        $adminEarned = $data['earnings']->admin_earned;
        $providerEarned =  $data['earnings']->provider_earned;
        $totalAmount =  $data['earnings']->total_amount;

        $data = [
            'tripTransactions'=>$tripTransactions,
            'search'=>$request->search??null,
            'from'=>(($filter == 'custom') && $from)?$from:null,
            'to'=>(($filter == 'custom') && $to)?$to:null,
            'zone'=>is_numeric($zone_id)?Helpers::get_zones_name($zone_id):null,
            'provider'=>is_numeric($provider_id)?Helpers::get_stores_name($provider_id):null,
            'adminEarned'=>$adminEarned,
            'providerEarned'=>$providerEarned,
            'totalAmount'=>$totalAmount,
            'filter'=>$filter,
        ];

        if ($request->type == 'excel') {
            return Excel::download(new TransactionReportExport($data), 'TransactionReport.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new TransactionReportExport($data), 'TransactionReport.csv');
        }
    }

    public function tripReport(Request $request)
    {
        $key = explode(' ', $request['search']);

        if (session()->has('from_date') == false) {
            session()->put('from_date', date('Y-m-01'));
            session()->put('to_date', date('Y-m-30'));
        }
        $from = session('from_date');
        $to = session('to_date');
        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $provider_id = $request->query('provider_id', 'all');
        $provider = is_numeric($provider_id) ? Store::findOrFail($provider_id) : null;
        $customer_id = $request->query('customer_id', 'all');
        $customer = is_numeric($customer_id) ? User::findOrFail($customer_id) : null;
        $filter = $request->query('filter', 'all_time');

        $trips = Trips::with(['customer', 'provider', 'trip_details', 'trip_transaction'])
            ->when(request('module_id'), function ($query) {
                return $query->module(request('module_id'));
            })
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->where('zone_id', $zone->id);
            })
            ->when(isset($provider), function ($query) use ($provider) {
                return $query->where('provider_id', $provider->id);
            })
            ->when(isset($customer), function ($query) use ($customer) {
                return $query->where('user_id', $customer->id);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('schedule_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('schedule_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->when(isset($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('id', 'like', "%{$value}%");
                    }
                });
            })
//
//            ->withSum('transaction', 'admin_commission')
//            ->withSum('transaction', 'admin_expense')
//            ->withSum('transaction', 'delivery_fee_comission')
            ->orderBy('schedule_at', 'desc')->paginate(config('default_pagination'))->withQueryString();

        // trip card values calculation
        $trips_list = Trips::when(request('module_id'), function ($query) {
            return $query->module(request('module_id'));
        })
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->where('zone_id', $zone->id);
            })
            ->when(isset($provider), function ($query) use ($provider) {
                return $query->where('provider_id', $provider->id);
            })
            ->when(isset($customer), function ($query) use ($customer) {
                return $query->where('user_id', $customer->id);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('schedule_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('schedule_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->when(isset($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('id', 'like', "%{$value}%");
                    }
                });
            })
            ->orderBy('schedule_at', 'desc')->get();


        $total_canceled_count = $trips_list->where('trip_status', 'canceled')->count();
        $total_completed_count = $trips_list->where('trip_status', 'completed')->count();
        $total_progress_count = $trips_list->whereIn('trip_status', ['confirmed','pending'])->count();
        $total_failed_count = $trips_list->where('trip_status', 'failed')->count();
        $total_ongoing_count = $trips_list->whereIn('trip_status', ['ongoing'])->count();
        return view('rental::admin.report.trip-report', compact('trips', 'trips_list', 'zone', 'provider', 'filter', 'customer', 'total_ongoing_count', 'total_failed_count', 'total_progress_count', 'total_canceled_count', 'total_completed_count'));
    }
    public function tripReportExport(Request $request)
    {

        $key = isset($request['search']) ? explode(' ', $request['search']) : [];

        if (session()->has('from_date') == false) {
            session()->put('from_date', date('Y-m-01'));
            session()->put('to_date', date('Y-m-30'));
        }
        $from = session('from_date');
        $to = session('to_date');
        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $provider_id = $request->query('provider_id', 'all');
        $provider = is_numeric($provider_id) ? Store::findOrFail($provider_id) : null;
        $customer_id = $request->query('customer_id', 'all');
        $customer = is_numeric($customer_id) ? User::findOrFail($customer_id) : null;
        $filter = $request->query('filter', 'all_time');

        $trips = Trips::with(['customer', 'provider', 'trip_details', 'trip_transaction'])
            ->when(request('module_id'), function ($query) {
                return $query->module(request('module_id'));
            })
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->where('zone_id', $zone->id);
            })
            ->when(isset($provider), function ($query) use ($provider) {
                return $query->where('provider_id', $provider->id);
            })
            ->when(isset($customer), function ($query) use ($customer) {
                return $query->where('user_id', $customer->id);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('schedule_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('schedule_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->when(isset($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('id', 'like', "%{$value}%");
                    }
                });
            })
//
//            ->withSum('transaction', 'admin_commission')
//            ->withSum('transaction', 'admin_expense')
//            ->withSum('transaction', 'delivery_fee_comission')
            ->orderBy('schedule_at', 'desc')->get();

        $data = [
            'trips'=>$trips,
            'search'=>$request->search??null,
            'from'=>(($filter == 'custom') && $from)?$from:null,
            'to'=>(($filter == 'custom') && $to)?$to:null,
            'zone'=>is_numeric($zone_id)?Helpers::get_zones_name($zone_id):null,
            'provider'=>is_numeric($provider_id)?Helpers::get_stores_name($provider_id):null,
            'customer'=>is_numeric($customer_id)?Helpers::get_customer_name($customer_id):null,
            'filter'=>$filter,
        ];

        if ($request->type == 'excel') {
            return Excel::download(new TripReportExport($data), 'TripReport.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new TripReportExport($data), 'TripReport.csv');
        }
    }
    public function vehicleReport(Request $request)
    {
        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $provider_id = $request->query('provider_id', 'all');
        $category_id = $request->query('category_id', 'all');
        $filter = $request->query('filter', 'all_time');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $provider = is_numeric($provider_id) ? Store::findOrFail($provider_id) : null;
        $category = is_numeric($category_id) ? VehicleCategory::findOrFail($category_id) : null;
        $vehicles = $this->get_vehicle_data($request);
        $vehicles =  $vehicles->paginate(config('default_pagination'))->withQueryString();

        return view('rental::admin.report.vehicle-wise-report', compact('zone', 'provider', 'category', 'vehicles', 'filter'));
    }
    public function vehicleReportExport(Request $request)
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', now()->firstOfMonth()->format('Y-m-d'));
            session()->put('to_date', now()->lastOfMonth()->format('Y-m-d'));
        }
        $from = session('from_date');
        $to = session('to_date');

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $provider_id = $request->query('provider_id', 'all');
        $category_id = $request->query('category_id', 'all');
        $filter = $request->query('filter', 'all_time');
        $vehicles = $this->get_vehicle_data($request);
        $vehicles =  $vehicles->get();

        $data = [
            'vehicles'=>$vehicles,
            'search'=>$request->search??null,
            'from'=>(($filter == 'custom') && $from)?$from:null,
            'to'=>(($filter == 'custom') && $to)?$to:null,
            'zone'=>is_numeric($zone_id)?Helpers::get_zones_name($zone_id):null,
            'provider'=>is_numeric($provider_id)?Helpers::get_stores_name($provider_id):null,
            'category'=>is_numeric($category_id)?Helpers::get_category_name($category_id):null,
            'module'=>request('module_id')?Helpers::get_module_name(request('module_id')):null,
            'filter'=>$filter,
        ];

        if ($request->type == 'excel') {
            return Excel::download(new VehicleReportExport($data), 'VehicleReport.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new VehicleReportExport($data), 'VehicleReport.csv');
        }
    }
    private static function get_vehicle_data($request){

        $key = explode(' ', $request['search']);
        if (session()->has('from_date') == false) {
            session()->put('from_date', now()->firstOfMonth()->format('Y-m-d'));
            session()->put('to_date', now()->lastOfMonth()->format('Y-m-d'));
        }
        $from = session('from_date');
        $to = session('to_date');

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $provider_id = $request->query('provider_id', 'all');
        $category_id = $request->query('category_id', 'all');
        $filter = $request->query('filter', 'all_time');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $provider = is_numeric($provider_id) ? Store::findOrFail($provider_id) : null;
        $category = is_numeric($category_id) ? VehicleCategory::findOrFail($category_id) : null;

        $vehicles =Vehicle::withCount([
                'tripDetails as trips_count' => function ($query) use ($from, $to, $filter) {
                    $query->whereHas('trip', function ($query) {
                        return $query->whereIn('trip_status', ['completed']);
                    })->applyDateFilter($filter, $from, $to);
                }, 'vehicleIdentities'
            ] )
            ->withSum([
                'tripDetails' => function ($query) use ($from, $to, $filter) {
                    $query->whereHas('trip', function ($query) {
                        return $query->whereIn('trip_status', ['completed']);
                    })->applyDateFilter($filter, $from, $to);
                },
            ], 'quantity')

            ->addSelect([
                'total_discount' => function ($query) use ($from, $to, $filter) {
                    $query->selectRaw('SUM(trip_details.discount_on_trip * trip_details.quantity)')
                        ->from('trip_details')
                        ->join('trips', 'trips.id', '=', 'trip_details.trip_id')
                        ->whereColumn('trip_details.vehicle_id', 'vehicles.id')
                        ->whereIn('trips.trip_status', ['completed'])
                        ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                            return $query->whereBetween('trip_details.created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
                        })
                        ->when(isset($filter) && $filter == 'this_year', function ($query) {
                            return $query->whereYear('trip_details.created_at', now()->format('Y'));
                        })
                        ->when(isset($filter) && $filter == 'this_month', function ($query) {
                            return $query->whereMonth('trip_details.created_at', now()->format('m'))
                                ->whereYear('trip_details.created_at', now()->format('Y'));
                        })
                        ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                            return $query->whereYear('trip_details.created_at', date('Y') - 1);
                        })
                        ->when(isset($filter) && $filter == 'this_week', function ($query) {
                            return $query->whereBetween('trip_details.created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]); // Filter by this week
                        });
                },
                'trips_sum_price' => function ($query) use ($from, $to, $filter) {
                    $query->selectRaw('SUM(trip_details.price * trip_details.quantity)')
                        ->from('trip_details')
                        ->join('trips', 'trips.id', '=', 'trip_details.trip_id')
                        ->whereColumn('trip_details.vehicle_id', 'vehicles.id')
                        ->whereIn('trips.trip_status', ['completed'])
                        ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                            return $query->whereBetween('trip_details.created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
                        })
                        ->when(isset($filter) && $filter == 'this_year', function ($query) {
                            return $query->whereYear('trip_details.created_at', now()->format('Y'));
                        })
                        ->when(isset($filter) && $filter == 'this_month', function ($query) {
                            return $query->whereMonth('trip_details.created_at', now()->format('m'))
                                ->whereYear('trip_details.created_at', now()->format('Y'));
                        })
                        ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                            return $query->whereYear('trip_details.created_at', date('Y') - 1);
                        })
                        ->when(isset($filter) && $filter == 'this_week', function ($query) {
                            return $query->whereBetween('trip_details.created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]); // Filter by this week
                        });
                },
            ])

            ->when($request->query('module_id', null), function ($query) use ($request) {
                return $query->module($request->query('module_id'));
            })
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('provider_id', $zone->stores->pluck('id'));
            })
            ->when(isset($provider), function ($query) use ($provider) {
                return $query->where('provider_id', $provider->id);
            })
            ->when(isset($category), function ($query) use ($category) {
                return $query->where('category_id', $category->id);
            })
            ->when(isset($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                });
            })
            ->with('provider')
            ->having('trips_count', '>' ,0)
            ->orderBy('trips_count', 'desc');

        return $vehicles;
    }
    public function set_date(Request $request)
    {
        session()->put('from_date', date('Y-m-d', strtotime($request['from'])));
        session()->put('to_date', date('Y-m-d', strtotime($request['to'])));
        return back();
    }
    public function providerSummaryReport(Request $request)
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
            '"'.translate('Sun').'"',
            '"'.translate('Mon').'"',
            '"'.translate('Tue').'"',
            '"'.translate('Wed').'"',
            '"'.translate('Thu').'"',
            '"'.translate('Fri').'"',
            '"'.translate('Sat').'"'
        );

        $key = explode(' ', $request['search']);

        $filter = $request->query('filter', 'all_time');

        $providers = Store::with('trips')
            ->whereHas('vendor',function($query){
                $query->where('status',1);
            })
            ->withCount('trips')
            ->withModuleType('rental')
            ->when(isset($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                });
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->with([
                    'trips' => function ($query) {
                        $query->whereYear('schedule_at', now()->format('Y'));
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->with([
                    'trips' => function ($query) {
                        $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->with([
                    'trips' => function ($query) {
                        $query->whereYear('schedule_at', date('Y') - 1);
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->with([
                    'trips' => function ($query) {
                        $query->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'all_time', function ($query) {
                return $query->with([
                    'trips' => function ($query) {
                        $query;
                    },
                ]);
            })
            ->orderBy('trips_count', 'DESC')->paginate(config('default_pagination'));

        $new_providers = Store::withModuleType('rental')->whereHas('vendor',function($query){
                $query->where('status',1);
            })
            ->applyDateFilter($filter)
            ->count();

        $trip_payment_methods = Trips::when(isset($filter) && $filter == 'this_year', function ($query) {
            return $query->whereYear('schedule_at', now()->format('Y'));
        })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('schedule_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->Completed()
            ->selectRaw(DB::raw("sum(`trip_amount`) as total_trip_amount, count(*) as trip_count, IF((`payment_method`='cash_payment'), `payment_method`, IF(`payment_method`='wallet',`payment_method`, 'digital_payment')) as 'payment_methods'"))->groupBy('payment_methods')
            ->get();

        $trips = Trips::when(isset($filter) && $filter == 'this_year', function ($query) {
            return $query->whereYear('schedule_at', now()->format('Y'));
        })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('schedule_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })->get();

        $total_trip_amount = $trips->whereIn('trip_status', ['completed'])->sum('trip_amount');
        $total_ongoing = $trips->whereIn('trip_status', ['pending', 'accepted', 'confirmed', 'processing', 'ongoing'])->count();
        $total_canceled = $trips->whereIn('trip_status', ['failed', 'canceled'])->count();
        $total_completed = $trips->whereIn('trip_status', ['completed'])->count();

        $vehicles = Vehicle::applyDateFilter($filter)->count();

        $monthly_trip = [];
        switch ($filter) {
            case "all_time":
                $monthly_trip = Trips::select(
                    DB::raw("(sum(trip_amount)) as trip_amount"),
                    DB::raw("(DATE_FORMAT(schedule_at, '%Y')) as year")
                )
                    ->Completed()
                    ->groupBy(DB::raw("DATE_FORMAT(schedule_at, '%Y')"))
                    ->get()->toArray();

                $label = array_map(function ($trip) {
                    return $trip['year'];
                }, $monthly_trip);
                $data = array_map(function ($trip) {
                    return $trip['trip_amount'];
                }, $monthly_trip);
                break;
            case "this_year":
                for ($i = 1; $i <= 12; $i++) {
                    $monthly_trip[$i] = Trips::Completed()->whereMonth('schedule_at', $i)->whereYear('schedule_at', now()->format('Y'))
                        ->sum('trip_amount');
                }
                $label = $months;
                $data = $monthly_trip;
                break;
            case "previous_year":
                for ($i = 1; $i <= 12; $i++) {
                    $monthly_trip[$i] = Trips::Completed()->whereMonth('schedule_at', $i)->whereYear('schedule_at', date('Y') - 1)
                        ->sum('trip_amount');
                }
                $label = $months;
                $data = $monthly_trip;
                break;
            case "this_week":
                $weekStartDate = now()->startOfWeek();
                for ($i = 1; $i <= 7; $i++) {
                    $monthly_trip[$i] = Trips::Completed()->whereDay('schedule_at', $weekStartDate->format('d'))->whereMonth('schedule_at', now()->format('m'))
                        ->sum('trip_amount');

                    $weekStartDate = $weekStartDate->addDays(1);
                }
                $label = $days;
                $data = $monthly_trip;
                break;
            case "this_month":
                $start = now()->startOfMonth();
                $end = now()->startOfMonth()->addDays(7);
                $total_day = now()->daysInMonth;
                $remaining_days = now()->daysInMonth - 28;
                $weeks = array(
                    '"'.translate('Day').' 1-7"',
                    '"'.translate('Day').' 8-14"',
                    '"'.translate('Day').' 15-21"',
                    '"'.translate('Day').' 22-' . $total_day . '"',
                );
                for ($i = 1; $i <= 4; $i++) {
                    $monthly_trip[$i] = Trips::Completed()
                        ->whereBetween('schedule_at', ["{$start->format('Y-m-d')} 00:00:00", "{$end->format('Y-m-d')} 23:59:59"])
                        ->sum('trip_amount');
                    $start = $start->addDays(7);
                    $end = $i == 3 ? $end->addDays(7 + $remaining_days) : $end->addDays(7);
                }
                $label = $weeks;
                $data = $monthly_trip;
                break;
            default:
                for ($i = 1; $i <= 12; $i++) {
                    $monthly_trip[$i] = Trips::Completed()->whereMonth('schedule_at', $i)->whereYear('schedule_at', now()->format('Y'))
                        ->sum('trip_amount');
                }
                $label = $months;
                $data = $monthly_trip;
        }

        return view('rental::admin.report.provider-summary-report', compact('providers', 'new_providers', 'trips', 'trip_payment_methods', 'vehicles', 'monthly_trip', 'label', 'data', 'filter', 'total_trip_amount', 'total_ongoing', 'total_canceled', 'total_completed'));
    }
    public function providerSummaryExport(Request $request)
    {
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];

        $filter = $request->query('filter', 'all_time');

        $providers = Store::with('trips')->withCount('trips')
            ->whereHas('vendor',function($query){
                $query->where('status',1);
            })
            ->withModuleType('rental')
            ->when(isset($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                });
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->with([
                    'trips' => function ($query) {
                        $query->whereYear('schedule_at', now()->format('Y'));
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->with([
                    'trips' => function ($query) {
                        $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->with([
                    'trips' => function ($query) {
                        $query->whereYear('schedule_at', date('Y') - 1);
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->with([
                    'trips' => function ($query) {
                        $query->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'all_time', function ($query) {
                return $query->with([
                    'trips' => function ($query) {
                        $query;
                    },
                ]);
            })
            ->orderBy('trips_count', 'DESC')->get();

        $new_providers = Store::withModuleType('rental')->whereHas('vendor',function($query){
                $query->where('status',1);
            })
            ->applyDateFilter($filter)->count();

        $trip_payment_methods = Trips::when(isset($filter) && $filter == 'this_year', function ($query) {
            return $query->whereYear('schedule_at', now()->format('Y'));
        })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('schedule_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->Completed()
            ->selectRaw(DB::raw("sum(`trip_amount`) as total_trip_amount, count(*) as trip_count, IF((`payment_method`='cash_payment'), `payment_method`, IF(`payment_method`='wallet',`payment_method`, 'digital_payment')) as 'payment_methods'"))->groupBy('payment_methods')
            ->get();

        $trips = Trips::when(isset($filter) && $filter == 'this_year', function ($query) {
            return $query->whereYear('schedule_at', now()->format('Y'));
        })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('schedule_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })->get();
        $total_trip_amount = $trips->whereIn('trip_status', ['completed'])->sum('trip_amount');
        $total_ongoing = $trips->whereIn('trip_status', ['pending', 'accepted', 'confirmed', 'processing', 'ongoing'])->count();
        $total_canceled = $trips->whereIn('trip_status', ['failed', 'canceled'])->count();
        $total_completed = $trips->whereIn('trip_status', ['completed'])->count();

        $data = [
            'providers'=>$providers,
            'search'=>$request->search??null,
            'new_providers'=>$new_providers,
            'trips'=>$trips->count(),
            'total_trip_amount'=>$total_trip_amount,
            'total_ongoing'=>$total_ongoing,
            'total_canceled'=>$total_canceled,
            'total_completed'=>$total_completed,
            'cash_payments'=>count($trip_payment_methods)>0?\App\CentralLogics\Helpers::number_format_short(isset($trip_payment_methods[0])?$trip_payment_methods[0]->total_trip_amount:0):0,
            'digital_payments'=>count($trip_payment_methods)>0?\App\CentralLogics\Helpers::number_format_short(isset($trip_payment_methods[1])?$trip_payment_methods[1]->total_trip_amount:0):0,
            'wallet_payments'=>count($trip_payment_methods)>0?\App\CentralLogics\Helpers::number_format_short(isset($trip_payment_methods[2])?$trip_payment_methods[2]->total_trip_amount:0):0,
            'filter'=>$filter,
        ];
        if ($request->type == 'excel') {
            return Excel::download(new ProviderSummaryReportExport($data), 'ProviderSummaryReport.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new ProviderSummaryReportExport($data), 'ProviderSummaryReport.csv');
        }
    }
    public function providerSalesReport(Request $request)
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', now()->firstOfMonth()->format('Y-m-d'));
            session()->put('to_date', now()->lastOfMonth()->format('Y-m-d'));
        }
        $from = session('from_date');
        $to = session('to_date');

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
            '"'.translate('Sun').'"',
            '"'.translate('Mon').'"',
            '"'.translate('Tue').'"',
            '"'.translate('Wed').'"',
            '"'.translate('Thu').'"',
            '"'.translate('Fri').'"',
            '"'.translate('Sat').'"'
        );
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];
        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $provider_id = $request->query('provider_id', 'all');
        $filter = $request->query('filter', 'all_time');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $provider = is_numeric($provider_id) ? Store::findOrFail($provider_id) : null;

        // vehicles


        $vehicles=$this->get_provider_sales_data($request)['vehicles'];
        $vehicles= $vehicles->paginate(config('default_pagination'))->withQueryString();
        $trips=$this->get_provider_sales_data($request)['trips'];

        // custom filtering for bar chart
        $monthly_trip = [];
        $label = [];
        if ($filter != 'custom') {
            switch ($filter) {
                case "all_time":
                    $monthly_trip = Trips::Completed()->when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('provider_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($provider), function ($query) use ($provider) {
                            return $query->where('provider_id', $provider->id);
                        })->select(
                            DB::raw("(sum(trip_amount)) as trip_amount"),
                            DB::raw("(DATE_FORMAT(schedule_at, '%Y')) as year")
                        )
                        ->groupBy(DB::raw("DATE_FORMAT(schedule_at, '%Y')"))
                        ->get()->toArray();

                    $label = array_map(function ($trip) {
                        return $trip['year'];
                    }, $monthly_trip);
                    $data = array_map(function ($trip) {
                        return $trip['trip_amount'];
                    }, $monthly_trip);
                    break;
                case "this_year":
                    for ($i = 1; $i <= 12; $i++) {
                        $monthly_trip[$i] = Trips::Completed()->when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('provider_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($provider), function ($query) use ($provider) {
                                return $query->where('provider_id', $provider->id);
                            })->whereMonth('schedule_at', $i)->whereYear('schedule_at', now()->format('Y'))
                            ->sum('trip_amount');
                    }
                    $label = $months;
                    $data = $monthly_trip;
                    break;
                case "previous_year":
                    for ($i = 1; $i <= 12; $i++) {
                        $monthly_trip[$i] = Trips::Completed()->when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('provider_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($provider), function ($query) use ($provider) {
                                return $query->where('provider_id', $provider->id);
                            })->whereMonth('schedule_at', $i)->whereYear('schedule_at', date('Y') - 1)
                            ->sum('trip_amount');
                    }
                    $label = $months;
                    $data = $monthly_trip;
                    break;
                case "this_week":
                    $weekStartDate = now()->startOfWeek();
                    for ($i = 1; $i <= 7; $i++) {
                        $monthly_trip[$i] = Trips::Completed()->when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('provider_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($provider), function ($query) use ($provider) {
                                return $query->where('provider_id', $provider->id);
                            })->whereDay('schedule_at', $weekStartDate->format('d'))->whereMonth('schedule_at', now()->format('m'))
                            ->sum('trip_amount');
                        $weekStartDate = $weekStartDate->addDays(1);
                    }
                    $label = $days;
                    $data = $monthly_trip;
                    break;
                case "this_month":
                    $start = now()->startOfMonth();
                    $end = now()->startOfMonth()->addDays(6);
                    $total_day = now()->daysInMonth;
                    $remaining_days = now()->daysInMonth - 28;
                    $weeks = array(
                        '"'.translate('Day').' 1-7"',
                        '"'.translate('Day').' 8-14"',
                        '"'.translate('Day').' 15-21"',
                        '"'.translate('Day').' 22-' . $total_day . '"',
                    );
                    for ($i = 1; $i <= 4; $i++) {
                        $monthly_trip[$i] = Trips::Completed()->when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('provider_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($provider), function ($query) use ($provider) {
                                return $query->where('provider_id', $provider->id);
                            })
                            ->whereBetween('schedule_at', ["{$start->format('Y-m-d')} 00:00:00", "{$end->format('Y-m-d')} 23:59:59"])
                            ->sum('trip_amount');
                        $start = $start->addDays(7);
                        $end = $i == 3 ? $end->addDays(7 + $remaining_days) : $end->addDays(7);
                    }
                    $label = $weeks;
                    $data = $monthly_trip;
                    break;
                default:
                    for ($i = 1; $i <= 12; $i++) {
                        $monthly_trip[$i] = Trips::Completed()->when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('provider_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($provider), function ($query) use ($provider) {
                                return $query->where('provider_id', $provider->id);
                            })->whereMonth('schedule_at', $i)->whereYear('schedule_at', now()->format('Y'))
                            ->sum('trip_amount');
                    }
                    $label = $months;
                    $data = $monthly_trip;
            }
        } else {

            $to = Carbon::parse($to);
            $from = Carbon::parse($from);

            $years_count = $to->diffInYears($from);
            $months_count = $to->diffInMonths($from);
            $weeks_count = $to->diffInWeeks($from);
            $days_count = $to->diffInDays($from);


            if ($years_count > 0) {
                $monthly_trip = Trips::Completed()->when(isset($zone), function ($query) use ($zone) {
                    return $query->whereIn('provider_id', $zone->stores->pluck('id'));
                })
                    ->when(isset($provider), function ($query) use ($provider) {
                        return $query->where('provider_id', $provider->id);
                    })
                    ->whereBetween('schedule_at', ["{$from}", "{$to->format('Y-m-d')} 23:59:59"])
                    ->select(
                        DB::raw("(sum(trip_amount)) as trip_amount"),
                        DB::raw("(DATE_FORMAT(schedule_at, '%Y')) as year")
                    )
                    ->groupBy('year')
                    ->get()->toArray();

                $label = array_map(function ($trip) {
                    return $trip['year'];
                }, $monthly_trip);
                $data = array_map(function ($trip) {
                    return $trip['trip_amount'];
                }, $monthly_trip);
            } elseif ($months_count > 0) {
                for ($i = (int)$from->format('m'); $i <= (int)$from->format('m') + $months_count; $i++) {
                    $monthly_trip[$i] = Trips::Completed()->when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('provider_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($provider), function ($query) use ($provider) {
                            return $query->where('provider_id', $provider->id);
                        })->whereMonth('schedule_at', $i)
                        ->sum('trip_amount');
                    $label[$i] = $months[$i - 1];
                }
                $label = $label;
                $data = $monthly_trip;
            } elseif ($weeks_count > 0) {
                for ($i = (int)$from->format('d'); $i <= (int)$to->format('d'); $i++) {
                    $monthly_trip[$i] = Trips::Completed()->when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('provider_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($provider), function ($query) use ($provider) {
                            return $query->where('provider_id', $provider->id);
                        })->whereDay('schedule_at', $i)->whereMonth('schedule_at', $from->format('m'))->whereYear('schedule_at', $from->format('Y'))
                        ->sum('trip_amount');
                    $label[$i] = $i;
                }
                $label = $label;
                $data = $monthly_trip;
            } elseif ($days_count >= 0) {
                for ($i = (int)$from->format('d'); $i <= (int)$to->format('d'); $i++) {
                    $monthly_trip[$i] = Trips::Completed()->when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('provider_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($provider), function ($query) use ($provider) {
                            return $query->where('provider_id', $provider->id);
                        })->whereDay('schedule_at', $i)->whereMonth('schedule_at', $from->format('m'))->whereYear('schedule_at', $from->format('Y'))
                        ->sum('trip_amount');
                    $label[$i] = $i;
                }
                $label = $label;
                $data = $monthly_trip;
            }
        }

        return view('rental::admin.report.provider-sales-report', compact('zone', 'provider', 'vehicles', 'trips', 'data', 'label', 'filter'));
    }
    public function providerSalesExport(Request $request)
    {
        $from = session('from_date');
        $to = session('to_date');
        $filter = $request->query('filter', 'all_time');

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $provider_id = $request->query('provider_id', 'all');


        $vehicles=$this->get_provider_sales_data($request)['vehicles'];
        $vehicles= $vehicles->get();
        $trips=$this->get_provider_sales_data($request)['trips'];

        $data = [
            'vehicles'=>$vehicles,
            'trips'=>$trips,
            'search'=>$request->search??null,
            'from'=>(($filter == 'custom') && $from)?$from:null,
            'to'=>(($filter == 'custom') && $to)?$to:null,
            'zone'=>is_numeric($zone_id)?Helpers::get_zones_name($zone_id):null,
            'provider'=>is_numeric($provider_id)?Helpers::get_stores_name($provider_id):null,
            'filter'=>$filter,
        ];
        if ($request->type == 'excel') {
            return Excel::download(new ProviderSalesReportExport($data), 'ProviderVehicleReport.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new ProviderSalesReportExport($data), 'ProviderVehicleReport.csv');
        }
    }
    private static function get_provider_sales_data($request){
        if (session()->has('from_date') == false) {
            session()->put('from_date', now()->firstOfMonth()->format('Y-m-d'));
            session()->put('to_date', now()->lastOfMonth()->format('Y-m-d'));
        }
        $from = session('from_date');
        $to = session('to_date');
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];
        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $provider_id = $request->query('provider_id', 'all');
        $filter = $request->query('filter', 'all_time');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $provider = is_numeric($provider_id) ? Store::findOrFail($provider_id) : null;

        $vehicles =Vehicle::withCount([
            'tripDetails as trips_count' => function ($query) use ($from, $to, $filter) {
                $query->whereHas('trip', function ($query) {
                    return $query->whereIn('trip_status', ['completed']);
                })->applyDateFilter($filter, $from, $to);
            }, 'vehicleIdentities'
        ] )
            ->withSum([
                'tripDetails' => function ($query) use ($from, $to, $filter) {
                    $query->whereHas('trip', function ($query) {
                        return $query->whereIn('trip_status', ['completed']);
                    })->applyDateFilter($filter, $from, $to);
                },
            ], 'quantity')

            ->addSelect([
                'total_discount' => function ($query) use ($from, $to, $filter) {
                    $query->selectRaw('SUM(trip_details.discount_on_trip * trip_details.quantity)')
                        ->from('trip_details')
                        ->join('trips', 'trips.id', '=', 'trip_details.trip_id')
                        ->whereColumn('trip_details.vehicle_id', 'vehicles.id')
                        ->whereIn('trips.trip_status', ['completed'])
                        ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                            return $query->whereBetween('trip_details.created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
                        })
                        ->when(isset($filter) && $filter == 'this_year', function ($query) {
                            return $query->whereYear('trip_details.created_at', now()->format('Y'));
                        })
                        ->when(isset($filter) && $filter == 'this_month', function ($query) {
                            return $query->whereMonth('trip_details.created_at', now()->format('m'))
                                ->whereYear('trip_details.created_at', now()->format('Y'));
                        })
                        ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                            return $query->whereYear('trip_details.created_at', date('Y') - 1);
                        })
                        ->when(isset($filter) && $filter == 'this_week', function ($query) {
                            return $query->whereBetween('trip_details.created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]); // Filter by this week
                        });
                },
                'trips_sum_price' => function ($query) use ($from, $to, $filter) {
                    $query->selectRaw('SUM(trip_details.price * trip_details.quantity)')
                        ->from('trip_details')
                        ->join('trips', 'trips.id', '=', 'trip_details.trip_id')
                        ->whereColumn('trip_details.vehicle_id', 'vehicles.id')
                        ->whereIn('trips.trip_status', ['completed'])
                        ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                            return $query->whereBetween('trip_details.created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
                        })
                        ->when(isset($filter) && $filter == 'this_year', function ($query) {
                            return $query->whereYear('trip_details.created_at', now()->format('Y'));
                        })
                        ->when(isset($filter) && $filter == 'this_month', function ($query) {
                            return $query->whereMonth('trip_details.created_at', now()->format('m'))
                                ->whereYear('trip_details.created_at', now()->format('Y'));
                        })
                        ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                            return $query->whereYear('trip_details.created_at', date('Y') - 1);
                        })
                        ->when(isset($filter) && $filter == 'this_week', function ($query) {
                            return $query->whereBetween('trip_details.created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]); // Filter by this week
                        });
                },
            ])
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('provider_id', $zone->stores->pluck('id'));
            })
            ->when(isset($provider), function ($query) use ($provider) {
                return $query->where('provider_id', $provider->id);
            })
            ->when(isset($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                });
            })
            ->having('trips_count', '>' ,0)
            ->orderBy('trips_count', 'desc');

        $trips = Trips::whereNotIn('trip_status', ['failed', 'canceled'])
            ->Completed()->with('trip_transaction')->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('provider_id', $zone->stores->pluck('id'));
            })
            ->when(isset($provider), function ($query) use ($provider) {
                return $query->where('provider_id', $provider->id);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('schedule_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('schedule_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->withSum('trip_transaction', 'admin_commission')
            ->withSum('trip_transaction', 'admin_expense')
            ->withSum('trip_transaction', 'store_amount')
            ->get();

        return ['vehicles'=> $vehicles , 'trips'=> $trips];
    }
    public function providerTripReport(Request $request)
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', now()->firstOfMonth()->format('Y-m-d'));
            session()->put('to_date', now()->lastOfMonth()->format('Y-m-d'));
        }
        $from = session('from_date');
        $to = session('to_date');

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
            '"'.translate('Sun').'"',
            '"'.translate('Mon').'"',
            '"'.translate('Tue').'"',
            '"'.translate('Wed').'"',
            '"'.translate('Thu').'"',
            '"'.translate('Fri').'"',
            '"'.translate('Sat').'"'
        );

        $key = isset($request['search']) ? explode(' ', $request['search']) : [];

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $provider_id = $request->query('provider_id', 'all');
        $filter = $request->query('filter', 'all_time');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $provider = is_numeric($provider_id) ? Store::findOrFail($provider_id) : null;

        // trip list with pagination
        $trips = Trips::with(['customer', 'provider'])
            ->when(isset($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('id', 'like', "%{$value}%");
                    }
                });
            })
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('provider_id', $zone->stores->pluck('id'));
            })
            ->when(isset($provider), function ($query) use ($provider) {
                return $query->where('provider_id', $provider->id);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('schedule_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('schedule_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->withSum('trip_transaction', 'admin_commission')
            ->withSum('trip_transaction', 'admin_expense')
            ->orderBy('schedule_at', 'desc')->paginate(config('default_pagination'));

        // trip card values calculation
        $trips_list = Trips::with(['customer', 'provider'])
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('provider_id', $zone->stores->pluck('id'));
            })
            ->when(isset($provider), function ($query) use ($provider) {
                return $query->where('provider_id', $provider->id);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('schedule_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('schedule_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->withSum('trip_transaction', 'admin_commission')
            ->withSum('trip_transaction', 'admin_expense')
            ->orderBy('schedule_at', 'desc')->get();

        $total_trip_amount = $trips_list->sum('trip_amount');
        $total_coupon_discount = $trips_list->sum('coupon_discount_amount');
        $total_product_discount = $trips_list->sum('discount_on_trip');

        $total_ongoing = $trips_list->whereIn('trip_status', ['pending', 'accepted', 'confirmed', 'processing', 'ongoing'])->sum('trip_amount');
        $total_canceled = $trips_list->whereIn('trip_status', ['failed', 'canceled'])->sum('trip_amount');
        $total_completed = $trips_list->where('trip_status', 'completed')->sum('trip_amount');
        $total_ongoing_count = $trips_list->whereIn('trip_status', ['pending', 'accepted', 'confirmed', 'processing', 'ongoing'])->count();
        $total_canceled_count = $trips_list->whereIn('trip_status', ['failed', 'canceled'])->count();
        $total_completed_count = $trips_list->where('trip_status', 'completed')->count();

        // payment type statistics
        $trip_payment_methods = Trips::when(isset($zone), function ($query) use ($zone) {
            return $query->whereIn('provider_id', $zone->stores->pluck('id'));
        })
            ->when(isset($provider), function ($query) use ($provider) {
                return $query->where('provider_id', $provider->id);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('schedule_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('schedule_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->selectRaw(DB::raw("sum(`trip_amount`) as total_trip_amount, count(*) as trip_count, IF((`payment_method`='cash_payment'), `payment_method`, IF(`payment_method`='wallet',`payment_method`, 'digital_payment')) as 'payment_methods'"))
            ->groupBy('payment_methods')
            ->get();

        // custom filtering for bar chart
        $monthly_trip = [];
        $label = [];
        if ($filter != 'custom') {
            switch ($filter) {
                case "all_time":
                    $monthly_trip = Trips::when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('provider_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($provider), function ($query) use ($provider) {
                            return $query->where('provider_id', $provider->id);
                        })

                        ->select(
                            DB::raw("(sum(trip_amount)) as trip_amount"),
                            DB::raw("(DATE_FORMAT(schedule_at, '%Y')) as year")
                        )
                        ->groupBy(DB::raw("DATE_FORMAT(schedule_at, '%Y')"))
                        ->get()->toArray();

                    $label = array_map(function ($trip) {
                        return $trip['year'];
                    }, $monthly_trip);
                    $data = array_map(function ($trip) {
                        return $trip['trip_amount'];
                    }, $monthly_trip);
                    break;
                case "this_year":
                    for ($i = 1; $i <= 12; $i++) {
                        $monthly_trip[$i] = Trips::when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('provider_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($provider), function ($query) use ($provider) {
                                return $query->where('provider_id', $provider->id);
                            })

                            ->whereMonth('schedule_at', $i)->whereYear('schedule_at', now()->format('Y'))
                            ->sum('trip_amount');
                    }
                    $label = $months;
                    $data = $monthly_trip;
                    break;
                case "previous_year":
                    for ($i = 1; $i <= 12; $i++) {
                        $monthly_trip[$i] = Trips::when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('provider_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($provider), function ($query) use ($provider) {
                                return $query->where('provider_id', $provider->id);
                            })

                            ->whereMonth('schedule_at', $i)->whereYear('schedule_at', date('Y') - 1)
                            ->sum('trip_amount');
                    }
                    $label = $months;
                    $data = $monthly_trip;
                    break;
                case "this_week":
                    $weekStartDate = now()->startOfWeek();
                    for ($i = 1; $i <= 7; $i++) {
                        $monthly_trip[$i] = Trips::when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('provider_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($provider), function ($query) use ($provider) {
                                return $query->where('provider_id', $provider->id);
                            })->whereDay('schedule_at', $weekStartDate->format('d'))->whereMonth('schedule_at', now()->format('m'))
                            ->sum('trip_amount');
                        $weekStartDate = $weekStartDate->addDays(1);
                    }
                    $label = $days;
                    $data = $monthly_trip;
                    break;
                case "this_month":
                    $start = now()->startOfMonth();
                    $end = now()->startOfMonth()->addDays(7);
                    $total_day = now()->daysInMonth;
                    $remaining_days = now()->daysInMonth - 28;
                    $weeks = array(
                        '"'.translate('Day').' 1-7"',
                        '"'.translate('Day').' 8-14"',
                        '"'.translate('Day').' 15-21"',
                        '"'.translate('Day').' 22-' . $total_day . '"',
                    );
                    for ($i = 1; $i <= 4; $i++) {
                        $monthly_trip[$i] = Trips::when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('provider_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($provider), function ($query) use ($provider) {
                                return $query->where('provider_id', $provider->id);
                            })

                            ->whereBetween('schedule_at', ["{$start->format('Y-m-d')} 00:00:00", "{$end->format('Y-m-d')} 23:59:59"])
                            ->sum('trip_amount');
                        $start = $start->addDays(7);
                        $end = $i == 3 ? $end->addDays(7 + $remaining_days) : $end->addDays(7);
                    }
                    $label = $weeks;
                    $data = $monthly_trip;
                    break;
                default:
                    for ($i = 1; $i <= 12; $i++) {
                        $monthly_trip[$i] = Trips::when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('provider_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($provider), function ($query) use ($provider) {
                                return $query->where('provider_id', $provider->id);
                            })->whereMonth('schedule_at', $i)->whereYear('schedule_at', now()->format('Y'))
                            ->sum('trip_amount');
                    }
                    $label = $months;
                    $data = $monthly_trip;
            }
        } else {

            $to = Carbon::parse($to);
            $from = Carbon::parse($from);

            $years_count = $to->diffInYears($from);
            $months_count = $to->diffInMonths($from);
            $weeks_count = $to->diffInWeeks($from);
            $days_count = $to->diffInDays($from);

            // dd($days_count);


            if ($years_count > 0) {
                $monthly_trip = Trips::when(isset($zone), function ($query) use ($zone) {
                    return $query->whereIn('provider_id', $zone->stores->pluck('id'));
                })
                    ->when(isset($provider), function ($query) use ($provider) {
                        return $query->where('provider_id', $provider->id);
                    })

                    ->whereBetween('schedule_at', ["{$from}", "{$to->format('Y-m-d')} 23:59:59"])
                    ->select(
                        DB::raw("(sum(trip_amount)) as trip_amount"),
                        DB::raw("(DATE_FORMAT(schedule_at, '%Y')) as year")
                    )
                    ->groupBy('year')
                    ->get()->toArray();

                $label = array_map(function ($trip) {
                    return $trip['year'];
                }, $monthly_trip);
                $data = array_map(function ($trip) {
                    return $trip['trip_amount'];
                }, $monthly_trip);
            } elseif ($months_count > 0) {
                for ($i = (int)$from->format('m'); $i <= (int)$from->format('m') + $months_count; $i++) {
                    $monthly_trip[$i] = Trips::when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('provider_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($provider), function ($query) use ($provider) {
                            return $query->where('provider_id', $provider->id);
                        })
                        ->whereMonth('schedule_at', $i)
                        ->sum('trip_amount');
                    $label[$i] = $months[$i - 1];
                }
                $label = $label;
                $data = $monthly_trip;
            } elseif ($weeks_count > 0) {
                // $start = $from;
                // $end = $from->addDays(7);
                // $weeks = [];
                // for ($i = 1; $i <= 4; $i++) {
                //     $weeks[$i] = '"'.translate('Day').' ' . (int)$start->format('d') . '-' . ((int)$start->format('d') + 7) . '"';
                //     $monthly_trip[$i] = Trips::when(isset($zone), function ($query) use ($zone) {
                //         return $query->whereIn('provider_id', $zone->stores->pluck('id'));
                //     })
                //         ->when(isset($provider), function ($query) use ($provider) {
                //             return $query->where('provider_id', $provider->id);
                //         })
                //         ->whereBetween('schedule_at', [$start, "{$end->format('Y-m-d')} 23:59:59"])
                //         ->sum('trip_amount');

                //     $start = $end;
                //     $end = $start->addDays(7);
                // }
                // $label = $weeks;
                // $data = $monthly_trip;
                for ($i = (int)$from->format('d'); $i <= (int)$to->format('d'); $i++) {
                    $monthly_trip[$i] = Trips::when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('provider_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($provider), function ($query) use ($provider) {
                            return $query->where('provider_id', $provider->id);
                        })
                        ->whereDay('schedule_at', $i)->whereMonth('schedule_at', $from->format('m'))->whereYear('schedule_at', $from->format('Y'))
                        ->sum('trip_amount');
                    $label[$i] = $i;
                }
                $label = $label;
                $data = $monthly_trip;
            } elseif ($days_count >= 0) {
                for ($i = (int)$from->format('d'); $i <= (int)$to->format('d'); $i++) {
                    $monthly_trip[$i] = Trips::when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('provider_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($provider), function ($query) use ($provider) {
                            return $query->where('provider_id', $provider->id);
                        })
                        ->whereDay('schedule_at', $i)->whereMonth('schedule_at', $from->format('m'))->whereYear('schedule_at', $from->format('Y'))
                        ->sum('trip_amount');
                    $label[$i] = $i;
                }
                $label = $label;
                $data = $monthly_trip;
            }
        }


        return view('rental::admin.report.provider-trip-report', compact('zone', 'provider', 'trips', 'trips_list', 'monthly_trip', 'total_trip_amount', 'trip_payment_methods', 'total_coupon_discount', 'total_product_discount', 'label', 'data', 'filter', 'total_ongoing', 'total_canceled', 'total_completed', 'total_ongoing_count', 'total_canceled_count', 'total_completed_count'));
    }
    public function providerTripExport(Request $request)
    {
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];

        $from = session('from_date');
        $to = session('to_date');

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $provider_id = $request->query('provider_id', 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $provider = is_numeric($provider_id) ? Store::findOrFail($provider_id) : null;
        $filter = $request->query('filter', 'all_time');

        // trip list
        $trips = Trips::with(['customer', 'provider'])
            ->when(isset($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('id', 'like', "%{$value}%");
                    }
                });
            })
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('provider_id', $zone->stores->pluck('id'));
            })
            ->when(isset($provider), function ($query) use ($provider) {
                return $query->where('provider_id', $provider->id);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('schedule_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('schedule_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->withSum('trip_transaction', 'admin_commission')
            ->withSum('trip_transaction', 'admin_expense')
            ->orderBy('schedule_at', 'desc')->get();

        // trip card values calculation
        $trips_list = Trips::with(['customer', 'provider'])
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('provider_id', $zone->stores->pluck('id'));
            })
            ->when(isset($provider), function ($query) use ($provider) {
                return $query->where('provider_id', $provider->id);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('schedule_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('schedule_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->withSum('trip_transaction', 'admin_commission')
            ->withSum('trip_transaction', 'admin_expense')
            ->orderBy('schedule_at', 'desc')->get();

        $total_trip_amount = $trips_list->sum('trip_amount');
        $total_ongoing_count = $trips_list->whereIn('trip_status', ['pending', 'accepted', 'confirmed', 'processing', 'ongoing'])->count();
        $total_canceled_count = $trips_list->whereIn('trip_status', ['failed', 'canceled'])->count();
        $total_completed_count = $trips_list->where('trip_status', 'completed')->count();


        $data = [
            'trips'=>$trips,
            'total_trips'=>$trips->count(),
            'total_trip_amount'=>$total_trip_amount,
            'total_ongoing_count'=>$total_ongoing_count,
            'total_canceled_count'=>$total_canceled_count,
            'total_completed_count'=>$total_completed_count,
            'search'=>$request->search??null,
            'from'=>(($filter == 'custom') && $from)?$from:null,
            'to'=>(($filter == 'custom') && $to)?$to:null,
            'zone'=>is_numeric($zone_id)?Helpers::get_zones_name($zone_id):null,
            'provider'=>is_numeric($provider_id)?Helpers::get_stores_name($provider_id):null,
            'filter'=>$filter,
        ];
        if ($request->type == 'excel') {
            return Excel::download(new ProviderTripReportExport($data), 'ProviderTripReport.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new ProviderTripReportExport($data), 'ProviderTripReport.csv');
        }
    }
    public function generateStatement($id)
    {
        $company_phone = BusinessSetting::where('key', 'phone')->first()->value;
        $company_email = BusinessSetting::where('key', 'email_address')->first()->value;
        $company_name = BusinessSetting::where('key', 'business_name')->first()->value;
        $company_web_logo = BusinessSetting::where('key', 'logo')->first()->value;
        $footer_text =BusinessSetting::where(['key' => 'footer_text'])->first()->value;

        $trip_transaction = TripTransaction::with('trip', 'trip.trip_details', 'trip.customer', 'trip.provider')->where('id', $id)->first();
        $data["email"] = $trip_transaction->trip->customer != null ? $trip_transaction->trip->customer["email"] : translate('email_not_found');
        $data["client_name"] = $trip_transaction->trip->customer != null ? $trip_transaction->trip->customer["f_name"] . ' ' . $trip_transaction->trip->customer["l_name"] : translate('customer_not_found');
        $data["trip_transaction"] = $trip_transaction;
        $mpdf_view = View::make(
            'rental::admin.report.trip-transaction-statement',
            compact('trip_transaction', 'company_phone', 'company_name', 'company_email', 'company_web_logo', 'footer_text')
        );
        Helpers::gen_mpdf($mpdf_view, 'trip_trans_statement', $trip_transaction->id);
    }

    public function tripInvoice($id)
    {
        $id = base64_decode($id);
        $BusinessData = ['footer_text', 'email_address'];
        $trip = Trips::findOrFail($id);
        $BusinessData = BusinessSetting::whereIn('key', $BusinessData)->pluck('value', 'key');
        $logo = BusinessSetting::where('key', "logo")->first();
        // return view('rental::admin.trip-invoice', compact('trip', 'BusinessData', 'logo'));
        $mpdf_view = View::make('email-templates.pdf-rental.trip-invoice', compact('trip', 'BusinessData', 'logo'));
        Helpers::gen_mpdf(view: $mpdf_view, file_prefix: 'TripInvoice', file_postfix: $id);
        return back();
    }

}

