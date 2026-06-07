
<div class="row">
    <div class="col-lg-12 text-center "><h1 > {{translate('Banner_List')}}
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
            <th>{{ translate('Banner_Title') }}</th>
            <th>{{ translate('Banner_Type') }}</th>
            <th>{{ translate('Provider') }}</th>
            <th>{{ translate('Url') }}</th>
            <th>{{ translate('Featured') }}</th>
            <th>{{ translate('Status') }}</th>

        </thead>
        <tbody>
        @foreach($data['data'] as $key => $brand)
            <tr>
        <td>{{ $loop->index+1}}</td>
        <td>{{ $brand->title }}</td>
        <td>{{ $brand->type == 'store_wise' ? translate('Provider_Wise') :translate('messages.default')  }}</td>

        <td>{{ $brand->type == 'store_wise' ?  $brand?->store?->name ?? "----------" : "-------------" }}</td>

        <td>{{ $brand?->default_link ?? '-------------' }}</td>

        <td>{{ $brand->featured == 1 ? translate('messages.Yes') : translate('messages.No')  }}</td>
        <td>{{ $brand->status == 1 ? translate('messages.Active') : translate('messages.Inactive')  }}</td>

            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
