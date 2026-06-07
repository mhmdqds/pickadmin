"use strict";
document.addEventListener("DOMContentLoaded", function () {
    const canvas = document.getElementById("updatingData");

    if (!canvas) return; // Exit if canvas is not found

    const labels = JSON.parse(canvas.dataset.chartLabels);
    const data = JSON.parse(canvas.dataset.chartData);
    const currencySymbol = canvas.dataset.chartCurrencySymbol;

    const ctx = canvas.getContext("2d");

    new Chart(ctx, {
        type: "bar",
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: "#82CFCF",
                hoverBackgroundColor: "#82CFCF",
                borderColor: "#82CFCF"
            }]
        },
        options: {
            legend: {
                display: false
            },
            scales: {
                yAxes: [{
                    gridLines: {
                        color: "#e7eaf3",
                        drawBorder: false,
                        zeroLineColor: "#e7eaf3"
                    },
                    ticks: {
                        beginAtZero: true,
                        stepSize: Math.ceil((data.reduce((a, b) => a + b, 0) / 10000)) * 2000,
                        fontSize: 12,
                        fontColor: "#97a4af",
                        fontFamily: "Open Sans, sans-serif",
                        padding: 5,
                        callback: function (value) {
                            return value + " " + currencySymbol;
                        }
                    }
                }],
                xAxes: [{
                    gridLines: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        fontSize: 12,
                        fontColor: "#97a4af",
                        fontFamily: "Open Sans, sans-serif",
                        padding: 5
                    },
                    categoryPercentage: 0.3,
                    maxBarThickness: 10
                }]
            },
            cornerRadius: 5,
            tooltips: {
                callbacks: {
                    label: function (tooltipItem, chart) {
                        return tooltipItem.yLabel + " " + currencySymbol;
                    }
                },
                mode: "index",
                intersect: false
            },
            hover: {
                mode: "nearest",
                intersect: true
            }
        }
    });
});
Chart.plugins.unregister(ChartDataLabels);

$('.js-chart').each(function() {
    $.HSCore.components.HSChartJS.init($(this));
});

let updatingChart = $.HSCore.components.HSChartJS.init($('#updatingData'));


document.addEventListener("DOMContentLoaded", function () {
    const providerSelect = document.querySelector(".js-data-example-ajax");

    if (!providerSelect) return;

    const url = providerSelect.dataset.getProviderUrl;
    const zoneId = providerSelect.dataset.zoneId;

    $(providerSelect).select2({
        ajax: {
            url: url,
            data: function (params) {
                let requestData = {
                    q: params.term, // Search term
                    page: params.page
                };

                if (zoneId) {
                    requestData.zone_ids = [zoneId];
                }

                return requestData;
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            __port: function (params, success, failure) {
                let $request = $.ajax(params);
                $request.then(success);
                $request.fail(failure);
                return $request;
            }
        }
    });
});
