<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\OrderTransaction;
use App\Models\SubscriptionTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ReportGeneratorTrait;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AdminEarningTransactionExport;
use App\Exports\DeliverymanEarningTransactionExport;

class AdminEarningReportController extends Controller
{
    use ReportGeneratorTrait;

    private function resolveModuleId(Request $request): string
    {
        return $request->query('module_id', 'all');
    }

    private function hasModuleFilter($module_id): bool
    {
        return !in_array($module_id, [null, '', 'all'], true);
    }

    private function shouldIncludeSubscription(array $order_types): bool
    {
        return !in_array('parcel', $order_types, true);
    }

    public function getAdminEarningReport(Request $request)
    {
        return view('admin-views.report.admin-earning-report');
    }

    public function getAdminEarningSummary(Request $request)
    {
        [$filter, $from, $to] = $this->resolveDateFilter($request);
        $module_id = $this->resolveModuleId($request);
        $order_types = $this->normalizeOrderTypes($request->query('order_types', $request->query('order_type', ['take_away', 'delivery'])));
        $include_subscription = $this->shouldIncludeSubscription($order_types);

        $summary = $this->buildAdminEarningSummary(
            filter: $filter,
            from: $from,
            to: $to,
            module_id: $module_id,
            order_types: $order_types,
            include_subscription: $include_subscription
        );
        $html = view('admin-views.report.partials._admin-earning-summary', compact('summary'))->render();
        return response()->json(['view' => $html]);
    }

    public function getAdminEarningBreakdown(Request $request)
    {
        [$filter, $from, $to] = $this->resolveDateFilter($request);
        $module_id = $this->resolveModuleId($request);
        $order_types = $this->normalizeOrderTypes($request->query('order_types', $request->query('order_type', ['take_away', 'delivery'])));
        $include_subscription = $this->shouldIncludeSubscription($order_types);

        $summary = $this->buildAdminEarningSummary(
            filter: $filter,
            from: $from,
            to: $to,
            module_id: $module_id,
            order_types: $order_types,
            include_subscription: $include_subscription
        );
        $earnings = $this->buildEarningBreakdown(
            filter: $filter,
            from: $from,
            to: $to,
            admin_earning: $summary['admin_earning'],
            module_id: $module_id,
            order_types: $order_types
        );

        $earnings['subscription_earning'] = $include_subscription ? $summary['subscription_earning'] : 0;
        $earnings['subscription_percentage'] = $include_subscription ? $summary['subscription_percentage'] : 0;


        $html = view('admin-views.report.partials._admin-earning-breakdown', compact('earnings', 'include_subscription'))->render();
        return response()->json(['view' => $html, 'earnings' => $earnings]);
    }

    public function getAdminExpenseBreakdown(Request $request)
    {
        [$filter, $from, $to] = $this->resolveDateFilter($request);
        $module_id = $this->resolveModuleId($request);
        $order_types = $this->normalizeOrderTypes($request->query('order_types', $request->query('order_type', ['take_away', 'delivery'])));
        $include_subscription = $this->shouldIncludeSubscription($order_types);

        // require admin expense total for percentage calculations
        $summary = $this->buildAdminEarningSummary(
            filter: $filter,
            from: $from,
            to: $to,
            module_id: $module_id,
            order_types: $order_types,
            include_subscription: $include_subscription
        );
        $expenses = $this->buildExpenseBreakdown(
            filter: $filter,
            from: $from,
            to: $to,
            admin_expense: $summary['admin_expense'],
            module_id: $module_id,
            order_types: $order_types
        );

        $html = view('admin-views.report.partials._admin-expense-breakdown', compact('expenses', 'include_subscription'))->render();
        return response()->json(['view' => $html]);
    }


    public function getMonthlyEarningsReport(Request $request)
    {
        [$filter, $from, $to] = $this->resolveDateFilter($request);
        $module_id = $this->resolveModuleId($request);
        $order_types = $this->normalizeOrderTypes($request->query('order_types', $request->query('order_type', ['take_away', 'delivery'])));
        $include_subscription = $this->shouldIncludeSubscription($order_types);

        $today = Carbon::now();
        $months = collect();
        $dateFormat = ($filter === 'this_week' || $filter === 'this_month') ? '%Y-%m-%d' : '%Y-%m';
        $singleDayCustom = $filter === 'custom' && $from && $to && $from === $to;

        if ($filter === 'this_year') {
            $startMonth = Carbon::now()->startOfYear();
            for ($i = 0; $i <= $today->month - 1; $i++) {
                $months->push($startMonth->copy()->addMonths($i)->format('Y-m'));
            }

        } elseif ($filter === 'this_month') {
            $daysInMonth = Carbon::now()->daysInMonth;
            $startOfMonth = Carbon::now()->startOfMonth();
            for ($i = 0; $i < $daysInMonth; $i++) {
                $months->push($startOfMonth->copy()->addDays($i)->format('Y-m-d'));
            }

        } elseif ($filter === 'this_week') {
            $startOfWeek = Carbon::now()->startOfWeek();
            for ($i = 0; $i <= 6; $i++) {
                $months->push($startOfWeek->copy()->addDays($i)->format('Y-m-d'));
            }

        } elseif ($filter === 'previous_year') {
            $startMonth = Carbon::now()->subYear()->startOfYear();
            for ($i = 0; $i < 12; $i++) {
                $months->push($startMonth->copy()->addMonths($i)->format('Y-m'));
            }
        } elseif ($filter === 'custom' && $from && $to) {
            $start = Carbon::parse($from)->startOfDay();
            $end = Carbon::parse($to)->endOfDay();
            $diffDays = $start->diffInDays($end);

            if ($diffDays > 365) {
                $dateFormat = '%Y';
                $temp = $start->copy()->startOfYear();
                while ($temp->year <= $end->year) {
                    $months->push($temp->format('Y'));
                    $temp->addYear();
                }
            } elseif ($diffDays > 31) {
                $dateFormat = '%Y-%m';
                $temp = $start->copy()->startOfMonth();
                // Ensure to cover the full range of months
                while ($temp->format('Y-m') <= $end->format('Y-m')) {
                    $months->push($temp->format('Y-m'));
                    $temp->addMonth();
                }
            } else {
                $dateFormat = '%Y-%m-%d';
                $temp = $start->copy();
                while ($temp->lte($end)) {
                    $months->push($temp->format('Y-m-d'));
                    $temp->addDay();
                }
            }

        } else {
            for ($i = 11; $i >= 0; $i--) {
                $months->push($today->copy()->subMonths($i)->format('Y-m'));
            }
        }

        $baseTransactionQuery = OrderTransaction::query()->whereNull('status')
            ->join('orders', 'orders.id', '=', 'order_transactions.order_id')
            ->applyDateFilter($filter, $from, $to, 'order_transactions.created_at');
        $baseTransactionQuery = $this->applyModuleOrOrderTypeFilter(
            query: $baseTransactionQuery,
            module_id: $module_id,
            order_types: $order_types,
            moduleColumn: 'order_transactions.module_id',
            orderTypeColumn: 'orders.order_type'
        );

        $earningFormula = $this->getAdminTotalEarningQuery();

        $earnings = $baseTransactionQuery
            ->selectRaw("DATE_FORMAT(order_transactions.created_at, '$dateFormat') as month")
            ->selectRaw("$earningFormula as total_earning")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total_earning', 'month');

        // subscriptions
        $subscriptionQuery = collect();
        if ($include_subscription) {
            $subscriptionBaseQuery = SubscriptionTransaction::where('is_trial', 0)
                ->where('payment_status', 'success')
                ->applyDateFilter($filter, $from, $to, 'subscription_transactions.created_at');
            if ($this->hasModuleFilter($module_id)) {
                $subscriptionBaseQuery->whereHas('store', function ($q) use ($module_id) {
                    $q->where('module_id', $module_id);
                });
            }

            $subscriptionQuery = $subscriptionBaseQuery
                ->selectRaw("DATE_FORMAT(subscription_transactions.created_at, '$dateFormat') as month")
                ->selectRaw("SUM(paid_amount) as total_sub_earning")
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total_sub_earning', 'month');
        }


        $expenseBaseQuery = Expense::where('created_by', 'admin')->whereNull('trip_id');
        $expenseBaseQuery = $this->moduleAndOrderTypeFilter(
            query: $expenseBaseQuery,
            module_id: $module_id,
            order_types: $order_types,
            keepStandaloneForModule: false,
            keepStandaloneForOrderType: true
        );

        $expenses = $expenseBaseQuery
            ->applyDateFilter($filter, $from, $to, 'expenses.created_at')
            ->selectRaw("DATE_FORMAT(expenses.created_at, '$dateFormat') as month")
            ->selectRaw("SUM(amount) as total_expense")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total_expense', 'month');



        $earningSeries = $months->map(function ($m) use ($earnings, $subscriptionQuery, $include_subscription) {
                $orderEarning = $earnings[$m] ?? 0;
                $subscriptionEarning = $include_subscription ? ($subscriptionQuery[$m] ?? 0) : 0;
                return round($orderEarning + $subscriptionEarning, 2);
            });
        $expenseSeries = $months->map(fn($m) => round($expenses[$m] ?? 0, 2));


        return response()->json([
            'categories' => $months->map(function ($m) use ($filter, $dateFormat, $singleDayCustom) {
                if ($filter === 'this_week') {
                    return Carbon::parse($m)->format('D');
                }
                if ($filter === 'this_month') {
                    return Carbon::parse($m)->format('j');
                }
                if ($filter === 'custom') {
                    if ($singleDayCustom) {
                        return Carbon::parse($m)->format('d M Y');
                    }
                    if ($dateFormat === '%Y') return $m;
                    if ($dateFormat === '%Y-%m') return Carbon::parse($m . '-01')->format('M');
                    if ($dateFormat === '%Y-%m-%d') return Carbon::parse($m)->format('j');
                }
                return Carbon::parse($m . '-01')->format('M');
            }),
            'earning_series' => $earningSeries,
            'expense_series' => $expenseSeries
        ]);
    }


    public function getZoneWiseEarnings(Request $request){

        [$filter, $from, $to] = $this->resolveDateFilter($request);
        $module_id = $this->resolveModuleId($request);
        $order_types = $this->normalizeOrderTypes($request->query('order_types', $request->query('order_type', ['take_away', 'delivery'])));
        $include_subscription = $this->shouldIncludeSubscription($order_types);

        $earningFormula =$this->getAdminTotalEarningQuery();


        $orderEarningsPerZone = OrderTransaction::query()
            ->whereNull('order_transactions.status')
            ->join('orders', 'orders.id', '=', 'order_transactions.order_id')
            ->applyDateFilter($filter, $from, $to, 'order_transactions.created_at');
        $orderEarningsPerZone = $this->applyModuleOrOrderTypeFilter(
            query: $orderEarningsPerZone,
            module_id: $module_id,
            order_types: $order_types,
            moduleColumn: 'order_transactions.module_id',
            orderTypeColumn: 'orders.order_type'
        )
            ->whereNotNull('orders.zone_id')
            ->when($filter === 'custom' && $from && $to, function ($query) use ($from, $to) {
                $query->whereBetween('order_transactions.created_at', ["$from 00:00:00", "$to 23:59:59"]);
            })
            ->when($filter === 'this_year', function ($query) {
                $query->whereYear('order_transactions.created_at', now()->year);
            })
            ->when($filter === 'this_month', function ($query) {
                $query->whereYear('order_transactions.created_at', now()->year)
                    ->whereMonth('order_transactions.created_at', now()->month);
            })
            ->when($filter === 'previous_year', function ($query) {
                $query->whereYear('order_transactions.created_at', now()->year - 1);
            })
            ->when($filter === 'this_week', function ($query) {
                $query->whereBetween('order_transactions.created_at', [
                    now()->startOfWeek()->format('Y-m-d H:i:s'),
                    now()->endOfWeek()->format('Y-m-d H:i:s')
                ]);
            })
            ->select('orders.zone_id')
            ->selectRaw("COALESCE($earningFormula, 0) as admin_earning")
            ->selectRaw("COUNT(order_transactions.id) as total_transactions")
            ->groupBy('orders.zone_id');


        $subscriptionEarningsPerZone = null;
        if ($include_subscription) {
            $subscriptionEarningsPerZone = DB::table('subscription_transactions as sub')
                ->join('stores', 'stores.id', '=', 'sub.store_id')
                ->where('sub.payment_status', 'success')
                ->where('sub.is_trial', 0)
                ->when($this->hasModuleFilter($module_id), function ($query) use ($module_id) {
                    $query->where('stores.module_id', $module_id);
                })
                ->when($filter === 'custom' && $from && $to, function ($query) use ($from, $to) {
                    $query->whereBetween('sub.created_at', ["$from 00:00:00", "$to 23:59:59"]);
                })
                ->when($filter === 'this_year', function ($query) {
                    $query->whereYear('sub.created_at', now()->year);
                })
                ->when($filter === 'this_month', function ($query) {
                    $query->whereYear('sub.created_at', now()->year)
                        ->whereMonth('sub.created_at', now()->month);
                })
                ->when($filter === 'previous_year', function ($query) {
                    $query->whereYear('sub.created_at', now()->year - 1);
                })
                ->when($filter === 'this_week', function ($query) {
                    $query->whereBetween('sub.created_at', [
                        now()->startOfWeek()->format('Y-m-d H:i:s'),
                        now()->endOfWeek()->format('Y-m-d H:i:s')
                    ]);
                })
                ->select('stores.zone_id')
                ->selectRaw("COALESCE(SUM(sub.paid_amount), 0) as subscription_earning")
                ->groupBy('stores.zone_id');
        }


        $isParcel = in_array('parcel', $order_types, true);

        $topZones = DB::table('zones')
            ->leftJoinSub($orderEarningsPerZone, 'oe', 'zones.id', '=', 'oe.zone_id')
            ->select('zones.id', 'zones.name as zone_name')
            ->selectRaw("COALESCE(oe.admin_earning, 0) as admin_earning")
            ->when($include_subscription, function ($query) use ($subscriptionEarningsPerZone) {
                $query->leftJoinSub($subscriptionEarningsPerZone, 'se', 'zones.id', '=', 'se.zone_id')
                    ->selectRaw("COALESCE(se.subscription_earning, 0) as subscription_earning")
                    ->selectRaw("COALESCE(oe.admin_earning, 0) + COALESCE(se.subscription_earning, 0) as total_earning");
            }, function ($query) {
                $query->selectRaw("0 as subscription_earning")
                    ->selectRaw("COALESCE(oe.admin_earning, 0) as total_earning");
            })
            ->selectRaw("COALESCE(oe.total_transactions, 0) as total_transactions")
            ->selectRaw(
                $isParcel
                    ? 'COALESCE(oe.total_transactions, 0) as total_order_count'
                    : (
                        $this->hasModuleFilter($module_id)
                            ? '(SELECT COUNT(DISTINCT id) FROM stores WHERE zone_id = zones.id AND module_id = ' . (int) $module_id . ') as total_stores'
                            : '(SELECT COUNT(DISTINCT id) FROM stores WHERE zone_id = zones.id) as total_stores'
                    )
            )
            ->havingRaw("total_earning > 0")
            ->orderByDesc('total_earning')
            ->limit(10)
            ->get();

        $totalEarningsAllZones = $topZones->sum('total_earning') > 0
            ? DB::table('zones')
                ->leftJoinSub($orderEarningsPerZone, 'oe', 'zones.id', '=', 'oe.zone_id')
                ->when($include_subscription, function ($query) use ($subscriptionEarningsPerZone) {
                    return $query->leftJoinSub($subscriptionEarningsPerZone, 'se', 'zones.id', '=', 'se.zone_id')
                        ->selectRaw("COALESCE(SUM(oe.admin_earning), 0) + COALESCE(SUM(se.subscription_earning), 0) as grand_total");
                }, function ($query) {
                    return $query->selectRaw("COALESCE(SUM(oe.admin_earning), 0) as grand_total");
                })
                ->value('grand_total')
            : 0;

        $topZones = $topZones->map(function($zone) use ($totalEarningsAllZones) {
            return [
                'zone_name'             => $zone->zone_name,
                'total_stores'     => $zone->total_stores ?? $zone->total_order_count ?? 0,
                'total_order_count' => $zone->total_order_count ?? null,
                'total_earning'         => $zone->total_earning,
                'percentage_of_earning' => $totalEarningsAllZones > 0
                    ? round(($zone->total_earning / $totalEarningsAllZones) * 100, 2)
                    : 0,
            ];
        });
        $html = view('admin-views.report.partials._top_zones', compact('topZones', 'include_subscription'))->render();
        return response()->json(['view' => $html]);
    }

    public function getTopEarningStores(Request $request){

        [$filter, $from, $to] = $this->resolveDateFilter($request);
        $module_id = $this->resolveModuleId($request);
        $order_types = $this->normalizeOrderTypes($request->query('order_types', $request->query('order_type', ['take_away', 'delivery'])));
        $hasModuleFilter = $this->hasModuleFilter($module_id);

        $earningFormula = $this->getAdminTotalEarningQuery();

        $subQuery = DB::table('subscription_transactions as sub')
            ->join('stores', 'stores.id', '=', 'sub.store_id')
            ->select('sub.store_id')
            ->selectRaw("COALESCE(SUM(sub.paid_amount), 0) as subscription_earning")
            ->where('sub.payment_status', 'success')
            ->where('sub.is_trial', 0)
            ->when($hasModuleFilter, function($q) use ($module_id) {
                $q->where('stores.module_id', $module_id);
            })
            ->when(true, function($q) use ($filter, $from, $to) {
                if($filter === 'custom' && $from && $to){
                    $q->whereBetween('sub.created_at', ["$from 00:00:00", "$to 23:59:59"]);
                } elseif($filter === 'this_year') {
                    $q->whereYear('sub.created_at', now()->year);
                } elseif($filter === 'this_month') {
                    $q->whereYear('sub.created_at', now()->year)
                    ->whereMonth('sub.created_at', now()->month);
                } elseif($filter === 'previous_year') {
                    $q->whereYear('sub.created_at', now()->year - 1);
                } elseif($filter === 'this_week') {
                    $q->whereBetween('sub.created_at', [
                        now()->startOfWeek()->format('Y-m-d H:i:s'),
                        now()->endOfWeek()->format('Y-m-d H:i:s')
                    ]);
                }
            })
            ->groupBy('sub.store_id');

        $topStores = DB::table('stores')
            ->when($hasModuleFilter, function ($query) use ($module_id) {
                $query->where('stores.module_id', $module_id);
            })
            ->leftJoin('orders','stores.id','=','orders.store_id')
            ->leftJoin('order_transactions', function ($join) use ($filter, $from, $to, $module_id, $hasModuleFilter) {
                $join->on('orders.id', '=', 'order_transactions.order_id');
                if ($hasModuleFilter) {
                    $join->where('order_transactions.module_id', $module_id);
                }
                if($filter === 'custom' && $from && $to){
                    $join->whereBetween('order_transactions.created_at', ["$from 00:00:00", "$to 23:59:59"]);
                } elseif($filter === 'this_year') {
                    $join->whereYear('order_transactions.created_at', now()->year);
                } elseif($filter === 'this_month') {
                    $join->whereYear('order_transactions.created_at', now()->year)
                    ->whereMonth('order_transactions.created_at', now()->month);
                } elseif($filter === 'previous_year') {
                    $join->whereYear('order_transactions.created_at', now()->year - 1);
                } elseif($filter === 'this_week') {
                    $join->whereBetween('order_transactions.created_at', [
                        now()->startOfWeek()->format('Y-m-d H:i:s'),
                        now()->endOfWeek()->format('Y-m-d H:i:s')
                    ]);
                }
            });
        $topStores = $this->applyModuleOrOrderTypeFilter(
            query: $topStores,
            module_id: $module_id,
            order_types: $order_types,
            moduleColumn: 'order_transactions.module_id',
            orderTypeColumn: 'orders.order_type'
        )
            ->leftJoinSub($subQuery, 'sub', 'stores.id', '=', 'sub.store_id')
            ->leftJoin('zones','stores.zone_id','=','zones.id')
            ->select(
                'stores.id',
                'stores.logo',
                'stores.name as store_name',
                'zones.name as zone_name'
            )
            ->selectRaw("COALESCE($earningFormula, 0) as admin_earning")
            ->selectRaw("COALESCE(sub.subscription_earning, 0) as subscription_earning")
            ->selectRaw("COALESCE($earningFormula, 0) + COALESCE(sub.subscription_earning, 0) as total_earning")
            ->selectRaw("COUNT(order_transactions.id) as total_transactions")
            ->selectSub(function($query) {
                $query->from('storages as storage')
                    ->whereColumn('storage.data_id','stores.id')
                    ->where('storage.data_type', \App\Models\Store::class)
                    ->limit(1)
                    ->select('value');
            }, 'storage')
            ->groupBy('stores.id', 'stores.name', 'zones.name', 'sub.subscription_earning')
            ->havingRaw("total_transactions > 0")
            ->havingRaw("total_earning > 0")
            ->orderByDesc('total_earning')
            ->limit(10)
            ->get()
            ->map(function($store) {
                $store->total_earning = $store->admin_earning + $store->subscription_earning;
                return $store;
            });


        $html = view('admin-views.report.partials._top_stores', compact('topStores'))->render();
        return response()->json(['view' => $html]);
    }

    public function getEarningTransactions(Request $request)
    {
        [$filter, $from, $to] = $this->resolveDateFilter($request);
        $module_id = $this->resolveModuleId($request);
        $order_types = $this->normalizeOrderTypes($request->query('order_types', $request->query('order_type', ['take_away', 'delivery'])));
        $include_subscription = $this->shouldIncludeSubscription($order_types);
        $type = $request->query('type', 'order'); // 'order', 'subscription', 'expense'

        if ($type === 'subscription' && !$include_subscription) {
            $transactions = collect();
        } elseif ($type === 'subscription') {
            $transactions = $this->get_subscription_earning_transactions(
                request: $request,
                filter: $filter,
                from: $from,
                to: $to,
                nopaginate: false,
                module_id: $module_id
            );
        } elseif ($type === 'expense') {
            $transactions = $this->get_expense_transactions(
                request: $request,
                filter: $filter,
                from: $from,
                to: $to,
                nopaginate: false,
                module_id: $module_id,
                order_types: $order_types
            );
        } else {
            $transactions = $this->get_order_earning_transactions(
                request: $request,
                filter: $filter,
                from: $from,
                to: $to,
                nopaginate: false,
                module_id: $module_id,
                order_types: $order_types
            );
        }

        $view = 'admin-views.report.partials._transaction_table';

        return response()->json([
            'transactions' => $transactions,
            'view' => view()->exists($view) ? view($view, [
                'transactions' => $transactions,
                'type' => $type,
                'use_additional_charge_name_in_breakdown' => true,
            ])->render() : ''
        ]);
    }
    public function getDeliverymanEarningTransactions(Request $request)
    {
        [$filter, $from, $to] = $this->resolveDateFilter($request);
        $delivery_man_id = $request->query('delivery_man_id', 'all');
        $type = 'order';
        $order_types = $this->normalizeOrderTypes($request->query('order_types', $request->query('order_type', ['take_away', 'delivery'])));
        // $order_types = 'all';
        $transactions = $this->get_deliveryman_earning_transactions(
            request: $request,
            delivery_man_id: $delivery_man_id,
            filter: $filter,
            from: $from,
            to: $to,
            nopaginate: false,
            order_types: $order_types
        );

        $view = 'admin-views.report.partials._transaction_table_deliveryman';

        return response()->json([
            'transactions' => $transactions,
            'view' => view()->exists($view) ? view($view, compact('transactions', 'type'))->render() : ''
        ]);
    }

    public function exportEarningTransactions(Request $request)
    {
        [$filter, $from, $to] = $this->resolveDateFilter($request);
        $module_id = $this->resolveModuleId($request);
        $order_types = $this->normalizeOrderTypes($request->query('order_types', $request->query('order_type', ['take_away', 'delivery'])));
        $include_subscription = $this->shouldIncludeSubscription($order_types);
        $type = $request->query('type', 'order'); // 'order', 'subscription', 'expense'
        $export_type = $request->query('export_type', 'excel');

        if ($type === 'subscription' && !$include_subscription) {
            $transactions = collect();
            $title = 'Subscription_Earning_Report';
        } elseif ($type === 'subscription') {
            $transactions = $this->get_subscription_earning_transactions(
                request: $request,
                filter: $filter,
                from: $from,
                to: $to,
                nopaginate: true,
                module_id: $module_id
            );
            $title = 'Subscription_Earning_Report';
        } elseif ($type === 'expense') {
            $transactions = $this->get_expense_transactions(
                request: $request,
                filter: $filter,
                from: $from,
                to: $to,
                nopaginate: true,
                module_id: $module_id,
                order_types: $order_types
            );
            $title = 'Admin_Expense_Report';
        } else {
            $transactions = $this->get_order_earning_transactions(
                request: $request,
                filter: $filter,
                from: $from,
                to: $to,
                nopaginate: true,
                module_id: $module_id,
                order_types: $order_types
            );
            $title = 'Admin_Earning_Report';
        }

        $data = [
            'transactions' => $transactions,
            'filter' => $filter,
            'from' => $from,
            'to' => $to,
            'search' => $request->search,
            'title' => $title,
            'type' => $type,
        ];

        if ($export_type === 'csv') {
            return Excel::download(new AdminEarningTransactionExport($data), $title . '.csv', \Maatwebsite\Excel\Excel::CSV);
        }
        return Excel::download(new AdminEarningTransactionExport($data), $title . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    public function exportDeliverymanEarningTransactions(Request $request)
    {
        [$filter, $from, $to] = $this->resolveDateFilter($request);
        $delivery_man_id = $request->query('delivery_man_id', 'all');
        $export_type = $request->query('export_type', 'excel');
        $type = 'order';
        $order_types = $this->normalizeOrderTypes($request->query('order_types', $request->query('order_type', ['take_away', 'delivery'])));
        $transactions = $this->get_deliveryman_earning_transactions(
            request: $request,
            delivery_man_id: $delivery_man_id,
            filter: $filter,
            from: $from,
            to: $to,
            nopaginate: true,
            order_types: $order_types
        );
        $title = 'Deliveryman_Earning_Report';

        $delivery_man_name = 'All';
        if($delivery_man_id && $delivery_man_id !== 'all'){
            $dm = \App\Models\DeliveryMan::find($delivery_man_id);
            $delivery_man_name = $dm ? $dm->f_name . ' ' . $dm->l_name : 'N/A';
        }

        $data = [
            'transactions' => $transactions,
            'filter' => $filter,
            'from' => $from,
            'to' => $to,
            'search' => $request->search,
            'title' => $title,
            'type' => $type,
            'delivery_man_name' => $delivery_man_name
        ];

        if ($export_type === 'csv') {
            return Excel::download(new DeliverymanEarningTransactionExport($data), $title . '.csv', \Maatwebsite\Excel\Excel::CSV);
        }
        return Excel::download(new DeliverymanEarningTransactionExport($data), $title . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
}
