<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeliveryMan;
use App\Traits\ReportGeneratorTrait;
use Illuminate\Http\Request;

class DeliverymanEarningReportController extends Controller
{
    use ReportGeneratorTrait;

    public function getEarningReport(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        if (!$dm) {
            return response()->json([
                'message' => translate('messages.unauthorized')
            ], 401);
        }

        $filter = $request->query('filter', 'all_time');
        $from = $filter === 'custom' ? $request->from : null;
        $to = $filter === 'custom' ? $request->to : null;
        $order_types = $request->query('order_types', $request->query('order_type', ['take_away', 'delivery']));

        $summary = $this->get_deliveryman_earning_summary_data(
            delivery_man_id: $dm->id,
            filter: $filter,
            from: $from,
            to: $to,
            order_types: $order_types
        );
        $trends = $this->get_deliveryman_earning_trend_data(
            delivery_man_id: $dm->id,
            filter: $filter,
            from: $from,
            to: $to,
            order_types: $order_types
        );
        $transactions = $this->get_deliveryman_earning_transactions(
            request: $request,
            delivery_man_id: $dm->id,
            filter: $filter,
            from: $from,
            to: $to,
            order_types: $order_types
        );

        unset($summary['breakdown']['admin_commission']);

        return response()->json([
            'summary' => $summary,
            'trends' => $trends,
            'transactions' => $transactions,
        ], 200);
    }
}
