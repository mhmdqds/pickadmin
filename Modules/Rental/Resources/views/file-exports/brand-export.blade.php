
<div class="row">
    <div class="col-lg-12 text-center "><h1 > {{translate('Brand_List')}}
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
            <th>{{ translate('Brand_Name') }}</th>
            <th>{{ translate('Brand_ID') }}</th>
            <th>{{ translate('Status') }}</th>

        </thead>
        <tbody>
        @foreach($data['data'] as $key => $brand)
            <tr>
        <td>{{ $loop->index+1}}</td>
        <td>{{ $brand->name }}</td>
        <td>{{ $brand->id }}</td>
        <td>{{ $brand->status == 1 ? translate('messages.Active') : translate('messages.Inactive')  }}</td>

            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
