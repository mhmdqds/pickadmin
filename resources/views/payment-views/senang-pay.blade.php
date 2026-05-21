@extends('payment-views.layouts.master')

@push('script')
@endpush

@section('content')

    @if(isset($config))
        <div class="text-center">
            <h1>Please do not refresh this page...</h1>
        </div>

        <div class="col-md-6 mb-4" style="cursor: pointer">
            <div class="card">
                <div class="card-body" style="height: 70px">

                    @php
                        $secretkey = trim($config->secret_key);

                        $detail = trim($payment_data->attribute ?? '');
                        $order_id = trim($payment_data->attribute_id ?? '');
                        $amount = number_format($payment_data->payment_amount, 2, '.', '');

                        $name = $payer->name ?? '';
                        $email = $payer->email ?? '';
                        $phone = $payer->phone ?? '';

                        $mode = $config->mode ?? 'test';

                        $hash = hash_hmac('sha256', $secretkey . $detail . $amount . $order_id, $secretkey);
                    @endphp

                    <form id="form" method="post" action="https://{{ $mode == 'live' ? 'app.senangpay.my' : 'sandbox.senangpay.my' }}/payment/{{ $config->merchant_id }}">
                        <input type="hidden" name="amount" value="{{ $amount }}">
                        <input type="hidden" name="name" value="{{ $name }}">
                        <input type="hidden" name="email" value="{{ $email }}">
                        <input type="hidden" name="phone" value="{{ $phone }}">
                        <input type="hidden" name="hash" value="{{ $hash }}">
                        <input type="hidden" name="detail" value="{{ $detail }}">
                        <input type="hidden" name="order_id" value="{{ $order_id }}">
                    </form>

                </div>
            </div>
        </div>

    @endif

    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("form").submit();
        });
    </script>

@endsection