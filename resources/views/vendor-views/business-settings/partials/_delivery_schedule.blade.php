@php($data=[])
<?php
foreach ($store->deliverySchedules as $schedule) {
    $data[$schedule->day][] = [
        'id'         => $schedule->id,
        'start_time' => $schedule->opening_time,
        'end_time'   => $schedule->closing_time,
        'is_active'  => (bool) $schedule->is_active,
    ];
}
$days = [
    1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday',
    5 => 'friday', 6 => 'saturday', 0 => 'sunday',
];
?>
@foreach($days as $dayId => $dayKey)
<div class="schedule-item p-20px py-0">
    <span class="btn">{{translate('messages.'.$dayKey)}} :</span>
    <div class="schedult-date-content">
        @if(isset($data[(string)$dayId]) && count($data[(string)$dayId]))
            @foreach ($data[(string)$dayId] as $day)
            <div class="d-inline-flex align-items-center">
                <span class="start--time">
                    <span class="clock--icon"><i class="tio-time"></i></span>
                    <span class="info">
                        <span>{{translate('messages.opening_time')}}</span>
                        {{date(config('timeformat'), strtotime($day['start_time']))}}
                    </span>
                </span>
                <span class="end--time">
                    <span class="clock--icon"><i class="tio-time"></i></span>
                    <span class="info">
                        <span>{{translate('messages.closing_time')}}</span>
                        {{date(config('timeformat'), strtotime($day['end_time']))}}
                    </span>
                </span>
                @if(empty($day['is_active']))
                    <span class="btn btn-sm btn-outline-warning m-1 disabled">{{translate('messages.delivery_disabled')}}</span>
                @endif
                <span class="dismiss--date delete-delivery-schedule"
                      data-url="{{route('vendor.business-settings.remove-delivery-schedule',['delivery_schedule'=>$day['id']])}}"
                      ><i class="tio-clear-circle-outlined"></i></span>
            </div>
            @endforeach
        @else
            <span class="btn btn-sm btn-outline-success m-1 disabled">{{translate('messages.delivery_default_hours')}}</span>
        @endif
        <span class="btn add--primary"
              data-toggle="modal"
              data-target="#exampleModalDelivery"
              data-dayid="{{$dayId}}"
              data-day="{{translate('messages.'.$dayKey)}}"><i class="tio-add"></i></span>
    </div>
</div>
@endforeach
