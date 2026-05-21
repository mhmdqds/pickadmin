<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Traits\ReportGeneratorTrait;
use Illuminate\Http\Request;

class StoreEarningReportController extends Controller
{
    use ReportGeneratorTrait;

    public function getEarningReport(Request $request)
    {
        $store = Helpers::get_store_data() ?? $request->vendor?->stores?->first();

        if (!$store) {
            return response()->json([
                'message' => translate('messages.unauthorized')
            ], 401);
        }

        $filter = $request->query('filter', 'all_time');
        $from = $filter === 'custom' ? $request->from : null;
        $to = $filter === 'custom' ? $request->to : null;
        $type = $request->query('type', 'earning');
        $limit = $request->query('limit', config('default_pagination', 25));
        $offset = $request->query('offset', 1);
        $order_types = $request->query('order_types', $request->query('order_type', ['take_away', 'delivery']));

        $summary = $this->get_store_earning_summary_data(
            store_id: $store->id,
            filter: $filter,
            from: $from,
            to: $to,
            order_types: $order_types
        );
        $trends = $this->get_store_earning_trend_data(
            store_id: $store->id,
            filter: $filter,
            from: $from,
            to: $to,
            order_types: $order_types
        );

        if ($type === 'expense') {
            $transactions = $this->get_store_expense_transactions(
                request: $request,
                store_id: $store->id,
                filter: $filter,
                from: $from,
                to: $to,
                nopaginate: false,
                limit: $limit,
                offset: $offset,
                order_types: $order_types
            );
        } elseif ($type === 'subscription') {
            $transactions = $this->get_store_subscription_transactions(
                request: $request,
                store_id: $store->id,
                filter: $filter,
                from: $from,
                to: $to,
                nopaginate: false,
                limit: $limit,
                offset: $offset
            );
        } else {
            $transactions = $this->get_store_earning_transactions(
                request: $request,
                store_id: $store->id,
                filter: $filter,
                from: $from,
                to: $to,
                nopaginate: false,
                limit: $limit,
                offset: $offset,
                order_types: $order_types
            );
        }

        return response()->json([
            'summary' => $summary,
            'trends' => $trends,
            'total_size' => $transactions->total(),
            'limit' => (int) $limit,
            'offset' => (int) $offset,
            'transactions' => $transactions->items(),
        ], 200);
    }
}
