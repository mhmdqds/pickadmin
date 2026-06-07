
<div class="row">
    <div class="col-lg-12 text-center "><h1 > {{translate('review_List')}}
    </h1></div>
    <div class="col-lg-12">

    <table>
        <thead>
            <tr>
                <th>{{ translate('Filter_Criteria') }}</th>
                <th></th>
                <th>
                    {{ translate('Search_Bar_Content')  }}: {{ $data['search'] ?? translate('N/A') }}

                </th>
                <th> </th>
                </tr>


        <tr>
            <th>{{ translate('sl') }}</th>
            <th>{{ translate('Provider_Name') }}</th>
            <th>{{ translate('Trip_ID') }}</th>
            <th>{{ translate('Customer_name') }}</th>
            <th>{{ translate('Vehicle_name') }}</th>
            <th>{{ translate('Rating') }}</th>
            <th>{{ translate('Comment') }}</th>
            <th>{{ translate('Reply') }}</th>
            <th>{{ translate('Status') }}</th>

        </thead>
        <tbody>
        @foreach($data['data'] as $key => $review)
            <tr>
                <td>{{ $loop->index+1}}</td>
                <td>{{ $review->provider->name }}</td>
                <td>{{ $review->trip->id }}</td>
                <td>{{ $review?->customer?->fullName }}</td>
                <td>{{ $review?->vehicle?->name }}</td>
                <td>{{ $review?->rating }}</td>
                <td>{{ $review?->comment }}</td>
                <td>{{ $review?->reply }}</td>
                 <td>{{ $review->status == 1 ? translate('messages.Active') : translate('messages.Inactive')  }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
