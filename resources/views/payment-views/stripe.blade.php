@extends('payment-views.layouts.master')

@push('script')
    {{--stripe--}}
    <script src="https://js.stripe.com/v3/"></script>
@endpush

@section('content')
    <div class="text-center"> <h1>Please do not refresh this page...</h1></div>

<script type="text/javascript">
    // Create an instance of the Stripe object with your publishable API key
    var stripe = Stripe('{{$public['published_key'] ?? ''}}');
    document.addEventListener("DOMContentLoaded", function () {
        fetch("{{ url("payment/stripe/token/?payment_id={$data->id}") }}", {
            method: "GET",
        }).then(function (response) {
            console.log(response)
            return response.text();
        }).then(function (session) {
            console.log(session)
            return stripe.redirectToCheckout({sessionId: JSON.parse(session).id});
        }).then(function (result) {
            if (result.error) {
                alert(result.error.message);
            }
        }).catch(function (error) {
            console.error("error:", error);
        });
    });

</script>
@endsection
