<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DisbursementReportExport;
use App\Models\DeliveryMan;
use App\Models\DisbursementDetails;
use App\Models\WithdrawalMethod;
use Carbon\Carbon;
use App\Models\Item;
use App\Models\User;
use App\Models\Zone;
use App\Models\Order;
use App\Models\Store;
use App\Models\Expense;
use App\Models\Category;
use App\Scopes\StoreScope;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use App\Models\OrderTransaction;
use App\Exports\ExpenseReportExport;
use App\Exports\ItemReportExport;
use App\Exports\LimitedStockReportExport;
use App\Exports\OrderReportExport;
use App\Exports\StoreOrderReportExport;
use App\Exports\StoreSalesReportExport;
use App\Exports\StoreSummaryReportExport;
use App\Exports\TransactionReportExport;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Config;
class ReportController extends Controller
{
    public function order_index()
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', date('Y-m-01'));
            session()->put('to_date', date('Y-m-30'));
        }
        return view('admin-views.report.order-index');
    }

    public function day_wise_report(Request $request)
    {
        $key = explode(' ', $request['search']);

        $from =  null;
        $to = null;
        $filter = $request->query('filter', 'all_time');
        if($filter == 'custom'){
            $from = $request->from ?? null;
            $to = $request->to ?? null;
        }

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store_id = $request->query('store_id', 'all');
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;

        $order_transactions = OrderTransaction::with('order', 'order.details', 'order.customer', 'order.store', 'delivery_man')->when(isset($zone), function ($query) use ($zone) {
            return $query->where('zone_id', $zone->id);
        })
                        ->when(isset($key), function ($query) use ($key) {
                    return $query->where(function ($q) use ($key) {
                            foreach ($key as $value) {
                                $q->orWhere('order_id', 'like', "%{$value}%");
                            }
                        });
                })
            ->when(isset($store), function ($query) use ($store) {
                return $query->whereHas('order', function ($q) use ($store) {
                    $q->where('store_id', $store->id);
                });
            })
            ->when(request('module_id'), function ($query) {
                return $query->module(request('module_id'));
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('created_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })->orderBy('created_at', 'desc')
            ->paginate(config('default_pagination'))->withQueryString();

        $admin_earned = OrderTransaction::with('order', 'order.details', 'order.customer', 'order.store')->when(isset($zone), function ($query) use ($zone) {
            return $query->where('zone_id', $zone->id);
        })
                        ->when(isset($key), function ($query) use ($key) {
                    return $query->where(function ($q) use ($key) {
                            foreach ($key as $value) {
                                $q->orWhere('order_id', 'like', "%{$value}%");
                            }
                        });
                })
            ->when(isset($store), function ($query) use ($store) {
                return $query->whereHas('order', function ($q) use ($store) {
                    $q->where('store_id', $store->id);
                });
            })
            ->when(request('module_id'), function ($query) {
                return $query->module(request('module_id'));
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('created_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })->orderBy('created_at', 'desc')
            ->notRefunded()
            ->sum(DB::raw('admin_commission'));

        $admin_earned_delivery_commission = OrderTransaction::with('order', 'order.details', 'order.customer', 'order.store')->when(isset($zone), function ($query) use ($zone) {
            return $query->where('zone_id', $zone->id);
        })
                        ->when(isset($key), function ($query) use ($key) {
                    return $query->where(function ($q) use ($key) {
                            foreach ($key as $value) {
                                $q->orWhere('order_id', 'like', "%{$value}%");
                            }
                        });
                })
            ->when(isset($store), function ($query) use ($store) {
                return $query->whereHas('order', function ($q) use ($store) {
                    $q->where('store_id', $store->id);
                });
            })
            ->when(request('module_id'), function ($query) {
                return $query->module(request('module_id'));
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('created_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->orderBy('created_at', 'desc')
            ->sum(DB::raw('case when delivery_man_id is null then original_delivery_charge else delivery_fee_comission end'));


        $store_earned = OrderTransaction::with('order', 'order.details', 'order.customer', 'order.store')->when(isset($zone), function ($query) use ($zone) {
            return $query->where('zone_id', $zone->id);
        })
                        ->when(isset($key), function ($query) use ($key) {
                    return $query->where(function ($q) use ($key) {
                            foreach ($key as $value) {
                                $q->orWhere('order_id', 'like', "%{$value}%");
                            }
                        });
                })
            ->when(isset($store), function ($query) use ($store) {
                return $query->whereHas('order', function ($q) use ($store) {
                    $q->where('store_id', $store->id);
                });
            })
            ->when(request('module_id'), function ($query) {
                return $query->module(request('module_id'));
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('created_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })->orderBy('created_at', 'desc')
            ->notRefunded()
            ->sum(DB::raw('store_amount'));
            // ->sum(DB::raw('store_amount - tax'));

        $deliveryman_earned = OrderTransaction::with('order', 'order.details', 'order.customer', 'order.store')->when(isset($zone), function ($query) use ($zone) {
            return $query->where('zone_id', $zone->id);
        })
                        ->when(isset($key), function ($query) use ($key) {
                    return $query->where(function ($q) use ($key) {
                            foreach ($key as $value) {
                                $q->orWhere('order_id', 'like', "%{$value}%");
                            }
                        });
                })
            ->when(isset($store), function ($query) use ($store) {
                return $query->whereHas('order', function ($q) use ($store) {
                    $q->where('store_id', $store->id);
                });
            })
            ->when(request('module_id'), function ($query) {
                return $query->module(request('module_id'));
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('created_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->whereNotNull('delivery_man_id')
            ->orderBy('created_at', 'desc')
            ->sum(DB::raw('original_delivery_charge + dm_tips'));
        return view('admin-views.report.day-wise-report', compact('order_transactions', 'zone', 'store', 'filter', 'admin_earned', 'admin_earned_delivery_commission', 'store_earned', 'deliveryman_earned','key','from','to'));
    }

    public function day_wise_export(Request $request)
    {
        $key = explode(' ', $request['search']);

         $from =  null;
        $to = null;
        $filter = $request->query('filter', 'all_time');
        if($filter == 'custom'){
            $from = $request->from ?? null;
            $to = $request->to ?? null;
        }
        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store_id = $request->query('store_id', 'all');
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;

        $order_transactions = OrderTransaction::when(isset($zone), function ($query) use ($zone) {
            return $query->where('zone_id', $zone->id);
        })
                        ->when(isset($key), function ($query) use ($key) {
                    return $query->where(function ($q) use ($key) {
                            foreach ($key as $value) {
                                $q->orWhere('order_id', 'like', "%{$value}%");
                            }
                        });
                })
            ->when(isset($store), function ($query) use ($store) {
                return $query->whereHas('order', function ($q) use ($store) {
                    $q->where('store_id', $store->id);
                });
            })
            ->when(request('module_id'), function ($query) {
                return $query->module(request('module_id'));
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('created_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })->orderBy('created_at', 'desc')
            ->get();

            $admin_earned = OrderTransaction::with('order', 'order.details', 'order.customer', 'order.store')->when(isset($zone), function ($query) use ($zone) {
                return $query->where('zone_id', $zone->id);
            })
                            ->when(isset($key), function ($query) use ($key) {
                        return $query->where(function ($q) use ($key) {
                                foreach ($key as $value) {
                                    $q->orWhere('order_id', 'like', "%{$value}%");
                                }
                            });
                    })
                ->when(isset($store), function ($query) use ($store) {
                    return $query->whereHas('order', function ($q) use ($store) {
                        $q->where('store_id', $store->id);
                    });
                })
                ->when(request('module_id'), function ($query) {
                    return $query->module(request('module_id'));
                })
                ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                    return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
                })
                ->when(isset($filter) && $filter == 'this_year', function ($query) {
                    return $query->whereYear('created_at', now()->format('Y'));
                })
                ->when(isset($filter) && $filter == 'this_month', function ($query) {
                    return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                })
                ->when(isset($filter) && $filter == 'this_month', function ($query) {
                    return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                })
                ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                    return $query->whereYear('created_at', date('Y') - 1);
                })
                ->when(isset($filter) && $filter == 'this_week', function ($query) {
                    return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
                })->orderBy('created_at', 'desc')
                ->notRefunded()
                ->sum(DB::raw('admin_commission -  delivery_fee_comission'));
            // ->sum(DB::raw('(admin_commission + admin_expense) - delivery_fee_comission'));

            $admin_earned_delivery_commission = OrderTransaction::with('order', 'order.details', 'order.customer', 'order.store')->when(isset($zone), function ($query) use ($zone) {
                return $query->where('zone_id', $zone->id);
            })
                            ->when(isset($key), function ($query) use ($key) {
                        return $query->where(function ($q) use ($key) {
                                foreach ($key as $value) {
                                    $q->orWhere('order_id', 'like', "%{$value}%");
                                }
                            });
                    })
                ->when(isset($store), function ($query) use ($store) {
                    return $query->whereHas('order', function ($q) use ($store) {
                        $q->where('store_id', $store->id);
                    });
                })
                ->when(request('module_id'), function ($query) {
                    return $query->module(request('module_id'));
                })
                ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                    return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
                })
                ->when(isset($filter) && $filter == 'this_year', function ($query) {
                    return $query->whereYear('created_at', now()->format('Y'));
                })
                ->when(isset($filter) && $filter == 'this_month', function ($query) {
                    return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                })
                ->when(isset($filter) && $filter == 'this_month', function ($query) {
                    return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                })
                ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                    return $query->whereYear('created_at', date('Y') - 1);
                })
                ->when(isset($filter) && $filter == 'this_week', function ($query) {
                    return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
                })->orderBy('created_at', 'desc')
                ->sum(DB::raw('case when delivery_man_id is null then original_delivery_charge else delivery_fee_comission end'));

            $store_earned = OrderTransaction::with('order', 'order.details', 'order.customer', 'order.store')->when(isset($zone), function ($query) use ($zone) {
                return $query->where('zone_id', $zone->id);
            })
                            ->when(isset($key), function ($query) use ($key) {
                        return $query->where(function ($q) use ($key) {
                                foreach ($key as $value) {
                                    $q->orWhere('order_id', 'like', "%{$value}%");
                                }
                            });
                    })
                ->when(isset($store), function ($query) use ($store) {
                    return $query->whereHas('order', function ($q) use ($store) {
                        $q->where('store_id', $store->id);
                    });
                })
                ->when(request('module_id'), function ($query) {
                    return $query->module(request('module_id'));
                })
                ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                    return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
                })
                ->when(isset($filter) && $filter == 'this_year', function ($query) {
                    return $query->whereYear('created_at', now()->format('Y'));
                })
                ->when(isset($filter) && $filter == 'this_month', function ($query) {
                    return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                })
                ->when(isset($filter) && $filter == 'this_month', function ($query) {
                    return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                })
                ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                    return $query->whereYear('created_at', date('Y') - 1);
                })
                ->when(isset($filter) && $filter == 'this_week', function ($query) {
                    return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
                })->orderBy('created_at', 'desc')
                ->notRefunded()
                ->sum(DB::raw('store_amount'));
                // ->sum(DB::raw('store_amount - tax'));

            $deliveryman_earned = OrderTransaction::with('order', 'order.details', 'order.customer', 'order.store')->when(isset($zone), function ($query) use ($zone) {
                return $query->where('zone_id', $zone->id);
            })
                            ->when(isset($key), function ($query) use ($key) {
                        return $query->where(function ($q) use ($key) {
                                foreach ($key as $value) {
                                    $q->orWhere('order_id', 'like', "%{$value}%");
                                }
                            });
                    })
                ->when(isset($store), function ($query) use ($store) {
                    return $query->whereHas('order', function ($q) use ($store) {
                        $q->where('store_id', $store->id);
                    });
                })
                ->when(request('module_id'), function ($query) {
                    return $query->module(request('module_id'));
                })
                ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                    return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
                })
                ->when(isset($filter) && $filter == 'this_year', function ($query) {
                    return $query->whereYear('created_at', now()->format('Y'));
                })
                ->when(isset($filter) && $filter == 'this_month', function ($query) {
                    return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                })
                ->when(isset($filter) && $filter == 'this_month', function ($query) {
                    return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                })
                ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                    return $query->whereYear('created_at', date('Y') - 1);
                })
                ->when(isset($filter) && $filter == 'this_week', function ($query) {
                    return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
                })
                ->whereNotNull('delivery_man_id')
                ->orderBy('created_at', 'desc')
                ->sum(DB::raw('original_delivery_charge + dm_tips'));

                $delivered = Order::when(isset($zone), function ($query) use ($zone) {
                    return $query->where('zone_id', $zone->id);
                })
                ->when(isset($key), function ($query) use ($key) {
                        return $query->where(function ($q) use ($key) {
                                foreach ($key as $value) {
                                    $q->orWhere('id', 'like', "%{$value}%");
                                }
                            });
                    })
                    ->when(request('module_id'), function ($query) {
                        return $query->module(request('module_id'));
                    })
                    ->whereIn('order_status', ['delivered','refund_requested','refund_request_canceled'])
                    ->when(isset($store), function ($query) use ($store) {
                        return $query->where('store_id', $store->id);
                    })
                    ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                        return $query->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
                    })
                    ->when(isset($filter) && $filter == 'this_year', function ($query) {
                        return $query->whereYear('created_at', now()->format('Y'));
                    })
                    ->when(isset($filter) && $filter == 'this_month', function ($query) {
                        return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                    })
                    ->when(isset($filter) && $filter == 'this_month', function ($query) {
                        return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                    })
                    ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                        return $query->whereYear('created_at', date('Y') - 1);
                    })
                    ->when(isset($filter) && $filter == 'this_week', function ($query) {
                        return $query->whereBetween('created_at', [
                            now()
                                ->startOfWeek()
                                ->format('Y-m-d H:i:s'),
                            now()
                                ->endOfWeek()
                                ->format('Y-m-d H:i:s'),
                        ]);
                    })
                    ->Notpos()
                    ->sum('order_amount');
                $canceled = Order::when(isset($zone), function ($query) use ($zone) {
                    return $query->where('zone_id', $zone->id);
                })
                ->when(isset($key), function ($query) use ($key) {
                        return $query->where(function ($q) use ($key) {
                                foreach ($key as $value) {
                                    $q->orWhere('id', 'like', "%{$value}%");
                                }
                            });
                    })
                    ->when(request('module_id'), function ($query) {
                        return $query->module(request('module_id'));
                    })
                    ->where(['order_status' => 'refunded'])
                    ->when(isset($store), function ($query) use ($store) {
                        return $query->where('store_id', $store->id);
                    })
                    ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                        return $query->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
                    })
                    ->when(isset($filter) && $filter == 'this_year', function ($query) {
                        return $query->whereYear('created_at', now()->format('Y'));
                    })
                    ->when(isset($filter) && $filter == 'this_month', function ($query) {
                        return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                    })
                    ->when(isset($filter) && $filter == 'this_month', function ($query) {
                        return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                    })
                    ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                        return $query->whereYear('created_at', date('Y') - 1);
                    })
                    ->when(isset($filter) && $filter == 'this_week', function ($query) {
                        return $query->whereBetween('created_at', [
                            now()
                                ->startOfWeek()
                                ->format('Y-m-d H:i:s'),
                            now()
                                ->endOfWeek()
                                ->format('Y-m-d H:i:s'),
                        ]);
                    })
                    ->whereNotNull('delivery_man_id')
                    ->Notpos()
                    // ->sum(DB::raw('order_amount - original_delivery_charge'));
                    ->sum(DB::raw('order_amount - delivery_charge - dm_tips'));

            $data = [
                'order_transactions'=>$order_transactions,
                'search'=>$request->search??null,
                'from'=>(($filter == 'custom') && $from)?$from:null,
                'to'=>(($filter == 'custom') && $to)?$to:null,
                'zone'=>is_numeric($zone_id)?Helpers::get_zones_name($zone_id):null,
                'store'=>is_numeric($store_id)?Helpers::get_stores_name($store_id):null,
                'module'=>request('module_id')?Helpers::get_module_name(request('module_id')):null,
                'admin_earned'=>$admin_earned + $admin_earned_delivery_commission,
                'store_earned'=>$store_earned,
                'deliveryman_earned'=>$deliveryman_earned,
                'delivered'=>$delivered,
                'canceled'=>$canceled,
                'filter'=>$filter,
            ];

        if ($request->type == 'excel') {
            return Excel::download(new TransactionReportExport($data), 'TransactionReport.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new TransactionReportExport($data), 'TransactionReport.csv');
        }
    }

    public function item_wise_report(Request $request)
    {

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');
        $category_id = $request->query('category_id', 'all');
        $filter = $request->query('filter', 'all_time');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;
        $category = is_numeric($category_id) ? Category::findOrFail($category_id) : null;
        $items = $this->get_item_data($request);
        $items =  $items->paginate(config('default_pagination'))->withQueryString();
        return view('admin-views.report.item-wise-report', compact('zone', 'store', 'category', 'items', 'filter'));
    }
    public function item_wise_export(Request $request)
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', now()->firstOfMonth()->format('Y-m-d'));
            session()->put('to_date', now()->lastOfMonth()->format('Y-m-d'));
        }
        $from = session('from_date');
        $to = session('to_date');

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');
        $category_id = $request->query('category_id', 'all');
        $filter = $request->query('filter', 'all_time');
        $items = $this->get_item_data($request);
        $items =  $items->get();

        $data = [
            'items'=>$items,
            'search'=>$request->search??null,
            'from'=>(($filter == 'custom') && $from)?$from:null,
            'to'=>(($filter == 'custom') && $to)?$to:null,
            'zone'=>is_numeric($zone_id)?Helpers::get_zones_name($zone_id):null,
            'store'=>is_numeric($store_id)?Helpers::get_stores_name($store_id):null,
            'category'=>is_numeric($category_id)?Helpers::get_category_name($category_id):null,
            'module'=>request('module_id')?Helpers::get_module_name(request('module_id')):null,
            'filter'=>$filter,
        ];

        if ($request->type == 'excel') {
            return Excel::download(new ItemReportExport($data), 'ItemReport.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new ItemReportExport($data), 'ItemReport.csv');
        }
    }


    private static function get_item_data($request){

        $key = explode(' ', $request['search']);
        if (session()->has('from_date') == false) {
            session()->put('from_date', now()->firstOfMonth()->format('Y-m-d'));
            session()->put('to_date', now()->lastOfMonth()->format('Y-m-d'));
        }
        $from = session('from_date');
        $to = session('to_date');

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');
        $category_id = $request->query('category_id', 'all');
        $filter = $request->query('filter', 'all_time');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;
        $category = is_numeric($category_id) ? Category::findOrFail($category_id) : null;

        $items = Item::withoutGlobalScope(StoreScope::class)
        ->leftJoin('order_details', 'order_details.item_id', '=', 'items.id')
        ->leftJoin('orders', function ($join) {
            $join->on('orders.id', '=', 'order_details.order_id')
                ->whereIn('orders.order_status', ['delivered', 'refund_requested', 'refund_request_canceled']);
        })
        ->select('items.*')
        ->selectRaw("
            COUNT(DISTINCT orders.id) as orders_count,
            SUM(order_details.quantity) as orders_sum_quantity,
            SUM(order_details.price * order_details.quantity) as orders_sum_price,
            SUM(order_details.discount_on_item * order_details.quantity) as total_discount
        ")
        ->when(isset($from, $to) && $from && $to && $filter == 'custom', function ($q) use ($from, $to) {
            $q->whereBetween('order_details.created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
        })
        ->when($filter == 'this_year', fn($q) => $q->whereYear('order_details.created_at', now()->year))
        ->when($filter == 'this_month', fn($q) => $q->whereYear('order_details.created_at', now()->year)
                                                    ->whereMonth('order_details.created_at', now()->month))
        ->when($filter == 'previous_year', fn($q) => $q->whereYear('order_details.created_at', now()->subYear()->year))
        ->when($filter == 'this_week', fn($q) => $q->whereBetween('order_details.created_at', [now()->startOfWeek(), now()->endOfWeek()]))
        ->when($request->query('module_id', null), fn($q) => $q->where('items.module_id', $request->query('module_id')))
        ->when(isset($zone), fn($q) => $q->whereIn('items.store_id', $zone->stores->pluck('id')))
        ->when(isset($store), fn($q) => $q->where('items.store_id', $store->id))
        ->when(isset($category), fn($q) => $q->where('items.category_id', $category->id))
        ->when(isset($key), fn($q) => $q->where(function ($q2) use ($key) {
            foreach ($key as $value) {
                $q2->orWhere('items.name', 'like', "%{$value}%");
            }
        }))
        ->with('module', 'store')
        ->groupBy('items.id')
        ->having('orders_count', '>', 0)
        ->orderByDesc('orders_count');

        return $items;
    }

    public function order_transaction()
    {
        $order_transactions = OrderTransaction::latest()->paginate(config('default_pagination'));
        return view('admin-views.report.order-transactions', compact('order_transactions'));
    }


    public function set_date(Request $request)
    {
        session()->put('from_date', date('Y-m-d', strtotime($request['from'])));
        session()->put('to_date', date('Y-m-d', strtotime($request['to'])));
        return back();
    }

    public function item_search(Request $request)
    {
        $key = explode(' ', $request['search']);

        if (session()->has('from_date') == false) {
            session()->put('from_date', now()->firstOfMonth()->format('Y-m-d'));
            session()->put('to_date', now()->lastOfMonth()->format('Y-m-d'));
        }
        $from = session('from_date');
        $to = session('to_date');

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');
        $category_id = $request->query('category_id', 'all');
        $filter = $request->query('filter', 'all_time');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;
        $category = is_numeric($category_id) ? Category::findOrFail($category_id) : null;
        $items = \App\Models\Item::withoutGlobalScope(StoreScope::class)
        ->withCount([
            'orders' => function ($query) use ($from, $to, $filter) {
                $query->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                    return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
                })
                    ->when(isset($filter) && $filter == 'this_year', function ($query) {
                        return $query->whereYear('created_at', now()->format('Y'));
                    })
                    ->when(isset($filter) && $filter == 'this_month', function ($query) {
                        return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                    })
                    ->when(isset($filter) && $filter == 'this_month', function ($query) {
                        return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                    })
                    ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                        return $query->whereYear('created_at', date('Y') - 1);
                    })
                    ->when(isset($filter) && $filter == 'this_week', function ($query) {
                        return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
                    })
                    ->when(isset($filter) && $filter == 'all_time', function ($query) {
                        return $query;
                    })
                    ->whereHas('order', function ($query) {
                        return $query->whereIn('order_status', ['delivered', 'refund_requested', 'refund_request_canceled']);
                    });
            },
        ])
        ->withSum([
            'orders' => function ($query) use ($from, $to, $filter) {
                $query->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                    return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
                })
                    ->when(isset($filter) && $filter == 'this_year', function ($query) {
                        return $query->whereYear('created_at', now()->format('Y'));
                    })
                    ->when(isset($filter) && $filter == 'this_month', function ($query) {
                        return $query->whereMonth('created_at', now()->format('m'));
                    })
                    ->when(isset($filter) && $filter == 'this_month', function ($query) {
                        return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                    })
                    ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                        return $query->whereYear('created_at', date('Y') - 1);
                    })
                    ->when(isset($filter) && $filter == 'this_week', function ($query) {
                        return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
                    })
                    ->when(isset($filter) && $filter == 'all_time', function ($query) {
                        return $query;
                    })
                    ->whereHas('order', function ($query) {
                        return $query->whereIn('order_status', ['delivered', 'refund_requested', 'refund_request_canceled']);
                    });
            },
        ], 'discount_on_item')
        ->withSum([
            'orders' => function ($query) use ($from, $to, $filter) {
                $query->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                    return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
                })
                    ->when(isset($filter) && $filter == 'this_year', function ($query) {
                        return $query->whereYear('created_at', now()->format('Y'));
                    })
                    ->when(isset($filter) && $filter == 'this_month', function ($query) {
                        return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                    })
                    ->when(isset($filter) && $filter == 'this_month', function ($query) {
                        return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                    })
                    ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                        return $query->whereYear('created_at', date('Y') - 1);
                    })
                    ->when(isset($filter) && $filter == 'this_week', function ($query) {
                        return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
                    })
                    ->when(isset($filter) && $filter == 'all_time', function ($query) {
                        return $query;
                    })
                    ->whereHas('order', function ($query) {
                        return $query->whereIn('order_status', ['delivered', 'refund_requested', 'refund_request_canceled']);
                    });
            },
        ], 'price')
        ->when($request->query('module_id', null), function ($query) use ($request) {
            return $query->module($request->query('module_id'));
        })
        ->when(isset($zone), function ($query) use ($zone) {
            return $query->whereIn('store_id', $zone->stores->pluck('id'));
        })
        ->when(isset($store), function ($query) use ($store) {
            return $query->where('store_id', $store->id);
        })
        ->when(isset($category), function ($query) use ($category) {
            return $query->where('category_id', $category->id);
        })
        ->with('module', 'store')
        ->where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('name', 'like', "%{$value}%");
            }
        })
        ->limit(25)->get();

        return response()->json([
            'count' => count($items),
            'view' => view('admin-views.report.partials._item_table', compact('items'))->render()
        ]);
    }


    public function store_summary_report(Request $request)
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

        $stores = Store::query()

        ->when(isset($key), function ($query) use ($key) {
            $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%");
                }
            });
        })

        ->withCount([
            'orders as total_orders' => function ($q) use ($filter) {

                $q->StoreOrder()

                    ->when($filter == 'this_year', function ($q) {
                        $q->whereYear('schedule_at', now()->year);
                    })
                    ->when($filter == 'this_month', function ($q) {
                        $q->whereYear('schedule_at', now()->year)
                        ->whereMonth('schedule_at', now()->month);
                    })
                    ->when($filter == 'previous_year', function ($q) {
                        $q->whereYear('schedule_at', now()->subYear()->year);
                    })
                    ->when($filter == 'this_week', function ($q) {
                        $q->whereBetween('schedule_at', [
                            now()->startOfWeek(),
                            now()->endOfWeek()
                        ]);
                    });
            },

            'orders as delivered' => function ($q) use ($filter) {
                $q->StoreOrder()->where('order_status','delivered')
                    ->when($filter == 'this_year', fn($q)=>$q->whereYear('schedule_at', now()->year))
                    ->when($filter == 'this_month', fn($q)=>$q->whereYear('schedule_at', now()->year)->whereMonth('schedule_at', now()->month))
                    ->when($filter == 'previous_year', fn($q)=>$q->whereYear('schedule_at', now()->subYear()->year))
                    ->when($filter == 'this_week', fn($q)=>$q->whereBetween('schedule_at',[now()->startOfWeek(),now()->endOfWeek()]));
            },

            'orders as canceled' => function ($q) use ($filter) {
                $q->StoreOrder()->where('order_status','canceled')
                    ->when($filter == 'this_year', fn($q)=>$q->whereYear('schedule_at', now()->year))
                    ->when($filter == 'this_month', fn($q)=>$q->whereYear('schedule_at', now()->year)->whereMonth('schedule_at', now()->month))
                    ->when($filter == 'previous_year', fn($q)=>$q->whereYear('schedule_at', now()->subYear()->year))
                    ->when($filter == 'this_week', fn($q)=>$q->whereBetween('schedule_at',[now()->startOfWeek(),now()->endOfWeek()]));
            },

            'orders as refunded' => function ($q) use ($filter) {
                $q->StoreOrder()->where('order_status','refunded')
                    ->when($filter == 'this_year', fn($q)=>$q->whereYear('schedule_at', now()->year))
                    ->when($filter == 'this_month', fn($q)=>$q->whereYear('schedule_at', now()->year)->whereMonth('schedule_at', now()->month))
                    ->when($filter == 'previous_year', fn($q)=>$q->whereYear('schedule_at', now()->subYear()->year))
                    ->when($filter == 'this_week', fn($q)=>$q->whereBetween('schedule_at',[now()->startOfWeek(),now()->endOfWeek()]));
            },

            'orders as refund_requested' => function ($q) use ($filter) {
                $q->StoreOrder()->whereNotNull('refund_requested')
                    ->when($filter == 'this_year', fn($q)=>$q->whereYear('schedule_at', now()->year))
                    ->when($filter == 'this_month', fn($q)=>$q->whereYear('schedule_at', now()->year)->whereMonth('schedule_at', now()->month))
                    ->when($filter == 'previous_year', fn($q)=>$q->whereYear('schedule_at', now()->subYear()->year))
                    ->when($filter == 'this_week', fn($q)=>$q->whereBetween('schedule_at',[now()->startOfWeek(),now()->endOfWeek()]));
            }

        ])
        ->withSum([
        'orders as delivered_amount' => function ($q) use ($filter) {

            $q->StoreOrder()
            ->where('order_status','delivered')

            ->when($filter == 'this_year', fn($q)=>$q->whereYear('schedule_at', now()->year))
            ->when($filter == 'this_month', fn($q)=>$q->whereYear('schedule_at', now()->year)
                                                    ->whereMonth('schedule_at', now()->month))
            ->when($filter == 'previous_year', fn($q)=>$q->whereYear('schedule_at', now()->subYear()->year))
            ->when($filter == 'this_week', fn($q)=>$q->whereBetween('schedule_at',[
                                                        now()->startOfWeek(),
                                                        now()->endOfWeek()
                                                    ]));
                        }
                    ], 'order_amount')

        ->orderByDesc('total_orders')
        ->paginate(config('default_pagination'));


        $new_stores = Store::when(isset($filter) && $filter == 'this_year', function ($query) {
            return $query->whereYear('created_at', now()->format('Y'));
        })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('created_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })->count();

        $order_payment_methods = Order::when(isset($filter) && $filter == 'this_year', function ($query) {
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
            ->StoreOrder()->Delivered()->NotRefunded()
            ->selectRaw(DB::raw("sum(`order_amount`) as total_order_amount, count(*) as order_count, IF((`payment_method`='cash_on_delivery'), `payment_method`, IF(`payment_method`='wallet',`payment_method`, 'digital_payment')) as 'payment_methods'"))->groupBy('payment_methods')
            ->get();


        $result = Order::when(isset($filter) && $filter == 'this_year', function ($query) {
            $query->whereYear('schedule_at', now()->year);
        })
        ->when(isset($filter) && $filter == 'this_month', function ($query) {
            $query->whereYear('schedule_at', now()->year)
                ->whereMonth('schedule_at', now()->month);
        })
        ->when(isset($filter) && $filter == 'previous_year', function ($query) {
            $query->whereYear('schedule_at', now()->subYear()->year);
        })
        ->when(isset($filter) && $filter == 'this_week', function ($query) {
            $query->whereBetween('schedule_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ]);
        })
        ->StoreOrder()
        ->selectRaw("
            COUNT(*) as total_orders,

            SUM(CASE
                WHEN order_status = 'delivered'
                THEN order_amount
                ELSE 0
            END) as total_order_amount,

            SUM(CASE
                WHEN order_status IN ('pending','accepted','confirmed','processing','handover','picked_up')
                THEN 1 ELSE 0
            END) as total_ongoing,

            SUM(CASE
                WHEN order_status IN ('failed','canceled')
                THEN 1 ELSE 0
            END) as total_canceled,

            SUM(CASE
                WHEN order_status = 'delivered'
                THEN 1 ELSE 0
            END) as total_delivered
        ")
        ->first();

        $total_order_amount = $result->total_order_amount;
        $total_ongoing      = $result->total_ongoing;
        $total_canceled     = $result->total_canceled;
        $total_delivered    = $result->total_delivered;
        $total_orders       = $result->total_orders;

        $items = Item::when(isset($filter) && $filter == 'this_year', function ($query) {
            return $query->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('created_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })->count();

        $monthly_order = [];
        switch ($filter) {
            case "all_time":
                $monthly_order = Order::select(
                    DB::raw("(sum(order_amount)) as order_amount"),
                    DB::raw("(DATE_FORMAT(schedule_at, '%Y')) as year")
                )
                    ->StoreOrder()->Delivered()->NotRefunded()
                    ->groupBy(DB::raw("DATE_FORMAT(schedule_at, '%Y')"))
                    ->get()->toArray();

                $label = array_map(function ($order) {
                    return $order['year'];
                }, $monthly_order);
                $data = array_map(function ($order) {
                    return $order['order_amount'];
                }, $monthly_order);
                break;
            case "this_year":
                for ($i = 1; $i <= 12; $i++) {
                    $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->whereMonth('schedule_at', $i)->whereYear('schedule_at', now()->format('Y'))
                        ->sum('order_amount');
                }
                $label = $months;
                $data = $monthly_order;
                break;
            case "previous_year":
                for ($i = 1; $i <= 12; $i++) {
                    $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->whereMonth('schedule_at', $i)->whereYear('schedule_at', date('Y') - 1)
                        ->sum('order_amount');
                }
                $label = $months;
                $data = $monthly_order;
                break;
            case "this_week":
                $weekStartDate = now()->startOfWeek();
                for ($i = 1; $i <= 7; $i++) {
                    $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->whereDay('schedule_at', $weekStartDate->format('d'))->whereMonth('schedule_at', now()->format('m'))
                        ->sum('order_amount');

                    $weekStartDate = $weekStartDate->addDays(1);
                }
                $label = $days;
                $data = $monthly_order;
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
                    $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()
                        ->whereBetween('schedule_at', ["{$start->format('Y-m-d')} 00:00:00", "{$end->format('Y-m-d')} 23:59:59"])
                        ->sum('order_amount');
                    $start = $start->addDays(7);
                    $end = $i == 3 ? $end->addDays(7 + $remaining_days) : $end->addDays(7);
                }
                $label = $weeks;
                $data = $monthly_order;
                break;
            default:
                for ($i = 1; $i <= 12; $i++) {
                    $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->whereMonth('schedule_at', $i)->whereYear('schedule_at', now()->format('Y'))
                        ->sum('order_amount');
                }
                $label = $months;
                $data = $monthly_order;
        }

        return view('admin-views.report.store-summary-report', compact('stores', 'new_stores', 'total_orders', 'order_payment_methods', 'items', 'monthly_order', 'label', 'data', 'filter', 'total_order_amount', 'total_ongoing', 'total_canceled', 'total_delivered'));
    }

    public function store_summary_search(Request $request)
    {
        $key = explode(' ', $request['search']);

        $filter = $request->query('filter', 'all_time');

        $stores = Store::with('orders')
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->StoreOrder()->whereYear('schedule_at', now()->format('Y'));
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->StoreOrder()->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->StoreOrder()->whereYear('schedule_at', date('Y') - 1);
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->StoreOrder()->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'all_time', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->StoreOrder();
                    },
                ]);
            })
            ->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%");
                }
            })->Active()
            ->limit(25)->get();

        return response()->json([
            'count' => count($stores),
            'view' => view('admin-views.report.partials._store_summary_table', compact('stores'))->render()
        ]);
    }

    public function store_sales_report(Request $request)
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
        $store_id = $request->query('store_id', 'all');
        $filter = $request->query('filter', 'all_time');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;

        // items

        $data_array= $this->get_store_sales_data($request);

        $items=$data_array['items'];
        $items= $items->paginate(config('default_pagination'))->withQueryString();

        $orders=$data_array['orders'];


        // custom filtering for bar chart
        $monthly_order = [];
        $label = [];
        $data = [];
        if ($filter != 'custom') {
            switch ($filter) {
                case "all_time":
                    $monthly_order = Order::StoreOrder()->Delivered()->NotRefunded()->when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('store_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($store), function ($query) use ($store) {
                            return $query->where('store_id', $store->id);
                        })->select(
                            DB::raw("(sum(order_amount)) as order_amount"),
                            DB::raw("(DATE_FORMAT(schedule_at, '%Y')) as year")
                        )
                        ->groupBy(DB::raw("DATE_FORMAT(schedule_at, '%Y')"))
                        ->get()->toArray();

                    $label = array_map(function ($order) {
                        return $order['year'];
                    }, $monthly_order);
                    $data = array_map(function ($order) {
                        return $order['order_amount'];
                    }, $monthly_order);
                    break;
                case "this_year":
                    for ($i = 1; $i <= 12; $i++) {
                        $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('store_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($store), function ($query) use ($store) {
                                return $query->where('store_id', $store->id);
                            })->whereMonth('schedule_at', $i)->whereYear('schedule_at', now()->format('Y'))
                            ->sum('order_amount');
                    }
                    $label = $months;
                    $data = $monthly_order;
                    break;
                case "previous_year":
                    for ($i = 1; $i <= 12; $i++) {
                        $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('store_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($store), function ($query) use ($store) {
                                return $query->where('store_id', $store->id);
                            })->whereMonth('schedule_at', $i)->whereYear('schedule_at', date('Y') - 1)
                            ->sum('order_amount');
                    }
                    $label = $months;
                    $data = $monthly_order;
                    break;
                case "this_week":
                    $weekStartDate = now()->startOfWeek();
                    for ($i = 1; $i <= 7; $i++) {
                        $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('store_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($store), function ($query) use ($store) {
                                return $query->where('store_id', $store->id);
                            })->whereDay('schedule_at', $weekStartDate->format('d'))->whereMonth('schedule_at', now()->format('m'))
                            ->sum('order_amount');
                        $weekStartDate = $weekStartDate->addDays(1);
                    }
                    $label = $days;
                    $data = $monthly_order;
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
                        $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('store_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($store), function ($query) use ($store) {
                                return $query->where('store_id', $store->id);
                            })
                            ->whereBetween('schedule_at', ["{$start->format('Y-m-d')} 00:00:00", "{$end->format('Y-m-d')} 23:59:59"])
                            ->sum('order_amount');
                        $start = $start->addDays(7);
                        $end = $i == 3 ? $end->addDays(7 + $remaining_days) : $end->addDays(7);
                    }
                    $label = $weeks;
                    $data = $monthly_order;
                    break;
                default:
                    for ($i = 1; $i <= 12; $i++) {
                        $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('store_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($store), function ($query) use ($store) {
                                return $query->where('store_id', $store->id);
                            })->whereMonth('schedule_at', $i)->whereYear('schedule_at', now()->format('Y'))
                            ->sum('order_amount');
                    }
                    $label = $months;
                    $data = $monthly_order;
            }
        } else {

            $to = Carbon::parse($to);
            $from = Carbon::parse($from);

            $years_count = $to->diffInYears($from);
            $months_count = $to->diffInMonths($from);
            $weeks_count = $to->diffInWeeks($from);
            $days_count = $to->diffInDays($from);

            if ($years_count > 0) {
                $monthly_order = Order::StoreOrder()->Delivered()->NotRefunded()->when(isset($zone), function ($query) use ($zone) {
                    return $query->whereIn('store_id', $zone->stores->pluck('id'));
                })
                    ->when(isset($store), function ($query) use ($store) {
                        return $query->where('store_id', $store->id);
                    })
                    ->whereBetween('schedule_at', ["{$from}", "{$to->format('Y-m-d')} 23:59:59"])
                    ->select(
                        DB::raw("(sum(order_amount)) as order_amount"),
                        DB::raw("(DATE_FORMAT(schedule_at, '%Y')) as year")
                    )
                    ->groupBy('year')
                    ->get()->toArray();

                $label = array_map(function ($order) {
                    return $order['year'];
                }, $monthly_order);
                $data = array_map(function ($order) {
                    return $order['order_amount'];
                }, $monthly_order);
            } elseif ($months_count > 0) {
                for ($i = (int)$from->format('m'); $i <= (int)$from->format('m') + $months_count; $i++) {
                    $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('store_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($store), function ($query) use ($store) {
                            return $query->where('store_id', $store->id);
                        })->whereMonth('schedule_at', $i)
                        ->sum('order_amount');
                    $label[$i] = $months[$i - 1];
                }
                $label = $label;
                $data = $monthly_order;
            } elseif ($weeks_count > 0) {
                for ($i = (int)$from->format('d'); $i <= (int)$to->format('d'); $i++) {
                    $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('store_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($store), function ($query) use ($store) {
                            return $query->where('store_id', $store->id);
                        })->whereDay('schedule_at', $i)->whereMonth('schedule_at', $from->format('m'))->whereYear('schedule_at', $from->format('Y'))
                        ->sum('order_amount');
                    $label[$i] = $i;
                }
                $label = $label;
                $data = $monthly_order;
            } elseif ($days_count >= 0) {
                for ($i = (int)$from->format('d'); $i <= (int)$to->format('d'); $i++) {
                    $monthly_order[$i] = Order::StoreOrder()->Delivered()->NotRefunded()->when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('store_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($store), function ($query) use ($store) {
                            return $query->where('store_id', $store->id);
                        })->whereDay('schedule_at', $i)->whereMonth('schedule_at', $from->format('m'))->whereYear('schedule_at', $from->format('Y'))
                        ->sum('order_amount');
                    $label[$i] = $i;
                }
                $label = $label;
                $data = $monthly_order;
            }
        }

        return view('admin-views.report.store-sales-report', compact('zone', 'store', 'items', 'orders', 'data', 'label', 'filter'));
    }


    public function store_sales_export(Request $request)
    {
        $from = session('from_date');
        $to = session('to_date');
        $filter = $request->query('filter', 'all_time');

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');


        $data= $this->get_store_sales_data($request);
        $items=$data['items'];
        $items= $items->get();
        $orders=$data['orders'];

            $data = [
                'items'=>$items,
                'orders'=>$orders,
                'search'=>$request->search??null,
                'from'=>(($filter == 'custom') && $from)?$from:null,
                'to'=>(($filter == 'custom') && $to)?$to:null,
                'zone'=>is_numeric($zone_id)?Helpers::get_zones_name($zone_id):null,
                'store'=>is_numeric($store_id)?Helpers::get_stores_name($store_id):null,
                'filter'=>$filter,
            ];
        if ($request->type == 'excel') {
            return Excel::download(new StoreSalesReportExport($data), 'StoreSalesReport.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new StoreSalesReportExport($data), 'StoreSalesReport.csv');
        }
    }


    private static function get_store_sales_data($request){
        if (session()->has('from_date') == false) {
            session()->put('from_date', now()->firstOfMonth()->format('Y-m-d'));
            session()->put('to_date', now()->lastOfMonth()->format('Y-m-d'));
        }
        $from = session('from_date');
        $to = session('to_date');
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];
        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');
        $filter = $request->query('filter', 'all_time');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;



            $items = Item::withoutGlobalScope(StoreScope::class)
            ->when(isset($zone), fn($q) => $q->whereIn('items.store_id', $zone->stores()->pluck('id')))
            ->when(isset($store), fn($q) => $q->where('items.store_id', $store->id))
            ->when(isset($key), function ($q) use ($key) {
                $q->where(function ($sub) use ($key) {
                    foreach ($key as $value) {
                        $sub->orWhere('items.name', 'like', "%{$value}%");
                    }
                });
            })
            ->leftJoin('order_details', 'order_details.item_id', '=', 'items.id')
            ->leftJoin('orders', function ($join) use ($from, $to, $filter) {
                $join->on('orders.id', '=', 'order_details.order_id')
                ->whereIn('orders.order_status', ['delivered','refund_requested','refund_request_canceled']);

        // Apply date filter
        if ($filter === 'this_year') {
            $join->whereRaw("YEAR(orders.schedule_at) = ?", [now()->year]);
        } elseif ($filter === 'this_month') {
            $join->whereRaw("YEAR(orders.schedule_at) = ? AND MONTH(orders.schedule_at) = ?", [now()->year, now()->month]);
        } elseif ($filter === 'previous_year') {
            $join->whereRaw("YEAR(orders.schedule_at) = ?", [now()->subYear()->year]);
        } elseif ($filter === 'this_week') {
            $join->whereBetween('orders.schedule_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($filter === 'custom' && $from && $to) {
            $join->whereBetween('orders.schedule_at', [$from . " 00:00:00", $to . " 23:59:59"]);
        }
        })
        ->select('items.*')
        ->selectRaw("
            COUNT(DISTINCT orders.id) as orders_count,
            SUM(order_details.quantity) as orders_sum_quantity,
            SUM(order_details.price * order_details.quantity) as orders_sum_price,
            SUM(order_details.discount_on_item * order_details.quantity) as total_discount
        ")
        ->groupBy('items.id')
        ->having('orders_count', '>', 0)
        ->orderByDesc('orders_count');

        $orders = Order::StoreOrder()
            ->whereNotIn('orders.order_status', ['refunded', 'failed', 'canceled'])
            ->Delivered()
            ->when(isset($zone), fn($q) => $q->whereIn('orders.store_id', $zone->stores()->pluck('id')))
            ->when(isset($store), fn($q) => $q->where('orders.store_id', $store->id))
            ->when(isset($from, $to) && $from && $to && $filter == 'custom', fn($q) =>
                $q->whereBetween('orders.schedule_at', [$from . " 00:00:00", $to . " 23:59:59"])
            )
            ->when($filter == 'this_year', fn($q) => $q->whereYear('orders.schedule_at', now()->year))
            ->when($filter == 'this_month', fn($q) => $q->whereYear('orders.schedule_at', now()->year)
                                                        ->whereMonth('orders.schedule_at', now()->month))
            ->when($filter == 'previous_year', fn($q) => $q->whereYear('orders.schedule_at', now()->subYear()->year))
            ->when($filter == 'this_week', fn($q) => $q->whereBetween('orders.schedule_at', [now()->startOfWeek(), now()->endOfWeek()]))

            ->leftJoin('order_transactions', 'order_transactions.order_id', '=', 'orders.id')

            ->selectRaw("
                SUM(orders.order_amount) as total_order_amount,
                SUM(orders.total_tax_amount) as total_tax_amount,
                COUNT(*) as total_order,
                SUM(order_transactions.admin_commission) as transaction_sum_admin_commission,
                SUM(order_transactions.delivery_fee_comission) as transaction_sum_delivery_fee_comission,
                SUM(order_transactions.admin_expense) as transaction_sum_admin_expense,
                SUM(order_transactions.store_amount) as transaction_sum_store_amount
            ")
            ->first();


        return ['items'=> $items , 'orders'=> $orders];
    }







    public function store_order_report(Request $request)
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', now()->firstOfMonth()->format('Y-m-d'));
            session()->put('to_date', now()->lastOfMonth()->format('Y-m-d'));
        }
        $from = session('from_date');
        $to = session('to_date');
        $data=[];
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
        $store_id = $request->query('store_id', 'all');
        $filter = $request->query('filter', 'all_time');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;

        // order list with pagination
        $orders = Order::with(['customer', 'store'])
            ->when(isset($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('id', 'like', "%{$value}%");
                    }
                });
            })
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('store_id', $zone->stores->pluck('id'));
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
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
            ->StoreOrder()->NotRefunded()
            ->withSum('transaction', 'admin_commission')
            ->withSum('transaction', 'admin_expense')
            ->withSum('transaction', 'delivery_fee_comission')
            ->orderBy('schedule_at', 'desc')->paginate(config('default_pagination'));


        $orders_summary = Order::StoreOrder()
            ->NotRefunded()
            ->when(isset($zone), fn($q) => $q->whereIn('orders.store_id', $zone->stores->pluck('id')))
            ->when(isset($store), fn($q) => $q->where('orders.store_id', $store->id))
            ->when(isset($from, $to) && $from && $to && $filter == 'custom', fn($q) =>
                $q->whereBetween('orders.schedule_at', [$from . " 00:00:00", $to . " 23:59:59"])
            )
            ->when($filter == 'this_year', fn($q) => $q->whereYear('orders.schedule_at', now()->year))
            ->when($filter == 'this_month', fn($q) => $q->whereYear('orders.schedule_at', now()->year)
                                                        ->whereMonth('orders.schedule_at', now()->month))
            ->when($filter == 'previous_year', fn($q) => $q->whereYear('orders.schedule_at', now()->subYear()->year))
            ->when($filter == 'this_week', fn($q) => $q->whereBetween('orders.schedule_at', [now()->startOfWeek(), now()->endOfWeek()]))
            ->leftJoin('order_transactions', 'order_transactions.order_id', '=', 'orders.id')
            ->selectRaw("
                COUNT(*) as total_orders_count,
                SUM(orders.order_amount) as total_order_amount,
                SUM(orders.coupon_discount_amount) as total_coupon_discount,
                SUM(orders.store_discount_amount) as total_product_discount,
                SUM(CASE WHEN orders.order_status IN ('pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up') THEN orders.order_amount ELSE 0 END) as total_ongoing,
                SUM(CASE WHEN orders.order_status IN ('failed', 'canceled') THEN orders.order_amount ELSE 0 END) as total_canceled,
                SUM(CASE WHEN orders.order_status = 'delivered' THEN orders.order_amount ELSE 0 END) as total_delivered,
                SUM(CASE WHEN orders.order_status IN ('pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up') THEN 1 ELSE 0 END) as total_ongoing_count,
                SUM(CASE WHEN orders.order_status IN ('failed', 'canceled') THEN 1 ELSE 0 END) as total_canceled_count,
                SUM(CASE WHEN orders.order_status = 'delivered' THEN 1 ELSE 0 END) as total_delivered_count,
                SUM(order_transactions.admin_commission) as transaction_sum_admin_commission,
                SUM(order_transactions.delivery_fee_comission) as transaction_sum_delivery_fee_comission,
                SUM(order_transactions.admin_expense) as transaction_sum_admin_expense
            ")
            ->first();

        $total_order_amount      = $orders_summary->total_order_amount;
        $total_coupon_discount   = $orders_summary->total_coupon_discount;
        $total_product_discount  = $orders_summary->total_product_discount;

        $total_ongoing           = $orders_summary->total_ongoing;
        $total_canceled          = $orders_summary->total_canceled;
        $total_delivered         = $orders_summary->total_delivered;

        $total_ongoing_count     = $orders_summary->total_ongoing_count;
        $total_canceled_count    = $orders_summary->total_canceled_count;
        $total_delivered_count   = $orders_summary->total_delivered_count;
        // payment type statistics
        $order_payment_methods = Order::when(isset($zone), function ($query) use ($zone) {
            return $query->whereIn('store_id', $zone->stores->pluck('id'));
        })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
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
            ->StoreOrder()->NotRefunded()
            ->selectRaw(DB::raw("sum(`order_amount`) as total_order_amount, count(*) as order_count, IF((`payment_method`='cash_on_delivery'), `payment_method`, IF(`payment_method`='wallet',`payment_method`, 'digital_payment')) as 'payment_methods'"))
            ->groupBy('payment_methods')
            ->get();

        // custom filtering for bar chart
        $monthly_order = [];
        $label = [];
        if ($filter != 'custom') {
            switch ($filter) {
                case "all_time":
                    $monthly_order = Order::when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('store_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($store), function ($query) use ($store) {
                            return $query->where('store_id', $store->id);
                        })
                        ->StoreOrder()->NotRefunded()
                        ->select(
                            DB::raw("(sum(order_amount)) as order_amount"),
                            DB::raw("(DATE_FORMAT(schedule_at, '%Y')) as year")
                        )
                        ->groupBy(DB::raw("DATE_FORMAT(schedule_at, '%Y')"))
                        ->get()->toArray();

                    $label = array_map(function ($order) {
                        return $order['year'];
                    }, $monthly_order);
                    $data = array_map(function ($order) {
                        return $order['order_amount'];
                    }, $monthly_order);
                    break;
                case "this_year":
                    for ($i = 1; $i <= 12; $i++) {
                        $monthly_order[$i] = Order::when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('store_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($store), function ($query) use ($store) {
                                return $query->where('store_id', $store->id);
                            })
                            ->StoreOrder()->NotRefunded()
                            ->whereMonth('schedule_at', $i)->whereYear('schedule_at', now()->format('Y'))
                            ->sum('order_amount');
                    }
                    $label = $months;
                    $data = $monthly_order;
                    break;
                case "previous_year":
                    for ($i = 1; $i <= 12; $i++) {
                        $monthly_order[$i] = Order::when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('store_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($store), function ($query) use ($store) {
                                return $query->where('store_id', $store->id);
                            })
                            ->StoreOrder()->NotRefunded()
                            ->whereMonth('schedule_at', $i)->whereYear('schedule_at', date('Y') - 1)
                            ->sum('order_amount');
                    }
                    $label = $months;
                    $data = $monthly_order;
                    break;
                case "this_week":
                    $weekStartDate = now()->startOfWeek();
                    for ($i = 1; $i <= 7; $i++) {
                        $monthly_order[$i] = Order::when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('store_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($store), function ($query) use ($store) {
                                return $query->where('store_id', $store->id);
                            })->StoreOrder()->NotRefunded()->whereDay('schedule_at', $weekStartDate->format('d'))->whereMonth('schedule_at', now()->format('m'))
                            ->sum('order_amount');
                        $weekStartDate = $weekStartDate->addDays(1);
                    }
                    $label = $days;
                    $data = $monthly_order;
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
                        $monthly_order[$i] = Order::when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('store_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($store), function ($query) use ($store) {
                                return $query->where('store_id', $store->id);
                            })
                            ->StoreOrder()->NotRefunded()
                            ->whereBetween('schedule_at', ["{$start->format('Y-m-d')} 00:00:00", "{$end->format('Y-m-d')} 23:59:59"])
                            ->sum('order_amount');
                        $start = $start->addDays(7);
                        $end = $i == 3 ? $end->addDays(7 + $remaining_days) : $end->addDays(7);
                    }
                    $label = $weeks;
                    $data = $monthly_order;
                    break;
                default:
                    for ($i = 1; $i <= 12; $i++) {
                        $monthly_order[$i] = Order::when(isset($zone), function ($query) use ($zone) {
                            return $query->whereIn('store_id', $zone->stores->pluck('id'));
                        })
                            ->when(isset($store), function ($query) use ($store) {
                                return $query->where('store_id', $store->id);
                            })->StoreOrder()->NotRefunded()->whereMonth('schedule_at', $i)->whereYear('schedule_at', now()->format('Y'))
                            ->sum('order_amount');
                    }
                    $label = $months;
                    $data = $monthly_order;
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
                $monthly_order = Order::when(isset($zone), function ($query) use ($zone) {
                    return $query->whereIn('store_id', $zone->stores->pluck('id'));
                })
                    ->when(isset($store), function ($query) use ($store) {
                        return $query->where('store_id', $store->id);
                    })
                    ->StoreOrder()->NotRefunded()
                    ->whereBetween('schedule_at', ["{$from}", "{$to->format('Y-m-d')} 23:59:59"])
                    ->select(
                        DB::raw("(sum(order_amount)) as order_amount"),
                        DB::raw("(DATE_FORMAT(schedule_at, '%Y')) as year")
                    )
                    ->groupBy('year')
                    ->get()->toArray();

                $label = array_map(function ($order) {
                    return $order['year'];
                }, $monthly_order);
                $data = array_map(function ($order) {
                    return $order['order_amount'];
                }, $monthly_order);
            } elseif ($months_count > 0) {
                for ($i = (int)$from->format('m'); $i <= (int)$from->format('m') + $months_count; $i++) {
                    $monthly_order[$i] = Order::when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('store_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($store), function ($query) use ($store) {
                            return $query->where('store_id', $store->id);
                        })
                        ->StoreOrder()->NotRefunded()
                        ->whereMonth('schedule_at', $i)
                        ->sum('order_amount');
                    $label[$i] = $months[$i - 1];
                }
                $label = $label;
                $data = $monthly_order;
            } elseif ($weeks_count > 0) {

                for ($i = (int)$from->format('d'); $i <= (int)$to->format('d'); $i++) {
                    $monthly_order[$i] = Order::when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('store_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($store), function ($query) use ($store) {
                            return $query->where('store_id', $store->id);
                        })
                        ->StoreOrder()->NotRefunded()
                        ->whereDay('schedule_at', $i)->whereMonth('schedule_at', $from->format('m'))->whereYear('schedule_at', $from->format('Y'))
                        ->sum('order_amount');
                    $label[$i] = $i;
                }
                $label = $label;
                $data = $monthly_order;
            } elseif ($days_count >= 0) {
                for ($i = (int)$from->format('d'); $i <= (int)$to->format('d'); $i++) {
                    $monthly_order[$i] = Order::when(isset($zone), function ($query) use ($zone) {
                        return $query->whereIn('store_id', $zone->stores->pluck('id'));
                    })
                        ->when(isset($store), function ($query) use ($store) {
                            return $query->where('store_id', $store->id);
                        })
                        ->StoreOrder()->NotRefunded()
                        ->whereDay('schedule_at', $i)->whereMonth('schedule_at', $from->format('m'))->whereYear('schedule_at', $from->format('Y'))
                        ->sum('order_amount');
                    $label[$i] = $i;
                }
                $label = $label;
                $data = $monthly_order;
            }
        }


        return view('admin-views.report.store-order-report', compact('zone', 'store', 'orders', 'monthly_order', 'total_order_amount', 'order_payment_methods', 'total_coupon_discount', 'total_product_discount', 'label', 'data', 'filter', 'total_ongoing', 'total_canceled', 'total_delivered', 'total_ongoing_count', 'total_canceled_count', 'total_delivered_count'));
    }

    public function store_order_search(Request $request)
    {
        $key = explode(' ', $request['search']);

        $from = session('from_date');
        $to = session('to_date');

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;

        $orders = Order::with(['customer', 'store'])
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('store_id', $zone->stores->pluck('id'));
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null, function ($query) use ($from, $to) {
                return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('id', 'like', "%{$value}%");
                }
            })
            ->withSum('transaction', 'admin_commission')
            ->withSum('transaction', 'admin_expense')
            ->withSum('transaction', 'delivery_fee_comission')
            ->StoreOrder()->NotRefunded()
            ->orderBy('schedule_at', 'desc')
            ->limit(25)->get();

        return response()->json([
            'count' => count($orders),
            'view' => view('admin-views.report.partials._store_order_table', compact('orders'))->render()
        ]);
    }

    public function store_order_export(Request $request)
    {
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];

        $from = session('from_date');
        $to = session('to_date');

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;
        $filter = $request->query('filter', 'all_time');

        $orders = Order::with(['customer', 'store'])
        ->when(isset($key), function ($query) use ($key) {
            return $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('id', 'like', "%{$value}%");
                }
            });
        })
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('store_id', $zone->stores->pluck('id'));
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
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
            ->StoreOrder()->NotRefunded()
            ->withSum('transaction', 'admin_commission')
            ->withSum('transaction', 'admin_expense')
            ->withSum('transaction', 'delivery_fee_comission')
            ->orderBy('schedule_at', 'desc')->get();

            $orders_list = Order::with(['customer', 'store'])
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('store_id', $zone->stores->pluck('id'));
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
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
            ->StoreOrder()->NotRefunded()
            ->withSum('transaction', 'admin_commission')
            ->withSum('transaction', 'admin_expense')
            ->withSum('transaction', 'delivery_fee_comission')
            ->orderBy('schedule_at', 'desc')->get();

        $total_order_amount = $orders_list->sum('order_amount');
        $total_coupon_discount = $orders_list->sum('coupon_discount_amount');
        $total_product_discount = $orders_list->sum('store_discount_amount');

        $total_ongoing = $orders_list->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->sum('order_amount');
        $total_canceled = $orders_list->whereIn('order_status', ['failed', 'canceled'])->sum('order_amount');
        $total_delivered = $orders_list->where('order_status', 'delivered')->sum('order_amount');
        $total_ongoing_count = $orders_list->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->count();
        $total_canceled_count = $orders_list->whereIn('order_status', ['failed', 'canceled'])->count();
        $total_delivered_count = $orders_list->where('order_status', 'delivered')->count();


            $data = [
                'orders'=>$orders,
                'total_orders'=>$orders->count(),
                'total_order_amount'=>$total_order_amount,
                'total_ongoing_count'=>$total_ongoing_count,
                'total_canceled_count'=>$total_canceled_count,
                'total_delivered_count'=>$total_delivered_count,
                'search'=>$request->search??null,
                'from'=>(($filter == 'custom') && $from)?$from:null,
                'to'=>(($filter == 'custom') && $to)?$to:null,
                'zone'=>is_numeric($zone_id)?Helpers::get_zones_name($zone_id):null,
                'store'=>is_numeric($store_id)?Helpers::get_stores_name($store_id):null,
                'filter'=>$filter,
            ];
        if ($request->type == 'excel') {
            return Excel::download(new StoreOrderReportExport($data), 'StoreOrderReport.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new StoreOrderReportExport($data), 'StoreOrderReport.csv');
        }
    }



    public function store_summary_export(Request $request)
    {
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];

        $filter = $request->query('filter', 'all_time');

        $stores = Store::with('orders')
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->StoreOrder()->whereYear('schedule_at', now()->format('Y'));
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->StoreOrder()->whereMonth('schedule_at', now()->format('m'))->whereYear('schedule_at', now()->format('Y'));
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->StoreOrder()->whereYear('schedule_at', date('Y') - 1);
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->StoreOrder()->whereBetween('schedule_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
                    },
                ]);
            })
            ->when(isset($filter) && $filter == 'all_time', function ($query) {
                return $query->with([
                    'orders' => function ($query) {
                        $query->StoreOrder();
                    },
                ]);
            })
            ->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%");
                }
            })
            ->Active()->orderBy('order_count', 'DESC')->get();
            $order_payment_methods = Order::when(isset($filter) && $filter == 'this_year', function ($query) {
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
                ->StoreOrder()->Delivered()->NotRefunded()
                ->selectRaw(DB::raw("sum(`order_amount`) as total_order_amount, count(*) as order_count, IF((`payment_method`='cash_on_delivery'), `payment_method`, IF(`payment_method`='wallet',`payment_method`, 'digital_payment')) as 'payment_methods'"))->groupBy('payment_methods')
                ->get();

            $new_stores = Store::when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('created_at', now()->format('Y'));
            })
                ->when(isset($filter) && $filter == 'this_month', function ($query) {
                    return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                })
                ->when(isset($filter) && $filter == 'this_month', function ($query) {
                    return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
                })
                ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                    return $query->whereYear('created_at', date('Y') - 1);
                })
                ->when(isset($filter) && $filter == 'this_week', function ($query) {
                    return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
                })->count();
            $orders = Order::when(isset($filter) && $filter == 'this_year', function ($query) {
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
                })->StoreOrder()->get();
            $total_order_amount = $orders->whereIn('order_status', ['delivered'])->sum('order_amount');
            $total_ongoing = $orders->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->count();
            $total_canceled = $orders->whereIn('order_status', ['failed', 'canceled'])->count();
            $total_delivered = $orders->whereIn('order_status', ['delivered'])->count();

            $data = [
                'stores'=>$stores,
                'search'=>$request->search??null,
                'new_stores'=>$new_stores,
                'orders'=>$orders->count(),
                'total_order_amount'=>$total_order_amount,
                'total_ongoing'=>$total_ongoing,
                'total_canceled'=>$total_canceled,
                'total_delivered'=>$total_delivered,
                'cash_payments'=>count($order_payment_methods)>0?\App\CentralLogics\Helpers::number_format_short(isset($order_payment_methods[0])?$order_payment_methods[0]->total_order_amount:0):0,
                'digital_payments'=>count($order_payment_methods)>0?\App\CentralLogics\Helpers::number_format_short(isset($order_payment_methods[1])?$order_payment_methods[1]->total_order_amount:0):0,
                'wallet_payments'=>count($order_payment_methods)>0?\App\CentralLogics\Helpers::number_format_short(isset($order_payment_methods[2])?$order_payment_methods[2]->total_order_amount:0):0,
                'filter'=>$filter,
            ];
        if ($request->type == 'excel') {
            return Excel::download(new StoreSummaryReportExport($data), 'StoreSummaryReport.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new StoreSummaryReportExport($data), 'StoreSummaryReport.csv');
        }
    }

    public function expense_export(Request $request)
    {
        $key = explode(' ', $request['search']);
        if (session()->has('from_date') == false) {
            session()->put('from_date', now()->firstOfMonth()->format('Y-m-d'));
            session()->put('to_date', now()->lastOfMonth()->format('Y-m-d'));
        }
        $from = session('from_date');
        $to = session('to_date');
        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store_id = $request->query('store_id', 'all');
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;
        $customer_id = $request->query('customer_id', 'all');
        $customer = is_numeric($customer_id) ? User::findOrFail($customer_id) : null;
        $filter = $request->query('filter', 'all_time');
        $module = request()->module;
             $type = $request->query('type', 'all');

        $expense = Expense::with('order', 'order.customer:id,f_name,l_name')->where('created_by', 'admin')->where('amount', '>' ,0)
            ->when($zone || $module || $customer || $store, function ($query) use ($zone, $module, $customer, $store) {
                $query->whereHas('order', function ($query) use ($zone, $store, $customer, $module) {
                    $query->when($module, function ($query) use ($module) {
                        return $query->module($module);
                    });
                    $query->when($zone, function ($query) use ($zone) {
                        return $query->where('zone_id', $zone->id);
                    });
                    $query->when($store, function ($query) use ($store) {
                        return $query->where('store_id', $store->id);
                    });
                    $query->when($customer, function ($query) use ($customer) {
                        return $query->where('user_id', $customer->id);
                    });
                });
            })
                   ->when(isset($type) &&  $type != 'all', function ($query) use ($type) {
                return $query->where('type',$type);
            })
            ->when(isset($filter) , function ($query) use ($filter,$from, $to) {
                return $query->applyDateFilter($filter, $from, $to);
            })
            ->search(keywords:$request['search'], mainCol: ['type', 'order_id', 'trip_id', 'ride_id'])
            ->orderBy('id')->get();

        $data = [
            'expenses'=>$expense,
            'search'=>$request->search??null,
            'from'=>(($filter == 'custom') && $from)?$from:null,
            'to'=>(($filter == 'custom') && $to)?$to:null,
            'zone'=>is_numeric($zone_id)?Helpers::get_zones_name($zone_id):null,
            'store'=>is_numeric($store_id)?Helpers::get_stores_name($store_id):null,
            'customer'=>is_numeric($customer_id)?Helpers::get_customer_name($customer_id):null,
            'module'=>request('module_id')?Helpers::get_module_name(request('module_id')):null,
            'filter'=>$filter,
        ];

        if ($request->export_type == 'excel') {
            return Excel::download(new ExpenseReportExport($data), 'ExpenseReport.xlsx');
        } else if ($request->export_type == 'csv') {
            return Excel::download(new ExpenseReportExport($data), 'ExpenseReport.csv');
        }
    }

    public function expense_search(Request $request)
    {
        $key = explode(' ', $request['search']);

        if (session()->has('from_date') == false) {
            session()->put('from_date', now()->firstOfMonth()->format('Y-m-d'));
            session()->put('to_date', now()->lastOfMonth()->format('Y-m-d'));
        }
        $from = session('from_date');
        $to = session('to_date');
        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store_id = $request->query('store_id', 'all');
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;
        $customer_id = $request->query('customer_id', 'all');
        $customer = is_numeric($customer_id) ? User::findOrFail($customer_id) : null;
        $filter = $request->query('filter', 'all_time');
             $type = $request->query('type', 'all');

        $expense = Expense::with('order')->where('amount', '>' ,0)
            ->whereHas('order', function ($query) use ($zone, $store, $customer) {
                $query->when(request('module_id'), function ($query) {
                    return $query->module(request('module_id'));
                });
                $query->when($zone, function ($query) use ($zone) {
                    return $query->where('zone_id', $zone->id);
                });
                $query->when($store, function ($query) use ($store) {
                    return $query->where('store_id', $store->id);
                });
                $query->when($customer, function ($query) use ($customer) {
                    return $query->where('user_id', $customer->id);
                });
            })
                   ->when(isset($type) &&  $type != 'all', function ($query) use ($type) {
                return $query->where('type',$type);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })
            ->when(isset($filter) && $filter == 'this_year', function ($query) {
                return $query->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'this_month', function ($query) {
                return $query->whereMonth('created_at', now()->format('m'))->whereYear('created_at', now()->format('Y'));
            })
            ->when(isset($filter) && $filter == 'previous_year', function ($query) {
                return $query->whereYear('created_at', date('Y') - 1);
            })
            ->when(isset($filter) && $filter == 'this_week', function ($query) {
                return $query->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            })
            ->when(isset($key), function ($query) use ($key){
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('type', 'like', "%{$value}%")
                            ->orWhere('order_id', 'like', "%{$value}%")
                            ->orWhere('trip_id', 'like', "%{$value}%");

                        if (addon_published_status('RideShare')) {
                            $q->orWhereHas('ride', function ($query) use ($value) {
                                $query->where('id', 'like', "%{$value}%")
                                    ->orWhere('ref_id', 'like', "%{$value}%");
                            });
                        }
                    }
                });
            })
            ->limit(25)->get();

        return response()->json([
            'count' => count($expense),
            'view' => view('admin-views.report.partials._expense_table', compact('expense'))->render()
        ]);
    }

    public function order_report(Request $request)
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
        $store_id = $request->query('store_id', 'all');
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;
        $customer_id = $request->query('customer_id', 'all');
        $customer = is_numeric($customer_id) ? User::findOrFail($customer_id) : null;
        $filter = $request->query('filter', 'all_time');

        $orders = Order::with(['customer', 'store', 'details', 'transaction'])
            ->when(request('module_id'), function ($query) {
                return $query->module(request('module_id'));
            })
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->where('zone_id', $zone->id);
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
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
            ->StoreOrder()
            ->withSum('transaction', 'admin_commission')
            ->withSum('transaction', 'admin_expense')
            ->withSum('transaction', 'delivery_fee_comission')
            ->orderBy('schedule_at', 'desc')->paginate(config('default_pagination'))->withQueryString();

        // order card values calculation
        $orders_list = Order::when(request('module_id'), function ($query) {
            return $query->module(request('module_id'));
        })
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->where('zone_id', $zone->id);
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
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
            ->StoreOrder()
            ->orderBy('schedule_at', 'desc')->get();

        $total_order_amount = $orders_list->sum('order_amount');
        $total_coupon_discount = $orders_list->sum('coupon_discount_amount');
        $total_product_discount = $orders_list->sum('store_discount_amount');

        $total_canceled_count = $orders_list->where('order_status', 'canceled')->count();
        $total_delivered_count = $orders_list->where('order_status', 'delivered')->count();
        $total_progress_count = $orders_list->whereIn('order_status', ['accepted', 'confirmed', 'processing', 'handover'])->count();
        $total_failed_count = $orders_list->where('order_status', 'failed')->count();
        $total_refunded_count = $orders_list->where('order_status', 'refunded')->count();
        $total_on_the_way_count = $orders_list->whereIn('order_status', ['picked_up'])->count();
        return view('admin-views.report.order-report', compact('orders', 'orders_list', 'zone', 'store', 'filter', 'customer', 'total_on_the_way_count', 'total_refunded_count', 'total_failed_count', 'total_progress_count', 'total_canceled_count', 'total_delivered_count'));
    }



    public function order_report_export(Request $request)
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
        $store_id = $request->query('store_id', 'all');
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;
        $customer_id = $request->query('customer_id', 'all');
        $customer = is_numeric($customer_id) ? User::findOrFail($customer_id) : null;
        $filter = $request->query('filter', 'all_time');

        $orders = Order::with(['customer', 'store'])
            ->when(request('module_id'), function ($query) {
                return $query->module(request('module_id'));
            })
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->where('zone_id', $zone->id);
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
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
            ->StoreOrder()
            ->withSum('transaction', 'admin_commission')
            ->withSum('transaction', 'admin_expense')
            ->withSum('transaction', 'delivery_fee_comission')
            ->orderBy('schedule_at', 'desc')->get();

        $data = [
            'orders'=>$orders,
            'search'=>$request->search??null,
            'from'=>(($filter == 'custom') && $from)?$from:null,
            'to'=>(($filter == 'custom') && $to)?$to:null,
            'zone'=>is_numeric($zone_id)?Helpers::get_zones_name($zone_id):null,
            'store'=>is_numeric($store_id)?Helpers::get_stores_name($store_id):null,
            'customer'=>is_numeric($customer_id)?Helpers::get_customer_name($customer_id):null,
            'module'=>request('module_id')?Helpers::get_module_name(request('module_id')):null,
            'filter'=>$filter,
        ];

        if ($request->type == 'excel') {
            return Excel::download(new OrderReportExport($data), 'OrderReport.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new OrderReportExport($data), 'OrderReport.csv');
        }
    }

    public function expense_report(Request $request)
    {
        $key = explode(' ', $request['search']);
        if (session()->has('from_date') == false) {
            session()->put('from_date', now()->firstOfMonth()->format('Y-m-d'));
            session()->put('to_date', now()->lastOfMonth()->format('Y-m-d'));
        }
        $from = session('from_date');
        $to = session('to_date');
        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store_id = $request->query('store_id', 'all');
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;
        $customer_id = $request->query('customer_id', 'all');
        $module_id = $request->query('module_id', 'all');
        $customer = is_numeric($customer_id) ? User::findOrFail($customer_id) : null;
        $filter = $request->query('filter', 'all_time');
        $type = $request->query('type', 'all');

        $expense = Expense::with('user','order', 'order.customer:id,f_name,l_name')->where('amount', '>' ,0)
            ->when(isset($zone) || isset($store) || isset($customer), function ($query) use ($zone, $store, $customer) {
                return $query->whereHas('order', function ($query) use ($zone, $store, $customer) {
                    $query->when($zone, function ($query) use ($zone) {
                        return $query->where('zone_id', $zone->id);
                    });
                    $query->when($store, function ($query) use ($store) {
                        return $query->where('store_id', $store->id);
                    });
                    $query->when($customer, function ($query) use ($customer) {
                        return $query->where('user_id', $customer->id);
                    });
                });
            })
            ->when(isset($type) &&  $type != 'all', function ($query) use ($type) {
                return $query->where('type',$type);
            })
            ->when(isset($module_id) &&  is_numeric($module_id), function ($query) use ($module_id) {
                return $query->whereHas('order', function ($query) use ($module_id) {
                    $query->when(is_numeric($module_id), function ($query) use ($module_id) {
                        return $query->where('module_id',$module_id);
                    });
                });
            })
            ->when(isset($filter) , function ($query) use ($filter,$from, $to) {
                return $query->applyDateFilter($filter, $from, $to);
            })
            ->search(keywords:$request['search'], mainCol: ['type', 'order_id', 'trip_id', 'ride_id'])
            ->where('created_by', 'admin')
            ->orderBy('created_at', 'desc')
            ->paginate(config('default_pagination'))->withQueryString();

        return view('admin-views.report.expense-report', compact('expense', 'zone', 'store', 'filter', 'customer','type'));
    }

    public function generate_statement($id)
    {
        $company_phone = BusinessSetting::where('key', 'phone')->first()->value;
        $company_email = BusinessSetting::where('key', 'email_address')->first()->value;
        $company_name = BusinessSetting::where('key', 'business_name')->first()->value;
        $company_web_logo = BusinessSetting::where('key', 'logo')->first()->value;
        $footer_text = \App\Models\BusinessSetting::where(['key' => 'footer_text'])->first()->value;

        $order_transaction = OrderTransaction::with('order', 'order.details', 'order.customer', 'order.store')->where('id', $id)->first();
        $data["email"] = $order_transaction->order->customer != null ? $order_transaction->order->customer["email"] : translate('email_not_found');
        $data["client_name"] = $order_transaction->order->customer != null ? $order_transaction->order->customer["f_name"] . ' ' . $order_transaction->order->customer["l_name"] : translate('customer_not_found');
        $data["order_transaction"] = $order_transaction;
        $mpdf_view = View::make(
            'admin-views.report.order-transaction-statement',
            compact('order_transaction', 'company_phone', 'company_name', 'company_email', 'company_web_logo', 'footer_text')
        );
        Helpers::gen_mpdf($mpdf_view, 'order_trans_statement', $order_transaction->id);
    }

    public function low_stock_report(Request $request)
    {
        $key = explode(' ', $request['search']);
        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;
        $stock_modules = array_keys(array_filter(config('module'), function ($var) {
            if (isset($var['stock']) && $var['stock']) return $var;
        }));
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];

        $items = Item::withoutGlobalScope(StoreScope::class)->with(['store', 'store.zone'])->whereHas('store.module', function ($query) {
            $query->where('module_type', '!=', 'food');
        })
            ->when($request->query('module_id', null), function ($query) use ($request) {
                return $query->module($request->query('module_id'));
            })
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('store_id', $zone->stores->pluck('id'));
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
            })
            ->when(count($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                });
            })
            ->whereHas('store.StoreConfig', function ($query) {
                $query->whereColumn('items.stock', '<=', 'store_configs.minimum_stock_for_warning')->orwhere('items.stock', 0);
            })
            ->orderBy('stock')
            ->paginate(config('default_pagination'))->withQueryString();

        return view('admin-views.report.low-stock-report', compact('zone', 'store', 'items'));
    }


    public function stock_report(Request $request)
    {
        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;
        $stock_modules = array_keys(array_filter(config('module'), function ($var) {
            if (isset($var['stock']) && $var['stock']) return $var;
        }));
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];

        $items = Item::withoutGlobalScope(StoreScope::class)
        ->with(['store', 'store.zone'])->whereHas('store.module', function ($query) use ($stock_modules) {
            $query->where('module_type', Config::get('module.current_module_type'));
        })
            ->when($request->query('module_id', null), function ($query) use ($request) {
                return $query->module($request->query('module_id'));
            })
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('store_id', $zone->stores->pluck('id'));
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
            })
            ->when(count($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                });
            })
            ->whereHas('store.StoreConfig', function ($query) {
                $query->whereColumn('items.stock', '<=', 'store_configs.minimum_stock_for_warning')->orwhere('items.stock', 0);
            })
            ->orderBy('stock')
            ->paginate(config('default_pagination'))->withQueryString();

        return view('admin-views.report.stock-report', compact('zone', 'store', 'items'));
    }



    public function low_stock_wise_export(Request $request)
    {
        $key = explode(' ', $request['search']);
        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;
        $stock_modules = array_keys(array_filter(config('module'), function ($var) {
            if (isset($var['stock']) && $var['stock']) return $var;
        }));
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];

        $items = Item::withoutGlobalScope(StoreScope::class)->with(['store', 'store.zone'])->whereHas('store.module', function ($query) {
            $query->where('module_type', '!=', 'food');
        })
            ->when($request->query('module_id', null), function ($query) use ($request) {
                return $query->module($request->query('module_id'));
            })
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('store_id', $zone->stores->pluck('id'));
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
            })
            ->when(count($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                });
            })
            ->whereHas('store.StoreConfig', function ($query) {
                $query->whereColumn('items.stock', '<=', 'store_configs.minimum_stock_for_warning')->orwhere('items.stock', 0);
            })
            ->orderBy('stock')
            ->get();

        $data = [
            'items'=>$items,
            'search'=>$request->search??null,
            'zone'=>is_numeric($zone_id)?Helpers::get_zones_name($zone_id):null,
            'store'=>is_numeric($store_id)?Helpers::get_stores_name($store_id):null,
        ];

        if ($request->type == 'excel') {
            return Excel::download(new LimitedStockReportExport($data), 'StockReport.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new LimitedStockReportExport($data), 'StockReport.csv');
        }
    }

    public function low_stock_search(Request $request)
    {
        $key = explode(' ', $request['search']);

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $store_id = $request->query('store_id', 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;
        $stock_modules = array_keys(array_filter(config('module'), function ($var) {
            if (isset($var['stock']) && $var['stock']) return $var;
        }));
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];

        $items = Item::withoutGlobalScope(StoreScope::class)->with(['store', 'store.zone'])->whereHas('store.module', function ($query) {
            $query->where('module_type', '!=', 'food');
        })
            ->when($request->query('module_id', null), function ($query) use ($request) {
                return $query->module($request->query('module_id'));
            })
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('store_id', $zone->stores->pluck('id'));
            })
            ->when(isset($store), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
            })
            ->when(count($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                });
            })
            ->orderBy('stock')
            ->limit(25)->get();

        return response()->json([
            'count' => count($items),
            'view' => view('admin-views.report.partials._stock_table', compact('items'))->render()
        ]);
    }

    public function disbursement_report(Request $request,$tab = 'store')
    {
        $from =  null;
        $to = null;
        $filter = $request->query('filter', 'all_time');
        if($filter == 'custom'){
            $from = $request->from ?? null;
            $to = $request->to ?? null;
        }
        $key = explode(' ', $request['search']);
        $zone_id = $request->query('zone_id', isset(auth('admin')?->user()?->zone_id) ? auth('admin')?->user()?->zone_id : 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store_id = $request->query('store_id', 'all');
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;
        $delivery_man_id = $request->query('delivery_man_id', 'all');
        $delivery_man = is_numeric($delivery_man_id) ? DeliveryMan::findOrFail($delivery_man_id) : null;
        $rider_id = $request->query('rider_id', 'all');
        $rider = is_numeric($rider_id) ? DeliveryMan::rider()->findOrFail($rider_id) : null;
        $withdrawal_methods = WithdrawalMethod::ofStatus(1)->get();
        $status = $request->query('status', 'all');
        $payment_method_id = $request->query('payment_method_id', 'all');
        $module_id = $request->query('module_id', 'all');

        $dis = DisbursementDetails::
        when((isset($tab) && ($tab == 'store')), function ($query) {
            return $query->whereNotNull('store_id');
        })
            ->when((isset($tab) && ($tab == 'delivery_man')), function ($query) {
                return $query->whereNotNull('delivery_man_id')->whereHas('delivery_man', function($q) {
                    $q->where('is_ride', 0);
                });
            })
            ->when((isset($tab) && ($tab == 'rider')), function ($query) {
                return $query->whereNotNull('delivery_man_id')->whereHas('rider', function($q) {
                    return $q;
                });
            })
            ->when((isset($zone) && ($tab == 'store')), function ($query) use ($zone) {
                return $query->whereHas('store',function($q)use ($zone){
                    $q->where('zone_id', $zone->id);
                });
            })
            ->when((isset($store) && ($tab == 'store')), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
            })
            ->when((isset($module_id) &&  is_numeric($module_id)&& ($tab == 'store')), function ($query) use ($module_id) {
                return $query->whereHas('store', function ($query) use ($module_id) {
                    $query->where('module_id',$module_id);
                });
            })
            ->when((isset($zone) && ($tab == 'delivery_man')), function ($query) use ($zone) {
                return $query->whereHas('store',function($q)use ($zone){
                    $q->where('zone_id', $zone->id);
                });
            })
            ->when((isset($zone) && ($tab == 'rider')), function ($query) use ($zone) {
                return $query->whereHas('store',function($q)use ($zone){
                    $q->where('zone_id', $zone->id);
                });
            })
            ->when((isset($delivery_man) && ($tab == 'delivery_man')), function ($query) use ($delivery_man) {
                return $query->where('delivery_man_id', $delivery_man->id);
            })
            ->when((isset($rider) && ($tab == 'rider')), function ($query) use ($rider) {
                return $query->where('delivery_man_id', $rider->id);
            })
            ->when((isset($payment_method_id) && ($payment_method_id != 'all')), function ($query) use ($payment_method_id) {
                return $query->whereHas('withdraw_method',function($q)use ($payment_method_id){
                    $q->where('withdrawal_method_id', $payment_method_id);
                });
            })
            ->when((isset($status) && ($status != 'all')), function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->when(isset($filter) , function ($query) use ($filter,$from, $to) {
                return $query->applyDateFilter($filter, $from, $to);
            })
            ->when(isset($key), function ($q) use ($key) {
                $q->where(function ($query) use ($key) {
                    foreach ($key as $value) {
                        $query->orWhere('disbursement_id', 'like', "%{$value}%")
                              ->orWhere('status', 'like', "%{$value}%")
                              ->orWhereHas('withdraw_method', function ($subQuery) use ($value) {
                                  $subQuery->where('method_name','like', "%{$value}%");
                              });
                    }
                });
            })
            ->latest();

        $total_disbursements= $dis->get();

        $disbursements= $dis->paginate(config('default_pagination'))->withQueryString();

        $pending =(float) $total_disbursements->where('status','pending')->sum('disbursement_amount');
        $completed =(float) $total_disbursements->where('status','completed')->sum('disbursement_amount');
        $canceled =(float) $total_disbursements->where('status','canceled')->sum('disbursement_amount');

        return view('admin-views.report.disbursement-report', compact('disbursements','pending', 'completed','canceled','zone', 'store','filter','from','to','withdrawal_methods','status','payment_method_id','tab'));

    }
    public function disbursement_report_export(Request $request,$type,$tab = 'store')
    {
        $from =  null;
        $to = null;
        $filter = $request->query('filter', 'all_time');
        if($filter == 'custom'){
            $from = $request->from ?? null;
            $to = $request->to ?? null;
        }
        $key = explode(' ', $request['search']);
        $zone_id = $request->query('zone_id', isset(auth('admin')?->user()?->zone_id) ? auth('admin')?->user()?->zone_id : 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $store_id = $request->query('store_id', 'all');
        $store = is_numeric($store_id) ? Store::findOrFail($store_id) : null;
        $delivery_man_id = $request->query('delivery_man_id', 'all');
        $delivery_man = is_numeric($delivery_man_id) ? DeliveryMan::findOrFail($delivery_man_id) : null;
        $rider_id = $request->query('rider_id', 'all');
        $rider = is_numeric($rider_id) ? DeliveryMan::rider()->findOrFail($rider_id) : null;
        $withdrawal_methods = WithdrawalMethod::ofStatus(1)->get();
        $status = $request->query('status', 'all');
        $payment_method_id = $request->query('payment_method_id', 'all');
        $module_id = $request->query('module_id', 'all');

        $disbursements = DisbursementDetails::
        when((isset($tab) && ($tab == 'store')), function ($query) {
            return $query->whereNotNull('store_id');
        })
            ->when((isset($tab) && ($tab == 'delivery_man')), function ($query) {
                return $query->whereNotNull('delivery_man_id')->whereHas('delivery_man', function($q) {
                    $q->where('is_ride', 0);
                });
            })
            ->when((isset($tab) && ($tab == 'rider')), function ($query) {
                return $query->whereNotNull('delivery_man_id')->whereHas('rider', function($q) {
                    return $q;
                });
            })
            ->when((isset($zone) && ($tab == 'store')), function ($query) use ($zone) {
                return $query->whereHas('store',function($q)use ($zone){
                    $q->where('zone_id', $zone->id);
                });
            })
            ->when((isset($store) && ($tab == 'store')), function ($query) use ($store) {
                return $query->where('store_id', $store->id);
            })
            ->when((isset($zone) && ($tab == 'delivery_man')), function ($query) use ($zone) {
                return $query->whereHas('store',function($q)use ($zone){
                    $q->where('zone_id', $zone->id);
                });
            })
            ->when((isset($zone) && ($tab == 'rider')), function ($query) use ($zone) {
                return $query->whereHas('store',function($q)use ($zone){
                    $q->where('zone_id', $zone->id);
                });
            })
            ->when((isset($module_id) &&  is_numeric($module_id)&& ($tab == 'store')), function ($query) use ($module_id) {
                return $query->whereHas('store', function ($query) use ($module_id) {
                    $query->where('module_id',$module_id);
                });
            })
            ->when((isset($delivery_man) && ($tab == 'delivery_man')), function ($query) use ($delivery_man) {
                return $query->where('delivery_man_id', $delivery_man->id);
            })
            ->when((isset($rider) && ($tab == 'rider')), function ($query) use ($rider) {
                return $query->where('delivery_man_id', $rider->id);
            })
            ->when((isset($payment_method_id) && ($payment_method_id != 'all')), function ($query) use ($payment_method_id) {
                return $query->whereHas('withdraw_method',function($q)use ($payment_method_id){
                    $q->where('withdrawal_method_id', $payment_method_id);
                });
            })
            ->when((isset($status) && ($status != 'all')), function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->when(isset($filter) , function ($query) use ($filter,$from, $to) {
                return $query->applyDateFilter($filter, $from, $to);
            })
            ->when(isset($key), function ($q) use ($key) {
                $q->where(function ($query) use ($key) {
                    foreach ($key as $value) {
                        $query->orWhere('disbursement_id', 'like', "%{$value}%")
                              ->orWhere('status', 'like', "%{$value}%")
                              ->orWhereHas('withdraw_method', function ($subQuery) use ($value) {
                                  $subQuery->where('method_name','like', "%{$value}%");
                              });
                    }
                });
            })
            ->latest()->get();

        $data=[
            'type'=>$tab,
            'disbursements' =>$disbursements,
            'store'=>isset($store)?$store->name:null,
            'delivery_man'=>isset($delivery_man)?$delivery_man->f_name.''.$delivery_man->f_name:null,
            'rider'=>isset($rider)?$rider->f_name.''.$rider->l_name:null,
            'search'=>$request->search??null,
            'status'=>$status,
            'zone'=>isset($zone)?$zone->name:null,
            'filter'=>$filter,
            'from'=>(($filter == 'custom') && $from)?$from:null,
            'to'=>(($filter == 'custom') && $to)?$to:null,
            'pending' =>(float) $disbursements->where('status','pending')->sum('disbursement_amount'),
            'completed' =>(float) $disbursements->where('status','completed')->sum('disbursement_amount'),
            'canceled' =>(float) $disbursements->where('status','canceled')->sum('disbursement_amount'),
        ];
        if($type == 'csv'){
            return Excel::download(new DisbursementReportExport($data), 'DisbursementReport.csv');
        }
        return Excel::download(new DisbursementReportExport($data), 'DisbursementReport.xlsx');

    }
}
