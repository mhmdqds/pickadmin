@php($params = session('dash_params'))

<div class="d-flex flex-wrap justify-content-between align-items-center __gap-12px">
    <div class="__gross-amount" id="gross_sale">
        <h6>{{\App\CentralLogics\Helpers::format_currency(array_sum($total_sell))}}</h6>
        <span>{{ translate('messages.Gross Sale') }}</span>
    </div>
    <div class="chart--label __chart-label p-0 move-left-100 ml-auto">
        <span class="indicator chart-bg-2"></span>
        <span class="info">
            {{ translate('sale') }} ({{ date("Y") }})
        </span>
    </div>
    <select class="custom-select border-0 text-center w-auto ml-auto commission_overview_stats_update" name="commission_overview">
            <option
            value="this_year" {{$params['commission_overview'] == 'this_year'?'selected':''}}>
            {{translate('This year')}}
        </option>
        <option
            value="this_month" {{$params['commission_overview'] == 'this_month'?'selected':''}}>
            {{translate('This month')}}
        </option>
        <option
            value="this_week" {{$params['commission_overview'] == 'this_week'?'selected':''}}>
            {{translate('This week')}}
        </option>
    </select>
</div>
<div id="commission-overview-board">

    <div id="grow-sale-chart"></div>
</div>

<script>
  "use strict";
    options = {
        series: [{
            name: '{{ translate('Gross Sale') }}',
            data: [{{ implode(",",$total_sell) }}]
        },{
            name: '{{ translate('Admin Comission') }}',
            data: [{{ implode(",",$commission) }}]
        },{
            name: '{{ translate('Delivery Comission') }}',
            data: [{{ implode(",",$delivery_commission) }}]
        }],
        chart: {
            height: 350,
            type: 'area',
            toolbar: {
                show:false
            },
            colors: ['#76ffcd','#ff6d6d', '#005555'],
        },
        colors: ['#76ffcd','#ff6d6d', '#005555'],
        dataLabels: {
            enabled: false,
            colors: ['#76ffcd','#ff6d6d', '#005555'],
        },
        stroke: {
            curve: 'smooth',
            width: 2,
            colors: ['#76ffcd','#ff6d6d', '#005555'],
        },
        fill: {
            type: 'gradient',
            colors: ['#76ffcd','#ff6d6d', '#005555'],
        },
        xaxis: {
            //   type: 'datetime',
            categories: [{!! implode(",",$label) !!}]
        },
        tooltip: {
            x: {
                format: 'dd/MM/yy HH:mm'
            },
        },
    };

    chart = new ApexCharts(document.querySelector("#grow-sale-chart"), options);
    chart.render();

    // INITIALIZATION OF CHARTJS
    // =======================================================
    Chart.plugins.unregister(ChartDataLabels);

    $('.js-chart').each(function () {
        $.HSCore.components.HSChartJS.init($(this));
    });

    updatingChart = $.HSCore.components.HSChartJS.init($('#updatingData'));


    $('.commission_overview_stats_update').on('change', function (){
        let type = $(this).val();
        commission_overview_stats_update(type);
    })
</script>