
<div class="row">
    <div class="col-lg-12 text-center "><h1 > {{translate('Driver_List')}}
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
            <th>{{ translate('Driver_ID') }}</th>
            <th>{{ translate('Driver_Name') }}</th>
            <th>{{ translate('Driver_Provider') }}</th>
            <th>{{ translate('Driver_Email') }}</th>
            <th>{{ translate('Driver_Phone') }}</th>
            <th>{{ translate('Status') }}</th>

        </thead>
        <tbody>
        @foreach($data['data'] as $key => $driver)
            <tr>
        <td>{{ $loop->index+1}}</td>
        <td>{{ $driver->id }}</td>
        <td>{{ $driver->fullName }}</td>
        <td>{{ $driver?->provider?->name }}</td>
        <td>{{ $driver->email }}</td>
        <td>{{ $driver->phone }}</td>
        <td>{{ $driver->status == 1 ? translate('messages.Active') : translate('messages.Inactive')  }}</td>

            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
