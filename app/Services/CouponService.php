<?php

namespace App\Services;

use App\Contracts\Repositories\CouponRepositoryInterface;

class CouponService
{
    public function __construct(
        protected CouponRepositoryInterface $couponRepo,
    )
    {
    }

    public function getAddData(Object $request, int|string $moduleId): array
    {
        $data  = '';
        $customerId  = $request->customer_ids ?? ['all'];
        if($request->coupon_type == 'zone_wise')
        {
            $data = $request->zone_ids;
        }
        else if($request->coupon_type == 'store_wise')
        {
            $data = $request->store_ids;
        }
        return [
            'title' => $request->title[array_search('default', $request->lang)],
            'code' => $request->code,
            'limit' => $request->coupon_type=='first_order'?1:$request->limit,
            'coupon_type' => $request->coupon_type,
            'start_date' => $request->start_date,
            'expire_date' => $request->expire_date,
            'min_purchase' => $request->min_purchase != null ? $request->min_purchase : 0,
            'max_discount' => $request->max_discount != null ? $request->max_discount : 0,
            'discount' => $request->discount_type == 'amount' ? $request->discount : $request['discount'],
            'discount_type' => $request->discount_type??'',
            'status' =>  1,
            'created_by' =>  'admin',
            'data' =>  json_encode($data),
            'customer_id' =>  json_encode($customerId),
            'module_id' => $moduleId,
            'store_id' => is_array($data) && $request->coupon_type == 'store_wise' ? $data[0] : null ,
        ];
    }

    public function getUniqueCouponCode(?string $title): string
    {
        if (!$title) {
            $code = strtoupper(bin2hex(random_bytes(4))); // 8 chars random
        } else {
            $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $title));
            if (empty($code)) {
                $code = strtoupper(bin2hex(random_bytes(4)));
            }
        }

        $code = substr($code, 0, 12);

        $exists = $this->couponRepo->getFirstWhere(params: ['code' => $code]);
        if (!$exists) {
            return $code;
        }

        $i = 1;
        while (true) {
            $suffix = (string)$i;
            $candidate = substr($code, 0, 12 - strlen($suffix)) . $suffix;
            if (!$this->couponRepo->getFirstWhere(params: ['code' => $candidate])) {
                return $candidate;
            }
            $i++;
        }
    }
}
