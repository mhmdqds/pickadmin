"use strict";
document.addEventListener("DOMContentLoaded", function () {
    const canvas = document.getElementById("updatingData");

    if (!canvas) return; // Exit if canvas is not found

    // Retrieve dataset attributes
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
                    label: function (tooltipItem) {
                        return tooltipItem.yLabel + " " + currencySymbol;
                    }
                },
                mode: "index",
                intersect: false
            },
            hover: {
                mode: "nearest",
                intersect: true
            },

        }
    });
});



   // Bar Charts
   Chart.plugins.unregister(ChartDataLabels);

   $('.js-chart').each(function () {
       $.HSCore.components.HSChartJS.init($(this));
   });

   let updatingChart = $.HSCore.components.HSChartJS.init($('#updatingData'));


document.addEventListener("DOMContentLoaded", function () {
    if (!window.chartData) {
        console.error("Chart data not found!");
        return;
    }

    let options = {
        series: window.chartData.series,
        chart: {
            width: 320,
            type: "donut",
        },
        labels: window.chartData.labels,
        dataLabels: {
            enabled: false,
            style: {
                colors: ["#ffffff", "#ffffff", "#107980"],
            },
        },
        responsive: [
            {
                breakpoint: 1650,
                options: {
                    chart: {
                        width: 260,
                    },
                },
            },
        ],
        colors: ["#107980", "#56B98F", "#111"],
        fill: {
            colors: ["#107980", "#56B98F", "#E5F5F1"],
        },
        legend: {
            show: false,
        },
    };

    let chartElement = document.querySelector("#dognut-pie");
    if (chartElement) {
        let chart = new ApexCharts(chartElement, options);
        chart.render();
    } else {
        console.error("Chart element not found!");
    }
});
