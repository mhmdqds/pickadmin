"use strict";
function initializeDonutChart(hourlyCount, distanceWiseCount) {
    let options;
    let chart;
    let ApexChart;

    options = {
        series: [hourlyCount, distanceWiseCount],
        chart: {
            width: 320,
            type: 'donut',
        },
        labels: ['Hourly Trip', 'Distance Wise Trip'],
        dataLabels: {
            enabled: false,
            style: {
                colors: ['#005555', '#b9e0e0']
            }
        },
        responsive: [{
            breakpoint: 1650,
            options: {
                chart: {
                    width: 250
                },
            }
        }],
        colors: ['#005555', '#111'],
        fill: {
            colors: ['#005555', '#b9e0e0']
        },
        legend: {
            show: false
        },
    };

    if (chart) {
        chart.destroy();
    }

    chart = new ApexCharts(document.querySelector("#dognut-pie"), options);
    chart.render();
}


function initializeAreaChart(totalSell, commission, totalSubs, labels) {
    let ApexChart;
    const options = {
            series: [{
                name: 'Gross Earning',
                data: totalSell
            }, {
                name: 'Commission Earning',
                data: commission
            }, {
                name: 'Subscription Earning',
                data: totalSubs
            }],
            chart: {
                height: 350,
                type: 'area',
                toolbar: {
                    show: false
                },
                colors: ['#76ffcd','#ff6d6d', '#005555'],
            },
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
                categories: labels
            },
            tooltip: {
                x: {
                    format: 'dd/MM/yy HH:mm'
                },
            },
        };

        if (ApexChart) {
            ApexChart.destroy();
        }

        ApexChart = new ApexCharts(document.querySelector("#grow-sale-chart"), options);
        ApexChart.render();
}


$('.fetch_data_zone_wise').on('change', function () {
    let zone_id = $('.fetch_data_zone_wise').val();
    fetch_data_zone_wise(zone_id);
});

function fetch_data_zone_wise(zone_id) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $.get({
        url: $('.fetch_data_zone_wise').data('src-url'),
        data: {
            zone_id: zone_id
        },
        beforeSend: function() {
            $('#loading').show()
        },
        success: function(data) {
            $('#deliveryStatistics').html(data.delivery_statistics);
            $('#commission-overview-board').html(data.sale_chart)
            $('#topProviders').html(data.top_providers)
            $('#topCustomers').html(data.top_customers)
            $('#trip-overview-board').html(data.by_trip_type)
            $('#zoneName').html(data.zoneName);
            $('.gross-earning').text(formatCurrency(data.grossEarning));
            initializeDonutChart(data.hourlyCount, data.distanceWiseCount);
            initializeAreaChart(data.total_sell,  data.commission,  data.total_subs,  data.labels );

        },
        complete: function() {
            $('#loading').hide()
        }
    });
}

$('.trip_stats_update').on('change', function () {
    let zone_id = $('.fetch_data_zone_wise').val();
    let statistics_type = $('.trip_stats_update').val();

    trip_stats_update(zone_id, statistics_type);
});

function trip_stats_update(zone_id, statistics_type) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $.get({
        url: $('.fetch_data_zone_wise').data('src-url'),
        data: {
            zone_id: zone_id,
            statistics_type: statistics_type
        },
        beforeSend: function() {
            $('#loading').show()
        },
        success: function(data) {
            $('#deliveryStatistics').html(data.delivery_statistics);
        },
        complete: function() {
            $('#loading').hide()
        }
    });
}
$('.user_overview_stats_update').on('change', function() {
    let type = $(this).val();
    let zone_id = $('.fetch_data_zone_wise').val();
    user_overview_stats_update(type, zone_id);
});

function user_overview_stats_update(type, zone_id) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $.get({
        url: $('#trip_by_trip_type_stats_update').data('src-url'),
        data: {
            trip_overview: type,
            zone_id: zone_id
        },
        beforeSend: function() {
            $('#loading').show()
        },
        success: function(data) {
            insert_param('trip_overview', type);
            $('#trip-overview-board').html(data.view);
            const hourlyCount = data.hourlyCount;
            const distanceWiseCount = data.distanceWiseCount;
            initializeDonutChart(hourlyCount, distanceWiseCount);
        },
        complete: function() {
            $('#loading').hide()
        }
    });
}

$('.commission_overview_stats_update').on('change', function() {
    let type = $(this).val();
    let zone_id = $('.fetch_data_zone_wise').val();
    commission_overview_stats_update(type, zone_id);
});

function commission_overview_stats_update(type, zone_id) {

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
        $.get({
            url: $('#commission_overview_stats_update').data('src-url'),
        data: {
            commission_overview: type,
            zone_id: zone_id
        },
        beforeSend: function() {
            $('#loading').show()
        },
        success: function(data) {
            let grossEarningTotal = (data.grossEarning).toFixed(2)
            insert_param('commission_overview', type);
            $('#commission-overview-board').html(data.view);
            $('.gross-earning').text(formatCurrency(grossEarningTotal));
            initializeAreaChart(data.total_sell,  data.commission,  data.total_subs,  data.labels );
        },
        complete: function() {
            $('#loading').hide()
        }
    });
}
const currencySymbol = document.getElementById('current_currency').dataset.currency.trim();

function formatCurrency(value) {
    const formattedValue = Number(value).toFixed(2);
    return `${currencySymbol}${formattedValue}`;
}

const currentUrl = document.getElementById('current_url').dataset.srcUrl.trim();


function insert_param(key, value) {
    key = encodeURIComponent(key);
    value = encodeURIComponent(value);
    let kvp = document.location.search.substr(1).split('&');
    let i = 0;

    for (; i < kvp.length; i++) {
        if (kvp[i].startsWith(key + '=')) {
            let pair = kvp[i].split('=');
            pair[1] = value;
            kvp[i] = pair.join('=');
            break;
        }
    }
    if (i >= kvp.length) {
        kvp[kvp.length] = [key, value].join('=');
    }

    const params = kvp.join('&');
    const newUrl = params ? `${currentUrl}?${params}` : currentUrl;
    window.history.pushState('page2', 'Title', newUrl);
}
