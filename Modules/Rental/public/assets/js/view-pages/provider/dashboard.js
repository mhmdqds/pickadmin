"use strict";

function initializeAreaChart(initialCommission, initialLabels) {

    let ApexChart;

    let options = {
        series: [{
            name: 'Earning',
            data: initialCommission
        }],
        chart: {
            height: 350,
            type: 'area',
            toolbar: {
                show: false
            },
            colors: ['#76ffcd', '#ff6d6d', '#005555'],
        },
        dataLabels: {
            enabled: false,
            colors: ['#76ffcd', '#ff6d6d', '#005555'],
        },
        stroke: {
            curve: 'smooth',
            width: 2,
            colors: ['#76ffcd', '#ff6d6d', '#005555'],
        },
        fill: {
            type: 'gradient',
            colors: ['#76ffcd', '#ff6d6d', '#005555'],
        },
        xaxis: {
            categories: initialLabels
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


$(document).ready(function () {
    $('.trip_stats_update').on('change', function () {
        let statistics_type = $('.trip_stats_update').val();
        let route = $(this).data('route');
        trip_stats_update(statistics_type, route);
    });

    function trip_stats_update(statistics_type, route) {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.get({
            url: route,
            data: {
                statistics_type: statistics_type
            },
            beforeSend: function () {
                $('#loading').show()
            },
            success: function (data) {
                $('#deliveryStatistics').html(data.delivery_statistics);
            },
            complete: function () {
                $('#loading').hide()
            }
        });
    }

    $('.commission_overview_stats_update').on('change', function () {
        let type = $(this).val();
        let route = $('#commission_overview_stats_update').data('route');

        commission_overview_stats_update(type, route);
    });

    function commission_overview_stats_update(type, route) {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.get({
            url: route,
            data: {
                commission_overview: type,
            },
            beforeSend: function () {
                $('#loading').show()
            },
            success: function (data) {
                let grossEarningTotal = (data.grossEarning).toFixed(2)
                insert_param('commission_overview', type);
                $('#commission-overview-board').html(data.view);
                $('.gross-earning').text(formatCurrency(grossEarningTotal));
                initializeAreaChart(data.commission , data.labels)
            },
            complete: function () {
                $('#loading').hide()
            }
        });
    }

    function formatCurrency(value) {
        var currency = $('#currency').data('currency');
        return currency + value;
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
});
