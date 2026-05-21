<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryMan;
use App\Traits\ReportGeneratorTrait;
use Illuminate\Http\Request;

class DeliverymanEarningReportController extends Controller
{
    use ReportGeneratorTrait;

    public function getDeliverymanEarningReport(Request $request)
    {
        $delivery_men = DeliveryMan::orderBy('f_name')->where('earning', 1)->get(['id', 'f_name', 'l_name']);
        $delivery_man_id = $request->query('delivery_man_id', 'all');

        return view('admin-views.report.deliveryman-earning-report', compact('delivery_men', 'delivery_man_id'));
    }

    public function getDeliverymanEarningSummary(Request $request)
    {
        $delivery_man_id = $request->query('delivery_man_id', 'all');
        $filter = $request->query('filter', 'all_time');
        $from = $filter === 'custom' ? $request->from : null;
        $to   = $filter === 'custom' ? $request->to : null;

        $summary = $this->get_deliveryman_earning_summary_data(
            delivery_man_id: $delivery_man_id,
            filter: $filter,
            from: $from,
            to: $to
        );
        
        return response()->json([
            'view' => view('admin-views.report.partials._deliveryman-earning-summary', compact('summary'))->render()
        ]);
    }

    public function getDeliverymanEarningBreakdown(Request $request)
    {
        $delivery_man_id = $request->query('delivery_man_id', 'all');
        $filter = $request->query('filter', 'all_time');
        $from = $filter === 'custom' ? $request->from : null;
        $to   = $filter === 'custom' ? $request->to : null;

        $summary = $this->get_deliveryman_earning_summary_data(
            delivery_man_id: $delivery_man_id,
            filter: $filter,
            from: $from,
            to: $to
        );
        
        return response()->json([
            'view' => view('admin-views.report.partials._deliveryman-earning-breakdown', compact('summary'))->render()
        ]);
    }

    public function getDeliverymanExpenseBreakdown(Request $request)
    {
        $delivery_man_id = $request->query('delivery_man_id', 'all');
        $filter = $request->query('filter', 'all_time');
        $from = $filter === 'custom' ? $request->from : null;
        $to   = $filter === 'custom' ? $request->to : null;

        $summary = $this->get_deliveryman_earning_summary_data(
            delivery_man_id: $delivery_man_id,
            filter: $filter,
            from: $from,
            to: $to
        );
        
        return response()->json([
            'view' => view('admin-views.report.partials._deliveryman-expense-breakdown', compact('summary'))->render()
        ]);
    }

    public function getDeliverymanEarningTrend(Request $request)
    {
        $delivery_man_id = $request->query('delivery_man_id', 'all');
        $filter = $request->query('filter', 'all_time');
        $from = $filter === 'custom' ? $request->from : null;
        $to   = $request->to;

        $trends = $this->get_deliveryman_earning_trend_data(
            delivery_man_id: $delivery_man_id,
            filter: $filter,
            from: $from,
            to: $to
        );

        return response()->json($trends);
    }
}
