<?php

namespace App\Traits;

use App\CentralLogics\Helpers;
use App\CentralLogics\OrderLogic;
use App\Models\Expense;
use App\Models\Module;
use App\Models\OrderTransaction;
use App\Models\SubscriptionTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait ReportGeneratorTrait
{
    public function getAdminTotalEarningQuery(): string
    {
        return "
            SUM(
                COALESCE(order_transactions.admin_commission, 0)
                + COALESCE(order_transactions.admin_expense, 0)
                - COALESCE(orders.flash_admin_discount_amount, 0)
            )
        ";
    }

    private function getPreviousPeriodRange($filter, $from, $to): ?array
    {
        $now = Carbon::now();

        if ($filter === 'custom' && $from && $to) {
            $currentStart = Carbon::parse($from)->startOfDay();
            $currentEnd = Carbon::parse($to)->endOfDay();
        } elseif ($filter === 'this_month') {
            $currentStart = $now->copy()->startOfMonth();
            $currentEnd = $now->copy();
        } elseif ($filter === 'this_year') {
            $currentStart = $now->copy()->startOfYear();
            $currentEnd = $now->copy();
        } elseif ($filter === 'this_week') {
            $currentStart = $now->copy()->startOfWeek();
            $currentEnd = $now->copy();
        } elseif ($filter === 'previous_year') {
            $currentStart = $now->copy()->subYear()->startOfYear();
            $currentEnd = $now->copy()->subYear()->endOfYear();
        } else {
            return null;
        }

        if ($currentEnd->lt($currentStart)) {
            return null;
        }

        $durationInSeconds = $currentStart->diffInSeconds($currentEnd);
        $previousEnd = $currentStart->copy()->subSecond();
        $previousStart = $previousEnd->copy()->subSeconds($durationInSeconds);

        return [
            $previousStart->format('Y-m-d H:i:s'),
            $previousEnd->format('Y-m-d H:i:s'),
        ];
    }

    private function applyStoreFilter($query, $store_id, string $column = 'store_id')
    {
        if ($store_id === 'all' || $store_id === null || $store_id === '') {
            return $query;
        }

        return $query->where($column, $store_id);
    }

    private function applyStoreModuleFilter($query, $module_id)
    {
        if ($module_id === null || $module_id === '' || $module_id === 'all') {
            return $query;
        }

        return $query->whereHas('store', function ($q) use ($module_id) {
            $q->where('module_id', $module_id);
        });
    }

    private function applyOrderTypeFilter($query, $order_types = null, string $column = 'orders.order_type')
    {
        $order_types = $this->normalizeOrderTypes($order_types);

        return $query->whereIn($column, $order_types);
    }

    private function applyModuleOrOrderTypeFilter($query, $module_id = 'all', $order_types = null, string $moduleColumn = 'orders.module_id', string $orderTypeColumn = 'orders.order_type')
    {
        $module_ids = $this->normalizeModuleIds($module_id);
        $order_types = $this->normalizeOrderTypes($order_types);

        if (!empty($module_ids)) {
            if(count($module_ids) === 1){
                return $query->where($moduleColumn, $module_ids[0]);
            } else{
                return $query->whereIn($moduleColumn, $module_ids);
            }

        } elseif( in_array('parcel',$order_types) &&  count($module_ids) == 0){
            $module_ids=Module::where('module_type','parcel')->pluck('id')->toArray();
                return $query->whereIn($moduleColumn, $module_ids);
        }

        return $this->applyOrderTypeFilter($query, $order_types, $orderTypeColumn);
    }

    private function normalizeOrderTypes($order_types): array
    {
        $order_types = is_string($order_types) ? explode(',', $order_types) : (array) $order_types;
        $order_types = array_values(array_filter(array_map('trim', $order_types), function ($type) {
            return $type !== '' && $type !== 'all';
        }));

        return empty($order_types) ? ['take_away', 'delivery'] : $order_types;
    }

    private function normalizeModuleIds($module_id): array
    {
        $module_id = is_string($module_id) ? explode(',', $module_id) : (array) $module_id;
        $module_id = array_map(static function ($id) {
            return is_string($id) ? trim($id) : $id;
        }, $module_id);

        return array_values(array_filter($module_id, static function ($id) {
            return $id !== '' && $id !== null && $id !== 'all';
        }));
    }

    private function isParcelReportContext($module_id = 'all', $order_types = null): bool
    {
        $order_types = $this->normalizeOrderTypes($order_types);
        if (count($order_types) === 1 && $order_types[0] === 'parcel') {
            return true;
        }

        $module_ids = $this->normalizeModuleIds($module_id);
        if (empty($module_ids)) {
            return false;
        }

        return Module::whereIn('id', $module_ids)->exists()
            && !Module::whereIn('id', $module_ids)->where('module_type', '!=', 'parcel')->exists();
    }

    private function moduleAndOrderTypeFilter($query, $module_id = 'all', $order_types = null, bool $keepStandaloneForModule = false, bool $keepStandaloneForOrderType = false, array $moduleRelations = ['order'], string $orderTypeColumn = 'order_type')
    {
        $module_ids = $this->normalizeModuleIds($module_id);

        if (!empty($module_ids)) {
            $query->where(function ($query) use ($module_ids, $moduleRelations, $keepStandaloneForModule) {
                if ($keepStandaloneForModule) {
                    $query->whereNull('order_id');
                }

                foreach ($moduleRelations as $index => $relation) {
                    $relationMethod = $keepStandaloneForModule || $index > 0 ? 'orWhereHas' : 'whereHas';
                $query->{$relationMethod}($relation, function ($relatedQuery) use ($module_ids) {
                    if (count($module_ids) === 1) {
                        $relatedQuery->where('module_id', $module_ids[0]);
                        return;
                    }

                    $relatedQuery->whereIn('module_id', $module_ids);
                });
            }
        });

            return $query;
        }

        $order_types = $this->normalizeOrderTypes($order_types);

        $query->where(function ($query) use ($order_types, $keepStandaloneForOrderType, $orderTypeColumn) {
            if ($keepStandaloneForOrderType) {
                $query->whereNull('order_id');
            }

            $query->orWhereHas('order', function ($orderQuery) use ($order_types, $orderTypeColumn) {
                $this->applyOrderTypeFilter($orderQuery, $order_types, $orderTypeColumn);
            });
        });

        return $query;
    }

    private function calculatePercentageData($current, $previous)
    {
        if ($previous == 0) {
            if ($current == 0) {
                return [0, false];
            }
            return [100, true];
        }

        $percentage = (($current - $previous) / abs($previous)) * 100;
        $percentage =round($percentage ,2);
        return [$percentage, $percentage >= 0];
    }

    private function calculatePercentage($part, $total)
    {
        if ($total == 0) return [0, false];

        $percentage = ($part / $total) * 100;

        $percentage =round($percentage ,2);
        return [$percentage, true];
    }

    private function calculate_percentage_info($current, $previous)
    {
        if ($previous == 0) {
            if ($current == 0) {
                return [0, false];
            }
            return [100, true];
        }

        $percentage = (($current - $previous) / abs($previous)) * 100;
        $percentage = round($percentage, 2);
        return [abs($percentage), $percentage >= 0];
    }

    public function resolveDateFilter(Request $request): array
    {
        $filter = $request->query('filter', 'all_time');

        return [
            $filter,
            $filter === 'custom' ? $request->from : null,
            $filter === 'custom' ? $request->to : null,
        ];
    }

    /**
     * Detailed earnings breakdown (order commission, delivery fee, additional charge)
     */

    // Admin Calculations

    public function buildAdminEarningSummary($filter, $from, $to, $module_id = 'all', $order_types = null, bool $include_subscription = true)
    {
        $earningFormula = $this->getAdminTotalEarningQuery();
        $previousPeriodRange = $this->getPreviousPeriodRange($filter, $from, $to);

        $baseTransactionQuery = OrderTransaction::query()->whereNull('status')
            ->join('orders', 'orders.id', '=', 'order_transactions.order_id');
        $baseTransactionQuery = $this->applyModuleOrOrderTypeFilter(
            query: $baseTransactionQuery,
            module_id: $module_id,
            order_types: $order_types,
            moduleColumn: 'order_transactions.module_id',
            orderTypeColumn: 'orders.order_type'
        );

        // current & previous earnings
        $admin_earning = (clone $baseTransactionQuery)
            ->applyDateFilter($filter, $from, $to, 'order_transactions.created_at')
            ->selectRaw($earningFormula . " as admin_earning")
            ->value('admin_earning') ?? 0;

        $admin_previous_earning = 0;
        if ($previousPeriodRange) {
            $admin_previous_earning = (clone $baseTransactionQuery)
                ->whereBetween('order_transactions.created_at', $previousPeriodRange)
                ->selectRaw($earningFormula . " as admin_earning")
                ->value('admin_earning') ?? 0;
        }

        $parcel = in_array('parcel', $order_types);
        // expenses
        $expenseQuery = Expense::withoutAddon()->where('created_by', 'admin')->whereNot('type','referrer');
        $expenseQuery = $this->moduleAndOrderTypeFilter(
            query: $expenseQuery,
            module_id: $module_id,
            order_types: $order_types,
            keepStandaloneForModule: !$parcel,
            keepStandaloneForOrderType: !$parcel
        );



        $admin_expense = (float) (clone $expenseQuery)
            ->applyDateFilter($filter, $from, $to, 'expenses.created_at')
            ->sum('amount');

        $admin_previous_expense = 0;
        if ($previousPeriodRange) {
            $admin_previous_expense = (clone $expenseQuery)
                ->whereBetween('expenses.created_at', $previousPeriodRange)
                ->sum('amount');
        }

        $subscription_earning = 0;
        $subscription_previous_earning = 0;
        if ($include_subscription) {
            $subscriptionQuery = SubscriptionTransaction::where('is_trial', 0)
                ->where('payment_status', 'success');
            $subscriptionQuery = $this->applyStoreModuleFilter($subscriptionQuery, $module_id);

            $subscription_earning = (clone $subscriptionQuery)
                ->applyDateFilter($filter, $from, $to, 'subscription_transactions.created_at')
                ->sum('paid_amount');

            if ($previousPeriodRange) {
                $subscription_previous_earning = (clone $subscriptionQuery)
                    ->whereBetween('subscription_transactions.created_at', $previousPeriodRange)
                    ->sum('paid_amount');
            }

            $admin_earning += $subscription_earning;
            $admin_previous_earning += $subscription_previous_earning;
        }

        $net_profit = $admin_earning - $admin_expense;
        $previous_net_profit = $admin_previous_earning - $admin_previous_expense;

        [$admin_earning_percentage, $admin_earning_positive] =
            $this->calculatePercentageData($admin_earning, $admin_previous_earning);

        [$net_profit_percentage, $net_profit_positive] =
            $this->calculatePercentageData($net_profit, $previous_net_profit);

        [$admin_expense_percentage, $admin_expense_positive] =
            $this->calculatePercentageData($admin_expense, $admin_previous_expense);

        [$subscription_percentage, $subscription_positive] = $include_subscription
            ? $this->calculatePercentage($subscription_earning, $admin_earning)
            : [0, true];

        return [
            'admin_earning' => $admin_earning,
            'admin_previous_earning' => $admin_previous_earning,
            'admin_earning_positive' => $admin_earning_positive,
            'admin_earning_percentage' => $admin_earning_percentage,

            'admin_expense' => $admin_expense,
            'admin_expense_percentage' => $admin_expense_percentage,
            'admin_previous_expense' => $admin_previous_expense,
            'admin_expense_positive' => $admin_expense_positive,

            'net_profit' => $net_profit,
            'previous_net_profit' => $previous_net_profit,
            'net_profit_percentage' => $net_profit_percentage,
            'net_profit_positive' => $net_profit_positive,

            'subscription_earning' => $subscription_earning,
            'subscription_previous_earning' => $subscription_previous_earning,
            'subscription_percentage' => $subscription_percentage,
            'subscription_positive' => $subscription_positive,
        ];
    }

    public function buildEarningBreakdown($filter, $from, $to, $admin_earning, $module_id = 'all', $order_types = null)
    {
        $is_parcel = $this->isParcelReportContext($module_id, $order_types);
        $baseTransactionQuery = OrderTransaction::query()->whereNull('status')
            ->join('orders', 'orders.id', '=', 'order_transactions.order_id');
        $baseTransactionQuery = $this->applyModuleOrOrderTypeFilter(
            query: $baseTransactionQuery,
            module_id: $module_id,
            order_types: $order_types,
            moduleColumn: 'order_transactions.module_id',
            orderTypeColumn: 'orders.order_type'
        );

        $earning_data = (clone $baseTransactionQuery)
            ->applyDateFilter($filter, $from, $to,'order_transactions.created_at')
            ->selectRaw("
            SUM(
                (
                    order_transactions.admin_commission
                    + order_transactions.admin_expense
                    - order_transactions.delivery_fee_comission
                    - order_transactions.additional_charge
                    - orders.flash_admin_discount_amount
                )
            ) as admin_earning,
            SUM(order_transactions.delivery_fee_comission) as delivery_fee_comission,
            SUM(order_transactions.additional_charge) as additional_charge")
            ->first();

        $order_commission = (float) ($earning_data->admin_earning ?? 0);
        $delivery_fee_comission = (float) ($earning_data->delivery_fee_comission ?? 0);
        $additional_charge = (float) ($earning_data->additional_charge ?? 0);

        if ($is_parcel) {
            $delivery_fee_comission += $order_commission;
            $order_commission = 0;
        }

        [$order_commission_percentage, $order_commission_positive] =
            $this->calculatePercentage($order_commission, $admin_earning);

        [$delivery_fee_comission_percentage, $delivery_fee_comission_positive] =
            $this->calculatePercentage($delivery_fee_comission, $admin_earning);

        [$additional_charge_percentage, $additional_charge_positive] =
            $this->calculatePercentage($additional_charge, $admin_earning);

        $additional_charge_name = Helpers::get_business_settings('additional_charge_name') ?? translate('Additional Charge');
        return [
            'order_commission' => round($order_commission, config('round_up_to_digit')),
            'order_commission_percentage' =>  $order_commission_percentage,
            'delivery_fee_comission' => round($delivery_fee_comission, config('round_up_to_digit')),
            'delivery_fee_comission_percentage' => $delivery_fee_comission_percentage,
            'additional_charge' => round($additional_charge, config('round_up_to_digit')),
            'additional_charge_percentage' => $additional_charge_percentage,
            'additional_charge_name' => $additional_charge_name,
            'is_parcel' => $is_parcel,
        ];
    }

    public function buildExpenseBreakdown($filter, $from, $to, $admin_expense, $module_id = 'all', $order_types = null)
    {
        $expenseQuery = Expense::withoutAddon()->where('created_by', 'admin');
        $expenseQuery = $this->moduleAndOrderTypeFilter(
            query: $expenseQuery,
            module_id: $module_id,
            order_types: $order_types,
            keepStandaloneForModule: true,
            keepStandaloneForOrderType: true
        );

        $all_expense = (clone $expenseQuery)
            ->applyDateFilter($filter, $from, $to,'expenses.created_at')
            ->selectRaw("
            SUM(CASE WHEN expenses.type = 'free_delivery' THEN expenses.amount ELSE 0 END) as free_delivery,
            SUM(CASE WHEN expenses.type = 'coupon_discount' THEN expenses.amount ELSE 0 END) as coupon_discount,
            SUM(
                    CASE
                        WHEN expenses.type IN ('discount_on_product', 'flash_sale_discount')
                        THEN expenses.amount
                        ELSE 0
                    END
                ) AS discount_on_item,
            SUM(CASE WHEN expenses.type = 'flash_sale_discount' THEN expenses.amount ELSE 0 END) as flash_sale_discount,
            SUM(CASE WHEN expenses.type = 'add_fund_bonus' THEN expenses.amount ELSE 0 END) as add_fund_bonus,
            SUM(CASE WHEN expenses.type = 'dm_admin_bonus' THEN expenses.amount ELSE 0 END) as dm_admin_bonus,
            SUM(CASE WHEN expenses.type = 'CashBack' THEN expenses.amount ELSE 0 END) as cashback,
            SUM(CASE WHEN expenses.type = 'referral_discount' THEN expenses.amount ELSE 0 END) as referral_discount")
            ->first();


        $total_free_delivery = $all_expense->free_delivery ;

        [$free_delivery_percentage, $free_delivery_positive] =
            $this->calculatePercentage($total_free_delivery, $admin_expense);

        [$coupon_discount_percentage, $coupon_discount_positive] =
            $this->calculatePercentage($all_expense->coupon_discount, $admin_expense);

        [$discount_on_item_percentage, $discount_on_item_positive] =
            $this->calculatePercentage($all_expense->discount_on_item + $all_expense->flash_sale_discount, $admin_expense);

        [$add_fund_bonus_percentage, $add_fund_bonus_positive] =
            $this->calculatePercentage($all_expense->add_fund_bonus, $admin_expense);

        [$cashback_percentage, $cashback_positive] =
            $this->calculatePercentage($all_expense->cashback, $admin_expense);

        [$other_percentage, $other_positive] =
            $this->calculatePercentage(
                $all_expense->dm_admin_bonus + $all_expense->referral_discount,
                $admin_expense
            );

        return [
            'free_delivery'=> $all_expense->free_delivery,
            'free_delivery_percentage'=> $free_delivery_percentage,
            'discount_on_item'=> $all_expense->discount_on_item,
            'discount_on_item_percentage'=> $discount_on_item_percentage,
            'coupon_discount'=> $all_expense->coupon_discount,
            'coupon_discount_percentage'=> $coupon_discount_percentage,
            'add_fund_bonus'=> $all_expense->add_fund_bonus,
            'add_fund_bonus_percentage'=> $add_fund_bonus_percentage,
            'cashback'=> $all_expense->cashback,
            'cashback_percentage'=> $cashback_percentage,
            'other'=> $all_expense->dm_admin_bonus + $all_expense->referral_discount,
            'other_percentage'=> $other_percentage,
            'module_id'=> $module_id,
            'order_types'=> $order_types,
        ];
    }

    public function get_order_earning_transactions($request, $filter, $from, $to, $nopaginate = false, $module_id = 'all', $order_types = null)
    {
        $search = $request->search ?? null;

        $query = OrderTransaction::with(['order.store', 'delivery_man'])
            ->join('orders', 'orders.id', '=', 'order_transactions.order_id')
            ->NotRefunded()
            ->applyDateFilter($filter, $from, $to, 'order_transactions.created_at')
            ->when($search, function ($query) use ($search) {
                $keywords = is_array($search) ? $search : explode(' ', $search);
                $keywords = array_filter(array_map('trim', $keywords));

                return $query->where(function ($subQuery) use ($keywords) {
                    foreach ($keywords as $word) {
                        $subQuery->where('order_transactions.id', 'like', "%{$word}%")
                            ->orWhere('orders.id', 'like', "%{$word}%");
                    }
                });
            })
            ->select('order_transactions.*', 'orders.order_amount', 'orders.delivery_charge', 'orders.dm_tips', 'orders.flash_admin_discount_amount', 'orders.flash_store_discount_amount', 'orders.extra_packaging_amount', 'orders.coupon_discount_amount', 'orders.store_discount_amount', 'orders.ref_bonus_amount', 'orders.coupon_created_by', 'orders.discount_on_product_by')
            ->latest('order_transactions.created_at');
        $query = $this->applyModuleOrOrderTypeFilter(
            query: $query,
            module_id: $module_id,
            order_types: $order_types,
            moduleColumn: 'order_transactions.module_id',
            orderTypeColumn: 'orders.order_type'
        );

        if ($nopaginate) {
            $transactions = $query->get();
        } else {
            $transactions = $query->paginate(config('default_pagination', 25))->withQueryString();
        }

        $collection = $nopaginate ? $transactions : $transactions->getCollection();

        $collection->transform(function ($transaction) {
            $admin_commission = max(0, ($transaction->admin_commission + $transaction->admin_expense) - $transaction->delivery_fee_comission - $transaction->additional_charge - $transaction->order['flash_admin_discount_amount']);
            $isParcel = $transaction->order?->order_type == 'parcel';
            $order_commission = $isParcel ? 0 : $admin_commission;
            $delivery_fee_comission = $transaction->delivery_fee_comission + ($isParcel ? $admin_commission : 0);
            $amount = $admin_commission + $transaction->delivery_fee_comission + $transaction->additional_charge;
            $store_name = $transaction->order?->store?->name;

            return [
                'transaction_id' => '#TXN ' . $transaction->id,
                'date' => $transaction->created_at,
                'source' => $store_name ?? ($transaction->delivery_man_id ? ($transaction->delivery_man ? $transaction->delivery_man->f_name . ' ' . $transaction->delivery_man->l_name : 'Delivery Man') : 'Admin'),
                'source_type' => $store_name ? 'Store' : ($transaction->delivery_man_id ? 'Delivery Man' : 'Admin'),
                'earning_from' => '#ORD ' . $transaction->order_id,
                'order_id' => $transaction->order_id,
                'earning_from_badge' => $transaction->delivery_man_id ? 'Delivery Commission' : null,
                'amount' => $amount,
                'breakdown' => [
                    'order_commission' => $order_commission,
                    'delivery_fee_comission' => $delivery_fee_comission,
                    'packaging_fee_collected' => $transaction->additional_charge,
                    'is_parcel' => $isParcel,
                    'hide_order_commission' => $isParcel,
                ]
            ];
        });

        return $transactions;
    }

    public function get_subscription_earning_transactions($request, $filter, $from, $to, $nopaginate = false, $module_id = 'all')
    {
        // checked
        $search = $request->search ?? null;

        $query = SubscriptionTransaction::with(['store'])
            ->where('payment_status', 'success')
            ->where('paid_amount', '>', 0)
            ->applyDateFilter($filter, $from, $to, 'created_at')
            ->search($search, ['store' => 'name'], ['id'])
            ->latest();
        $query = $this->applyStoreModuleFilter($query, $module_id);

        if ($nopaginate) {
            $transactions = $query->get();
        } else {
            $transactions = $query->paginate(config('default_pagination', 25))->withQueryString();
        }

        $collection = $nopaginate ? $transactions : $transactions->getCollection();

        $collection->transform(function ($transaction) {
            $type = match ($transaction->plan_type) {
                'renew' => 'Renew Subscription',
                'new_plan' => 'Migrate to New Plan',
                'first_purchased' => 'First Purchased',
                'free_trial' => 'Free Trial',
                default => ucwords(str_replace('_', ' ', $transaction->plan_type)),
            };

            $typeBadgeStyle = match ($transaction->plan_type) {
                'renew' => 'background-color: #F0F2F7; color: #4B5563;',
                'new_plan' => 'background-color: #FFF6E6; color: #B76E00;',
                'first_purchased' => 'background-color: #EAF7EE; color: #1F7A4D;',
                'free_trial' => 'background-color: #EDF4FF; color: #295EBC;',
                default => 'background-color: #F4F5F7; color: #4B5563;',
            };

            return [
                'transaction_id' => $transaction->id,
                'date' => $transaction->created_at,
                'store' => $transaction->store ? $transaction->store->name : 'Store',
                'transaction_type' => $type,
                'transaction_type_badge_style' => $typeBadgeStyle,
                'amount' => $transaction->paid_amount,
            ];
        });

        return $transactions;
    }

    public function get_expense_transactions($request, $filter, $from, $to, $nopaginate = false, $module_id = 'all', $order_types = null)
    {
        $search = $request->search ?? null;
        $expenseQuery = Expense::withoutAddon()->with(['store', 'delivery_man', 'user','order'])
            ->where('created_by', 'admin')
            ->whereNot('type','referrer')
            ->when($search, function ($query) use ($search) {
                $search = str_replace(['#ORD', '#TXN', '#'], '', $search);
                $query->where('order_id', 'like', "%{$search}%");
            });
        $expenseQuery = $this->moduleAndOrderTypeFilter(
            query: $expenseQuery,
            module_id: $module_id,
            order_types: $order_types,
            keepStandaloneForModule: false,
            keepStandaloneForOrderType: in_array('parcel', $order_types) ? false : true,
            moduleRelations: ['store', 'order']
        );

        $expenseQuery = $expenseQuery
            ->applyDateFilter($filter, $from, $to, 'created_at')
            ->latest('created_at');

        if ($nopaginate) {
            $results = $expenseQuery->get();
        } else {
            $results = $expenseQuery->paginate(config('default_pagination', 25))->withQueryString();
        }

        $formattedData = ($nopaginate ? $results : $results->getCollection())->map(function ($transaction) {
            $source = 'Admin';
            $source_type = 'Admin';
            $expense_source_store = ['discount_on_product', 'flash_sale_discount'];
            $transaction_type_badge = ucwords(str_replace('_', ' ', $transaction->type == 'discount_on_product' ? 'Discount On Item': $transaction->type ));

            if ($transaction->order && (in_array($transaction->type, $expense_source_store))) {
                $source = $transaction->order->store->name;
                $module = $transaction?->order?->store?->module?->module_type;
                $source_type = $module == 'food' ? 'Restaurant' : 'Store';
            } elseif ($transaction->delivery_man) {
                $source = $transaction->delivery_man->f_name . ' ' . $transaction->delivery_man->l_name;
                $source_type = 'Delivery Man';
            } elseif ($transaction->user) {
                $source = $transaction->user->f_name . ' ' . $transaction->user->l_name;
                $source_type = 'Customer';
            } elseif ($transaction->order->customer) {
                $source = $transaction->order->customer->f_name . ' ' . $transaction->order->customer->l_name;
                $source_type = 'Customer';

            } elseif ($transaction->type === 'tax') {
                $source = 'Government';
                $source_type = 'Tax Office';
            }

            return [
                'transaction_id' => '#TXN ' . $transaction->id,
                'date' => $transaction->created_at,
                'source' => $source,
                'source_type' => $source_type,
                'expense_source' => $transaction->order_id ? '#ORD ' . $transaction->order_id : '',
                'order_id' => $transaction->order_id,
                'expense_source_badge' => $transaction_type_badge,
                'amount' => $transaction->amount,
                'breakdown' => []
            ];
        });

        if ($nopaginate) {
            return $formattedData;
        }

        $results->setCollection($formattedData);
        return $results;
    }

    // Vendor Calculations

    public function get_store_earning_summary_data($store_id, $filter, $from, $to, $order_types = null)
    {

        $previousPeriodRange = $this->getPreviousPeriodRange($filter, $from, $to);
        $storeExpenseQuery = $this->applyStoreFilter(Expense::withoutAddon()->where('created_by', 'vendor'), $store_id);

        $baseQuery = OrderTransaction::join('orders', 'orders.id', '=', 'order_transactions.order_id')->NotRefunded();
        $baseQuery = $this->applyStoreFilter($baseQuery, $store_id, 'orders.store_id');
        $baseQuery = $this->applyModuleOrOrderTypeFilter(
            query: $baseQuery,
            module_id: 'all',
            order_types: $order_types,
            moduleColumn: 'orders.module_id',
            orderTypeColumn: 'orders.order_type'
        );

        $earningFormula = "
            SUM(
                orders.order_amount
                - orders.dm_tips
                - orders.delivery_charge
                - order_transactions.tax
                - orders.extra_packaging_amount
                - orders.additional_charge
                + orders.coupon_discount_amount
                + orders.store_discount_amount
                + orders.ref_bonus_amount
                + orders.flash_admin_discount_amount
                + orders.flash_store_discount_amount
                + COALESCE(orders.extra_discount_amount, 0)
            )
            -
            SUM(
                order_transactions.admin_commission
                + order_transactions.admin_expense
                - order_transactions.delivery_fee_comission
                - order_transactions.additional_charge
                - orders.flash_admin_discount_amount
            ) AS order_sales,
                        SUM(order_transactions.tax) as tax_collected,
                        SUM(order_transactions.extra_packaging_amount) as packaging_fee_collected,
                        SUM(
                            order_transactions.admin_commission
                                + order_transactions.admin_expense
                                - order_transactions.delivery_fee_comission
                                - order_transactions.additional_charge
                                - orders.flash_admin_discount_amount
                        ) as admin_commission,


                        SUM(order_transactions.additional_charge) as service_charge_paid,


                        COUNT(DISTINCT order_transactions.id) as total_orders
        ";

        $current_data = (clone $baseQuery)
            ->applyDateFilter($filter, $from, $to, 'order_transactions.created_at')
            ->selectRaw($earningFormula)
            ->first();

        $previous_data = null;
        if ($previousPeriodRange) {
            $previous_data = (clone $baseQuery)
                ->whereBetween('order_transactions.created_at', $previousPeriodRange)
                ->selectRaw($earningFormula)
                ->first();
        }
        $previous_data = $previous_data ?? (object) [];

        $current_expense_breakdown = (clone $storeExpenseQuery)
            ->applyDateFilter($filter, $from, $to, 'created_at')
            ->selectRaw("
            SUM(
                    CASE
                        WHEN type IN ('discount_on_product', 'flash_sale_discount')
                        AND created_by = 'vendor'
                        THEN amount
                        ELSE 0
                    END
                ) AS discount_on_item,
                
                SUM(CASE WHEN type = 'flash_sale_discount' AND created_by = 'vendor' THEN amount ELSE 0 END) as flash_sale_discount,
                SUM(CASE WHEN type = 'coupon_discount' AND created_by = 'vendor' THEN amount ELSE 0 END) as coupon_contribution,
                SUM(CASE WHEN type = 'free_delivery' AND created_by = 'vendor' THEN amount ELSE 0 END) as free_delivery,
                COUNT(DISTINCT id) as total_expense,
                SUM(amount) as total_expense_amount
            ")
            ->first();

        $previous_expense_breakdown = null;
        if ($previousPeriodRange) {
            $previous_expense_breakdown = (clone $storeExpenseQuery)
                ->whereBetween('created_at', $previousPeriodRange)
                        ->selectRaw("COUNT(DISTINCT id) as total_expense,
                        SUM(amount) as total_expense_amount ")
                ->first();
        }
        $previous_expense_breakdown = $previous_expense_breakdown ?? (object) [];

        $current_commission_expense_count = (clone $baseQuery)
            ->applyDateFilter($filter, $from, $to, 'order_transactions.created_at')
            ->where(function ($query) {
                $query->whereNull('order_transactions.is_subscribed')
                    ->orWhere('order_transactions.is_subscribed', 0);
            })
            ->whereRaw('(COALESCE(order_transactions.admin_commission, 0) - COALESCE(orders.flash_admin_discount_amount, 0)) > 0')
            ->count('order_transactions.id');

        // Store subscription fees
        $subQuery = $this->applyStoreFilter(
            SubscriptionTransaction::where('payment_status', 'success')->where('paid_amount', '>', 0),
            $store_id
        );

        $current_subs_data = (clone $subQuery)
            ->applyDateFilter($filter, $from, $to, 'created_at')
            ->selectRaw("SUM(paid_amount) as total_amount, COUNT(id) as total_count")
            ->first();

        $previous_subs_data = null;
        if ($previousPeriodRange) {
            $previous_subs_data = (clone $subQuery)
                ->whereBetween('created_at', $previousPeriodRange)
                ->selectRaw("SUM(paid_amount) as total_amount, COUNT(id) as total_count")
                ->first();
        }
        $previous_subs_data = $previous_subs_data ?? (object) [];

        $current_subscription_fee = $current_subs_data->total_amount ?? 0;
        $previous_subscription_fee = $previous_subs_data->total_amount ?? 0;
        $current_admin_commission = $current_data->admin_commission ?? 0;
        $previous_admin_commission = $previous_data->admin_commission ?? 0;
        $current_discount_on_item = $current_expense_breakdown->discount_on_item ?? 0 + $current_expense_breakdown->flash_sale_discount ?? 0;
        $current_coupon_contribution = $current_expense_breakdown->coupon_contribution ?? 0;
        $current_free_delivery = $current_expense_breakdown->free_delivery ?? 0;

        $current_earnings = ($current_data->order_sales ?? 0)
            + ($current_data->tax_collected ?? 0)
            + ($current_data->packaging_fee_collected ?? 0)
            + ($current_data->admin_commission ?? 0);
        $current_earning_breakdown_total = ($current_data->order_sales ?? 0)
            + ($current_data->tax_collected ?? 0)
            + ($current_data->packaging_fee_collected ?? 0);

        $current_expenses = ($current_expense_breakdown->total_expense_amount ?? 0) + $current_subscription_fee + $current_admin_commission;
        $current_expense_breakdown_total = $current_admin_commission
            + $current_subscription_fee
            + $current_discount_on_item
            + $current_coupon_contribution
            + $current_free_delivery;
        $current_net_profit = $current_earnings - $current_expenses;
        $previous_earnings = ($previous_data->order_sales ?? 0)
            + ($previous_data->tax_collected ?? 0)
            + ($previous_data->packaging_fee_collected ?? 0)
            + ($previous_data->admin_commission ?? 0);

        $previous_expenses = ($previous_expense_breakdown->total_expense_amount ?? 0) + $previous_subscription_fee + $previous_admin_commission;
        $previous_net_profit = $previous_earnings - $previous_expenses;


        [$earning_percentage, $earning_positive] = $this->calculate_percentage_info($current_earnings, $previous_earnings);
        [$expense_percentage, $expense_positive] = $this->calculate_percentage_info($current_expenses, $previous_expenses);
        [$profit_percentage, $profit_positive] = $this->calculate_percentage_info($current_net_profit, $previous_net_profit);

        [$order_sales_percentage, $order_sales_positive] =
            $this->calculatePercentage($current_data->order_sales ?? 0, $current_earning_breakdown_total);

        [$tax_collected_percentage, $tax_collected_positive] =
            $this->calculatePercentage($current_data->tax_collected ?? 0, $current_earning_breakdown_total);

        [$packaging_fee_collected_percentage, $packaging_fee_collected_positive] =
            $this->calculatePercentage($current_data->packaging_fee_collected ?? 0, $current_earning_breakdown_total);

        [$admin_commission_percentage, $admin_commission_positive] =
            $this->calculatePercentage($current_data->admin_commission ?? 0, $current_expense_breakdown_total);

        [$subscription_fee_percentage, $subscription_fee_positive] =
            $this->calculatePercentage($current_subscription_fee, $current_expense_breakdown_total);

        [$discount_on_item_percentage, $discount_on_item_positive] =
            $this->calculatePercentage($current_discount_on_item, $current_expense_breakdown_total);

        [$coupon_contribution_percentage, $coupon_contribution_positive] =
            $this->calculatePercentage($current_coupon_contribution, $current_expense_breakdown_total);

        [$free_delivery_percentage, $free_delivery_positive] =
            $this->calculatePercentage($current_free_delivery, $current_expense_breakdown_total);

        return [
            'total_earnings_with_admin_commission' => $current_earnings,
            'total_earnings_percentage' => $earning_percentage,
            'total_earnings_positive' => $earning_positive,

            'total_expenses' => $current_expenses,
            'total_expenses_percentage' => $expense_percentage,
            'total_expenses_positive' => $expense_positive,

            'net_profit' => $current_net_profit,
            'net_profit_percentage' => $profit_percentage,
            'net_profit_positive' => $profit_positive,

            'total_transaction_earning_count' => $current_data->total_orders ?? 0,
            'total_transaction_expense_count' =>  ($current_expense_breakdown->total_expense ?? 0) + $current_commission_expense_count,
            'total_transaction_subscription_count' => $current_subs_data->total_count ?? 0,


            'breakdown' => [
                'order_sales' => $current_data->order_sales ?? 0,
                'order_sales_percentage' => $order_sales_percentage,
                'order_sales_positive' => $order_sales_positive,
                'tax_collected' => $current_data->tax_collected ?? 0,
                'tax_collected_percentage' => $tax_collected_percentage,
                'tax_collected_positive' => $tax_collected_positive,
                'packaging_fee_collected' => $current_data->packaging_fee_collected ?? 0,
                'packaging_fee_collected_percentage' => $packaging_fee_collected_percentage,
                'packaging_fee_collected_positive' => $packaging_fee_collected_positive,

                'admin_commission' => $current_data->admin_commission ?? 0,
                'admin_commission_percentage' => $admin_commission_percentage,
                'admin_commission_positive' => $admin_commission_positive,
                'subscription_fee' => $current_subscription_fee,
                'subscription_fee_percentage' => $subscription_fee_percentage,
                'subscription_fee_positive' => $subscription_fee_positive,
                'discount_on_item' => $current_discount_on_item,
                'discount_on_item_percentage' => $discount_on_item_percentage,
                'discount_on_item_positive' => $discount_on_item_positive,
                'coupon_contribution' => $current_coupon_contribution,
                'coupon_contribution_percentage' => $coupon_contribution_percentage,
                'coupon_contribution_positive' => $coupon_contribution_positive,
                'free_delivery' => $current_free_delivery,
                'free_delivery_percentage' => $free_delivery_percentage,
                'free_delivery_positive' => $free_delivery_positive,
                // 'store_expense' => $current_data->store_expense ?? 0,
            ]
        ];
    }

    public function get_store_earning_trend_data($store_id, $filter, $from, $to, $order_types = null, $module_id = 'all')
    {
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
            for ($i = 0; $i < 7; $i++) {
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

        $baseTransactionQuery = OrderTransaction::join('orders', 'orders.id', '=', 'order_transactions.order_id')
            ->NotRefunded();
        $baseTransactionQuery = $this->applyStoreFilter($baseTransactionQuery, $store_id, 'orders.store_id');
        $baseTransactionQuery = $this->applyModuleOrOrderTypeFilter(
            query: $baseTransactionQuery,
            module_id: $module_id,
            order_types: $order_types,
            moduleColumn: 'orders.module_id',
            orderTypeColumn: 'orders.order_type'
        );
        $baseTransactionQuery = $baseTransactionQuery->applyDateFilter($filter, $from, $to, 'order_transactions.created_at');

        $earningFormula = "
            SUM(
                orders.order_amount
                - orders.dm_tips
                - orders.delivery_charge
                - order_transactions.tax
                - orders.extra_packaging_amount
                + orders.coupon_discount_amount
                + orders.store_discount_amount
                + orders.ref_bonus_amount
                + orders.flash_admin_discount_amount
                + orders.flash_store_discount_amount
                + COALESCE(orders.extra_discount_amount, 0)
                - order_transactions.admin_commission
                - order_transactions.admin_expense
                + order_transactions.delivery_fee_comission

                + order_transactions.tax
                + order_transactions.extra_packaging_amount
                + order_transactions.admin_commission
                + order_transactions.admin_expense
                - order_transactions.delivery_fee_comission
                - order_transactions.additional_charge
                
            )
        ";


        $earnings = (clone $baseTransactionQuery)
            ->selectRaw("DATE_FORMAT(order_transactions.created_at, '$dateFormat') as month")
            ->selectRaw("$earningFormula as total_earning")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total_earning', 'month');

        $expenseQuery = Expense::withoutAddon()->where('created_by', 'vendor')
            ->when($store_id && $store_id !== 'all', function ($query) use ($store_id) {
                return $query->where('store_id', $store_id);
            })
            ->applyDateFilter($filter, $from, $to, 'created_at');

        if (!in_array($module_id, [null, '', 'all'], true)) {
            $expenseQuery = $expenseQuery->whereHas('store', function ($query) use ($module_id) {
                $query->where('module_id', $module_id);
            });
        } else {
            $expenseQuery = $expenseQuery->where(function ($query) use ($order_types) {
                $query->whereNull('order_id')
                    ->orWhereHas('order', function ($orderQuery) use ($order_types) {
                        $this->applyModuleOrOrderTypeFilter(
                            query: $orderQuery,
                            module_id: 'all',
                            order_types: $order_types,
                            moduleColumn: 'module_id',
                            orderTypeColumn: 'order_type'
                        );
                    });
            });
        }

        $expenses = (clone $expenseQuery)
            ->selectRaw("DATE_FORMAT(created_at, '$dateFormat') as month")
            ->selectRaw("SUM(amount) as total_expense")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total_expense', 'month');

        $subQuery = $this->applyStoreFilter(
            SubscriptionTransaction::where('payment_status', 'success'),
            $store_id
        )->applyDateFilter($filter, $from, $to, 'created_at');
        if (!in_array($module_id, [null, '', 'all'], true)) {
            $subQuery = $this->applyStoreModuleFilter($subQuery, $module_id);
        }

        $subExpenses = (clone $subQuery)
            ->selectRaw("DATE_FORMAT(created_at, '$dateFormat') as month")
            ->selectRaw("SUM(paid_amount) as total_sub")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total_sub', 'month');

        $earningSeries = $months->map(fn($m) => round($earnings[$m] ?? 0, 2));
        $expenseSeries = $months->map(fn($m) => round(($expenses[$m] ?? 0) + ($subExpenses[$m] ?? 0), 2));

        return [
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
                    if ($dateFormat === '%Y')
                        return $m;
                    if ($dateFormat === '%Y-%m')
                        return Carbon::parse($m . '-01')->format('M');
                    if ($dateFormat === '%Y-%m-%d')
                        return Carbon::parse($m)->format('j');
                }
                return Carbon::parse($m . '-01')->format('M');
            }),
            'earning_series' => $earningSeries,
            'expense_series' => $expenseSeries
        ];
    }

    public function get_store_earning_transactions($request, $store_id, $filter, $from, $to, $nopaginate = false, $limit = null, $offset = null, $order_types = null)
    {
        $search = $request->search ?? null;

        $query = OrderTransaction::with(['order.store'])
            ->join('orders', 'orders.id', '=', 'order_transactions.order_id')
            ->NotRefunded()
            ->when($search, function ($query) use ($search) {
                $search = str_replace(['#ORD', '#TXN', '#'], '', $search);
                return $query->where('order_transactions.order_id', 'like', "%{$search}%");
            })
            ->applyDateFilter($filter, $from, $to, 'order_transactions.created_at')
            ->select('order_transactions.*')
            ->latest('order_transactions.created_at');

        $query = $this->applyStoreFilter($query, $store_id, 'orders.store_id');
        $query = $this->applyModuleOrOrderTypeFilter(
            query: $query,
            module_id: 'all',
            order_types: $order_types,
            moduleColumn: 'orders.module_id',
            orderTypeColumn: 'orders.order_type'
        );

        if ($nopaginate) {
            $transactions = $query->get();
        } else {
            $perPage = $limit ?? config('default_pagination', 25);
            $page = $offset ?? $request->get('page', 1);
            $transactions = $query->paginate($perPage, ['*'], 'page', $page)->withQueryString();
        }


        $collection = ($nopaginate ? $transactions : $transactions->getCollection())->map(function ($transaction) {
        $order_sales =  OrderLogic::get_original_admin_commission_details($transaction)['item_price_after_admin_commission'];

        $total_earning = $order_sales + $transaction->tax + $transaction->extra_packaging_amount;

            return [
                'transaction_id' => '#TXN ' . $transaction->id,
                'date' => $transaction->created_at,
                'source' => $transaction->store ? $transaction->store->name :  'Parcel',
                'source_type' => 'Store',
                'earning_from' => '#ORD ' . $transaction->order_id,
                'order_id' => $transaction->order_id,
                'amount' => $total_earning,
                'breakdown' => [
                    'order_commission' => $order_sales,
                    'tax_collected' => $transaction->tax,
                    'packaging_fee_collected' => $transaction->extra_packaging_amount,
                ]
            ];
        });

        if ($nopaginate) {
            return $collection;
        }

        $transactions->setCollection($collection);
        return $transactions;

    }

    public function get_store_expense_transactions($request, $store_id, $filter, $from, $to, $nopaginate = false, $limit = null, $offset = null, $order_types = null)
    {
        $search = $request->search ?? null;
        $module_id = $request->query('module_id', 'all');
        $order_types = $order_types ?? $request->query('order_types', $request->query('order_type', ['take_away', 'delivery']));
        $order_types = $this->normalizeOrderTypes($order_types);
        $expenseQuery = Expense::withoutAddon()->with(['store', 'order.store'])
            ->where('created_by', 'vendor')
            ->when($store_id && $store_id !== 'all', function ($query) use ($store_id) {
                return $query->where('store_id', $store_id);
            })
            ->applyDateFilter($filter, $from, $to, 'created_at')
            ->latest('created_at');

        if (!in_array($module_id, [null, '', 'all'], true)) {
            $expenseQuery->whereHas('store', function ($query) use ($module_id) {
                $query->where('module_id', $module_id);
            });
        } else {
            $expenseQuery->where(function ($query) use ($order_types) {
                $query->whereNull('order_id')
                    ->orWhereHas('order', function ($orderQuery) use ($order_types) {
                        $this->applyModuleOrOrderTypeFilter(
                            query: $orderQuery,
                            module_id: 'all',
                            order_types: $order_types,
                            moduleColumn: 'module_id',
                            orderTypeColumn: 'order_type'
                        );
                    });
            });
        }

        if ($search) {
            $search = str_replace(['#ORD', '#TXN', '#'], '', $search);
            $expenseQuery->where('order_id', 'like', "%{$search}%");
        }

        $commissionQuery = OrderTransaction::with(['order.store'])
            ->join('orders', 'orders.id', '=', 'order_transactions.order_id')
            ->NotRefunded()
            ->where(function ($query) {
                $query->whereNull('order_transactions.is_subscribed')
                    ->orWhere('order_transactions.is_subscribed', 0);
            })
            ->applyDateFilter($filter, $from, $to, 'order_transactions.created_at')
            ->select('order_transactions.*', 'orders.flash_admin_discount_amount')
            ->latest('order_transactions.created_at');

        $commissionQuery = $this->applyStoreFilter($commissionQuery, $store_id, 'orders.store_id');
        $commissionQuery = $this->applyModuleOrOrderTypeFilter(
            query: $commissionQuery,
            module_id: $module_id,
            order_types: $order_types,
            moduleColumn: 'orders.module_id',
            orderTypeColumn: 'orders.order_type'
        );

        if ($search) {
            $cleanSearch = str_replace(['#ORD', '#TXN', '#'], '', $search);
            $normalizedSearch = strtolower(trim($search));
            $matchesCommissionLabel = $normalizedSearch !== ''
                && (
                    str_contains(strtolower('Commission Paid'), $normalizedSearch)
                    || str_contains(strtolower('Store Commission Paid'), $normalizedSearch)
                );

            $commissionQuery->where(function ($query) use ($cleanSearch, $matchesCommissionLabel) {
                $query->where('order_transactions.order_id', 'like', "%{$cleanSearch}%");

                if ($matchesCommissionLabel) {
                    $query->orWhereRaw('(COALESCE(order_transactions.admin_commission, 0) - COALESCE(orders.flash_admin_discount_amount, 0)) > 0');
                }
            });
        }

        $expenseCollection = $expenseQuery->get()->map(function ($row) {
            $date = $row->created_at;
            $source = $row->store?->name ?? ($row->order?->store?->name ?? 'Store');

            return [
                'transaction_id' => '#TXN ' . $row->id,
                'date' => $date,
                'source' => $source,
                'source_type' => 'Store',
                'expense_source' => $row->order_id ? '#ORD ' . $row->order_id : '',
                'order_id' => $row->order_id,
                'expense_source_badge' => translate($row->type == 'discount_on_product' ? 'Discount on Item' : $row->type),
                'amount' => $row->amount,
                'breakdown' => [],
                '_sort_at' => optional($row->created_at)->timestamp ?? 0,
            ];
        });

        $commissionCollection = $commissionQuery->get()
            ->map(function ($transaction) {
                $admin_commission = max(0, ($transaction->admin_commission + $transaction->admin_expense) - $transaction->delivery_fee_comission - $transaction->additional_charge - $transaction->order['flash_admin_discount_amount']);
                $isParcel = $transaction->order?->order_type == 'parcel';
                $order_commission = $isParcel ? 0 : $admin_commission;

                $commissionAmount = $order_commission;

                if ($commissionAmount <= 0) {
                    return null;
                }

                return [
                    'transaction_id' => '#TXN ' . $transaction->id,
                    'date' => $transaction->created_at,
                    'source' => $transaction->order?->store?->name ?? 'Store',
                    'source_type' => 'Store',
                    'expense_source' => '#ORD ' . $transaction->order_id,
                    'order_id' => $transaction->order_id,
                    'expense_source_badge' => 'Commission Paid',
                    'amount' => round($commissionAmount, 2),
                    'breakdown' => [],
                    '_sort_at' => optional($transaction->created_at)->timestamp ?? 0,
                ];
            })
            ->filter();

        $formattedData = $expenseCollection
            ->merge($commissionCollection)
            ->sortByDesc('_sort_at')
            ->values()
            ->map(function ($item) {
                unset($item['_sort_at']);
                return $item;
            });

        if ($nopaginate) {
            return $formattedData;
        }

        $perPage = $limit ?? config('default_pagination', 25);
        $page = (int) ($offset ?? $request->get('page', 1));
        $items = $formattedData->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $formattedData->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    public function get_store_subscription_transactions($request, $store_id, $filter, $from, $to, $nopaginate = false, $limit = null, $offset = null)
    {
        $search = $request->search ?? null;
        // Subscription transactions
        $subQuery = $this->applyStoreFilter(
            SubscriptionTransaction::where('payment_status', 'success'),
            $store_id
        )->applyDateFilter($filter, $from, $to, 'created_at')
            ->search($search, ['store' => 'name'], ['id'])
            ->latest();

        if ($nopaginate) {
            $subsData = $subQuery->get();
        } else {
            $perPage = $limit ?? config('default_pagination', 25);
            $page = $offset ?? $request->get('page', 1);
            $subsData = $subQuery->paginate($perPage, ['*'], 'page', $page)->withQueryString();
        }

        $subscriptionTransactions = $subsData->map(function ($t) {
            $type = match ($t->plan_type) {
                'renew' => 'Renew Subscription',
                'new_plan' => 'Migrate to New Plan',
                'first_purchased' => 'First Purchased',
                'free_trial' => 'Free Trial',
                default => ucwords(str_replace('_', ' ', $t->plan_type)),
            };

            $typeBadgeStyle = match ($t->plan_type) {
                'renew' => 'background-color: #F0F2F7; color: #4B5563;',
                'new_plan' => 'background-color: #FFF6E6; color: #B76E00;',
                'first_purchased' => 'background-color: #EAF7EE; color: #1F7A4D;',
                'free_trial' => 'background-color: #EDF4FF; color: #295EBC;',
                default => 'background-color: #F4F5F7; color: #4B5563;',
            };

            return [
                'transaction_id' => $t->id,
                'date' => $t->created_at->format('d M Y h:i a'),
                'store' => $t->store ? $t->store->name : 'Store',
                'transaction_type' => $type,
                'transaction_type_badge_style' => $typeBadgeStyle,
                'amount' => $t->paid_amount,
            ];
        });

        if ($nopaginate) {
            return $subscriptionTransactions;
        }

        $subsData->setCollection($subscriptionTransactions);
        return $subsData;
    }

    // Delivery Man Calculations

    public function get_deliveryman_earning_summary_data($delivery_man_id, $filter, $from, $to, $order_types = null)
    {
        $previousPeriodRange = $this->getPreviousPeriodRange($filter, $from, $to);
        $baseQuery = OrderTransaction::join('orders', 'orders.id', '=', 'order_transactions.order_id')
            ->NotRefunded()
            ->whereNotNull('order_transactions.delivery_man_id')
            ->whereHas('delivery_man', function ($query) {
                $query->where('earning', 1);
            })
            ->whereHas('delivery_man', function ($query) {
                $query->where('earning', 1);
            })
            ->whereHas('delivery_man', function ($query) {
                $query->where('earning', 1);
            })
            ->when($delivery_man_id !== 'all' && $delivery_man_id !== null, function ($query) use ($delivery_man_id) {
                return $query->where('order_transactions.delivery_man_id', $delivery_man_id);
            });

            $earningFormula = "
                SUM(order_transactions.original_delivery_charge) as delivery_charge,
                SUM(order_transactions.dm_tips) as dm_tips,
                SUM(
                    CASE
                        WHEN orders.order_type != 'parcel' THEN order_transactions.delivery_fee_comission
                        WHEN orders.order_type = 'parcel' THEN order_transactions.admin_commission - order_transactions.additional_charge
                        ELSE 0
                    END
                ) as admin_commission
            ";

        $current_data = (clone $baseQuery)
            ->applyDateFilter($filter, $from, $to, 'order_transactions.created_at')
            ->selectRaw($earningFormula)
            ->first();

        $previous_data = null;
        if ($previousPeriodRange) {
            $previous_data = (clone $baseQuery)
                ->whereBetween('order_transactions.created_at', $previousPeriodRange)
                ->selectRaw($earningFormula)
                ->first();
        }
        $previous_data = $previous_data ?? (object) [];

        $current_earnings = ($current_data->delivery_charge ?? 0) + ($current_data->dm_tips ?? 0) + ($current_data->admin_commission ?? 0);
        $current_expenses = ($current_data->admin_commission ?? 0);
        $current_net_profit = $current_earnings - $current_expenses;
        $current_earning_breakdown_total = $current_net_profit;
        $current_expense_breakdown_total = $current_expenses;

        $previous_earnings = ($previous_data->delivery_charge ?? 0) + ($previous_data->dm_tips ?? 0) + ($previous_data->admin_commission ?? 0);
        $previous_expenses = ($previous_data->admin_commission ?? 0);
        $previous_net_profit = $previous_earnings - $previous_expenses;

        [$earning_percentage, $earning_positive] = $this->calculate_percentage_info($current_earnings, $previous_earnings);
        [$expense_percentage, $expense_positive] = $this->calculate_percentage_info($current_expenses, $previous_expenses);
        [$profit_percentage, $profit_positive] = $this->calculate_percentage_info($current_net_profit, $previous_net_profit);

        [$delivery_charge_percentage, $delivery_charge_positive] =
            $this->calculatePercentage((float) ($current_data->delivery_charge ?? 0), $current_earning_breakdown_total);

        [$dm_tips_percentage, $dm_tips_positive] =
            $this->calculatePercentage((float) ($current_data->dm_tips ?? 0), $current_earning_breakdown_total);

        [$admin_commission_percentage, $admin_commission_positive] =
            $this->calculatePercentage((float) ($current_data->admin_commission ?? 0), $current_expense_breakdown_total);

        return [
            'total_earnings' => $current_earnings,
            'total_earnings_percentage' => $earning_percentage,
            'total_earnings_positive' => $earning_positive,

            'total_expenses' => $current_expenses,
            'total_expenses_percentage' => $expense_percentage,
            'total_expenses_positive' => $expense_positive,

            'net_profit' => $current_net_profit,
            'net_profit_percentage' => $profit_percentage,
            'net_profit_positive' => $profit_positive,

            'breakdown' => [
                'delivery_charge' => (float)(($current_data->delivery_charge ?? 0)),
                'delivery_charge_percentage' => $delivery_charge_percentage,
                'delivery_charge_positive' => $delivery_charge_positive,
                'dm_tips' => (float)($current_data->dm_tips ?? 0),
                'dm_tips_percentage' => $dm_tips_percentage,
                'dm_tips_positive' => $dm_tips_positive,
                'admin_commission' => (float)($current_data->admin_commission ?? 0),
                'admin_commission_percentage' => $admin_commission_percentage,
                'admin_commission_positive' => $admin_commission_positive,
            ]
        ];
    }

    public function get_deliveryman_earning_trend_data($delivery_man_id, $filter, $from, $to, $order_types = null)
    {
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
            for ($i = 0; $i < 7; $i++) {
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

        $baseTransactionQuery = OrderTransaction::join('orders', 'orders.id', '=', 'order_transactions.order_id')
            ->NotRefunded()
            ->whereNotNull('order_transactions.delivery_man_id')
            ->whereHas('delivery_man', function ($query) {
                $query->where('earning', 1);
            })
            ->when($delivery_man_id !== 'all' && $delivery_man_id !== null, function ($query) use ($delivery_man_id) {
                return $query->where('order_transactions.delivery_man_id', $delivery_man_id);
            })
            ->applyDateFilter($filter, $from, $to, 'order_transactions.created_at');

            $earningFormula = "SUM(order_transactions.original_delivery_charge + order_transactions.dm_tips)";

            $expenseFormula = "
                SUM(
                    CASE
                        WHEN orders.order_type != 'parcel' THEN order_transactions.delivery_fee_comission
                        WHEN orders.order_type = 'parcel' THEN order_transactions.admin_commission - order_transactions.additional_charge
                        ELSE 0
                    END
                )
            ";

        $earnings = (clone $baseTransactionQuery)
            ->selectRaw("DATE_FORMAT(order_transactions.created_at, '$dateFormat') as month")
            ->selectRaw("$earningFormula as total_earning")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total_earning', 'month');

        $expenses = (clone $baseTransactionQuery)
            ->selectRaw("DATE_FORMAT(order_transactions.created_at, '$dateFormat') as month")
            ->selectRaw("$expenseFormula as total_expense")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total_expense', 'month');

        $earningSeries = $months->map(function ($m) use ($earnings) {
            return round($earnings[$m] ?? 0, 2);
        });
        $expenseSeries = $months->map(fn($m) => round($expenses[$m] ?? 0, 2));

        return [
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
                    if ($dateFormat === '%Y')
                        return $m;
                    if ($dateFormat === '%Y-%m')
                        return Carbon::parse($m . '-01')->format('M');
                    if ($dateFormat === '%Y-%m-%d')
                        return Carbon::parse($m)->format('j');
                }
                return Carbon::parse($m . '-01')->format('M');
            }),
            'earning_series' => $earningSeries,
            'expense_series' => $expenseSeries
        ];
    }

    public function get_deliveryman_earning_transactions($request, $delivery_man_id, $filter, $from, $to, $nopaginate = false, $order_types = null)
    {
        $search = $request->search ?? null;
        $limit = $request->limit;
        $offset = $request->offset;
        $query = OrderTransaction::with(['order', 'delivery_man'])
            ->join('orders', 'orders.id', '=', 'order_transactions.order_id')
            ->NotRefunded()
            ->whereHas('delivery_man', function ($query) {
                $query->where('earning', 1);
            })
            ->when($delivery_man_id && $delivery_man_id !== 'all', function ($query) use ($delivery_man_id) {
                return $query->where('order_transactions.delivery_man_id', $delivery_man_id);
            })
            ->whereNotNull('order_transactions.delivery_man_id')
            ->applyDateFilter($filter, $from, $to, 'order_transactions.created_at')
            ->select('order_transactions.*')
            ->search($search, ['delivery_man' => 'f_name'], ['order_id'])
            ->latest();

        if ($nopaginate) {
            $transactions = $query->get();
        } else {
                if($limit && $offset){
                    $transactions = $query->paginate($limit, ['*'], 'page', $offset);
                }else{
                    $transactions = $query->paginate(config('default_pagination', 25))->withQueryString();
                }
        }

        $collection = $nopaginate ? $transactions : $transactions->getCollection();

        $collection->transform(function ($transaction) {
            return [
                'order_id' => '#' . $transaction->order_id,
                'raw_order_id' => $transaction->order_id,
                'order_date' => $transaction->created_at,
                'delivery_man' => $transaction->delivery_man ? $transaction->delivery_man->f_name . ' ' . $transaction->delivery_man->l_name : 'Delivery Man',
                'delivery_charge' => $transaction->order->original_delivery_charge?? 0,
                'tips' => $transaction->dm_tips,
                'commission_paid' => $transaction->order->order_type !=  'parcel' ? $transaction->delivery_fee_comission : ($transaction->admin_commission - $transaction->additional_charge),
                'net_profit' => $transaction->original_delivery_charge + $transaction->dm_tips,
                'date' => $transaction->created_at,
            ];
        });

        if($limit && $offset){
            return [
                'total_size' => $transactions->total(),
                'limit' => (int) $limit,
                'offset' => (int) $offset,
                'data' => $collection->values()->all()
            ];
        }
        return $transactions;
    }

}
