<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Traits\ReportGeneratorTrait;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StoreEarningTransactionExport;

class StoreEarningReportController extends Controller
{
    use ReportGeneratorTrait;

    public function getStoreEarningReport(Request $request)
    {
        $store_id = $request->query('store_id', 'all');
        $store = $store_id !== 'all' ? Store::find($store_id) : null;
        $module_id = $request->query('module_id', $store?->module_id ?? 'all');

        return view('admin-views.report.store-earning-report', compact('store', 'store_id', 'module_id'));
    }

    public function getStoreEarningSummary(Request $request)
    {
        $store_id = $request->query('store_id', 'all');
        [$filter, $from, $to] = $this->resolveDateFilter($request);
        $summary = $this->get_store_earning_summary_data(
            store_id: $store_id,
            filter: $filter,
            from: $from,
            to: $to
        );

        return response()->json([
            'view' => view('admin-views.report.partials._store-earning-summary', compact('summary'))->render()
        ]);
    }

    public function getStoreEarningBreakdown(Request $request)
    {
        $store_id = $request->query('store_id', 'all');
        [$filter, $from, $to] = $this->resolveDateFilter($request);
        $summary = $this->get_store_earning_summary_data(
            store_id: $store_id,
            filter: $filter,
            from: $from,
            to: $to
        );

        return response()->json([
            'view' => view('admin-views.report.partials._store-earning-breakdown', compact('summary'))->render()
        ]);
    }

    public function getStoreExpenseBreakdown(Request $request)
    {
        $store_id = $request->query('store_id', 'all');
        [$filter, $from, $to] = $this->resolveDateFilter($request);
        $summary = $this->get_store_earning_summary_data(
            store_id: $store_id,
            filter: $filter,
            from: $from,
            to: $to
        );

        return response()->json([
            'view' => view('admin-views.report.partials._store-expense-breakdown', compact('summary'))->render()
        ]);
    }

    public function getStoreEarningTrend(Request $request)
    {
        $store_id = $request->query('store_id', 'all');
        $module_id = $request->query('module_id', 'all');
        [$filter, $from, $to] = $this->resolveDateFilter($request);
        $trends = $this->get_store_earning_trend_data(
            store_id: $store_id,
            filter: $filter,
            from: $from,
            to: $to,
            order_types: null,
            module_id: $module_id
        );

        return response()->json($trends);
    }

    public function getStoreEarningTransactions(Request $request)
    {
        $store_id = $request->query('store_id', 'all');
        [$filter, $from, $to] = $this->resolveDateFilter($request);
        $type = $request->query('type', 'order');

        if ($type === 'expense') {
            $transactions = $this->get_store_expense_transactions(
                request: $request,
                store_id: $store_id,
                filter: $filter,
                from: $from,
                to: $to
            );
        } elseif ($type === 'subscription') {
            $transactions = $this->get_store_subscription_transactions(
                request: $request,
                store_id: $store_id,
                filter: $filter,
                from: $from,
                to: $to
            );
        } else {
            $transactions = $this->get_store_earning_transactions(
                request: $request,
                store_id: $store_id,
                filter: $filter,
                from: $from,
                to: $to
            );
        }

        $view = 'admin-views.report.partials._transaction_table';

        return response()->json([
            'transactions' => $transactions,
            'view' => view()->exists($view) ? view($view, compact('transactions', 'type'))->render() : ''
        ]);
    }

    public function exportStoreEarningTransactions(Request $request)
    {
        $store_id = $request->query('store_id', 'all');
        [$filter, $from, $to] = $this->resolveDateFilter($request);
        $type = $request->query('type', 'order');
        $export_type = $request->query('export_type', 'excel');
        $store_name = 'All';
        if ($store_id !== 'all') {
            $store = Store::find($store_id);
            $store_name = $store ? $store->name : 'N/A';
        }

        if ($type === 'expense') {
            $transactions = $this->get_store_expense_transactions(
                request: $request,
                store_id: $store_id,
                filter: $filter,
                from: $from,
                to: $to,
                nopaginate: true
            );
            $title = 'Store_Expense_Report';
        } elseif ($type === 'subscription') {
            $transactions = $this->get_store_subscription_transactions(
                request: $request,
                store_id: $store_id,
                filter: $filter,
                from: $from,
                to: $to,
                nopaginate: true
            );
            $title = 'Store_Subscription_Report';
        } else {
            $transactions = $this->get_store_earning_transactions(
                request: $request,
                store_id: $store_id,
                filter: $filter,
                from: $from,
                to: $to,
                nopaginate: true
            );
            $title = 'Store_Earning_Report';
        }

        $data = [
            'transactions' => $transactions,
            'filter' => $filter,
            'from' => $from,
            'to' => $to,
            'search' => $request->search,
            'title' => $title,
            'store_name' => $store_name,
            'type' => $type,
        ];

        if ($export_type === 'csv') {
            return Excel::download(new StoreEarningTransactionExport($data), $title . '.csv', \Maatwebsite\Excel\Excel::CSV);
        }
        return Excel::download(new StoreEarningTransactionExport($data), $title . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
}
