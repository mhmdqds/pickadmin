<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Traits\ReportGeneratorTrait;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StoreEarningTransactionExport;

class StoreEarningReportController extends Controller
{
    use ReportGeneratorTrait;

    public function getStoreEarningReport(Request $request)
    {
        $store = Helpers::get_store_data();
        $store_id = $store?->id ?? 'all';

        return view('vendor-views.report.store-earning-report', compact('store', 'store_id'));
    }

    public function getStoreEarningSummary(Request $request)
    {
        $store = Helpers::get_store_data();
        $store_id = $store?->vendor_id ?? 'all';
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
        $store = Helpers::get_store_data();
        $store_id = $store?->vendor_id ?? 'all';
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
        $store = Helpers::get_store_data();
        $store_id = $store?->vendor_id ?? 'all';
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
        $store = Helpers::get_store_data();
        $store_id = $store?->vendor_id ?? 'all';
        [$filter, $from, $to] = $this->resolveDateFilter($request);

        $trends = $this->get_store_earning_trend_data(
            store_id: $store_id,
            filter: $filter,
            from: $from,
            to: $to
        );

        return response()->json($trends);
    }

    public function getStoreEarningTransactions(Request $request)
    {
        $store = Helpers::get_store_data();
        $store_id = $store?->vendor_id ?? 'all';
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

        $hide_source_column = true;

        return response()->json([
            'transactions' => $transactions,
            'view' => view('admin-views.report.partials._transaction_table', compact('transactions', 'type', 'hide_source_column'))->render()
        ]);
    }

    public function exportStoreEarningTransactions(Request $request)
    {
        $store = Helpers::get_store_data();
        $store_id = $store?->vendor_id ?? 'all';
        [$filter, $from, $to] = $this->resolveDateFilter($request);
        $type = $request->query('type', 'order');
        $export_type = $request->query('export_type', 'excel');

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
            'store_name' => $store?->name ?? 'All',
            'type' => $type,
        ];

        if ($export_type === 'csv') {
            return Excel::download(new StoreEarningTransactionExport($data), $title . '.csv', \Maatwebsite\Excel\Excel::CSV);
        }
        return Excel::download(new StoreEarningTransactionExport($data), $title . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
}
