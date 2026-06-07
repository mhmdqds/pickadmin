<div>
    <div class="table-responsive">
        <table id="datatable"
            class="table table-thead-bordered table-align-middle card-table">
            <thead class="thead-light">
                <tr>
                    <th class="w--1 border-0">{{translate('sl')}}</th>
                    <th class="w--1 border-0">{{translate('messages.Trip_id')}}</th>
                    <th class="w--2 border-0">{{translate('messages.total_trip_amount')}}</th>
                    <th class="w--3 border-0">{{translate('messages.provider_earned')}}</th>
                    <th class="w--1 border-0">{{translate('messages.admin_earned')}}</th>
                    <th class="w--1 border-0">{{translate('messages.additional_charge')}}</th>
                    <th class="w--1 border-0">{{translate('messages.vat/tax')}}</th>
                </tr>
            </thead>
            <tbody>
            @php($digitalTransaction = \Modules\Rental\Entities\TripTransaction::where('vendor_id', $store->vendor->id)->latest()->paginate(25))
            @foreach($digitalTransaction as $key => $transaction)
                <tr>
                    <td scope="row">{{$key+$digitalTransaction->firstItem()}}</td>
                    <td><a href="{{route('admin.rental.trip.details',$transaction->trip_id)}}">{{$transaction->trip_id}}</a></td>
                    <td>{{\App\CentralLogics\Helpers::format_currency($transaction->trip_amount)}}</td>
                    <td>{{\App\CentralLogics\Helpers::format_currency($transaction->store_amount - $transaction->tax)}}</td>
                    <td>{{\App\CentralLogics\Helpers::format_currency($transaction->admin_commission)}}</td>
                    <td>{{\App\CentralLogics\Helpers::format_currency($transaction->additional_charge)}}</td>
                    <td>{{\App\CentralLogics\Helpers::format_currency($transaction->tax)}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@if(count($digitalTransaction) !== 0)
<hr>
@endif
<div class="page-area">
    {!! $digitalTransaction->links() !!}
</div>
@if(count($digitalTransaction) === 0)
<div class="empty--data">
    <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
    <h5>
        {{translate('no_data_found')}}
    </h5>
</div>
@endif
