<div>
    <div style="width: 100%; position: relative; height: 500px;">
        <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl mb-3.5">
            NP-Likeliness (Density Plot)
        </h2>
        <canvas id="myChart"></canvas>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const ctx = document.getElementById('myChart');

        // Get the data from PHP/Livewire
        const data = @js($data['properties']['np_likeness']);

        // Prepare datasets
        const datasets = [{
            label: 'Overall',
            data: data.overall.density_data.map(point => ({
                x: point.x,
                y: point.y
            })),
            borderColor: 'black',
            backgroundColor: 'black',
            borderWidth: 2,
            pointRadius: 0,
            tension: 0.4,
            fill: false
        }];

        // Add collections
        Object.entries(data.collections).forEach(([name, collection], index) => {
            datasets.push({
                label: name,
                data: collection.density_data.map(point => ({
                    x: point.x,
                    y: point.y
                })),
                borderColor: `hsl(${index * 30}, 70%, 50%)`,
                borderWidth: 2,
                pointRadius: 0,
                tension: 0.4,
                fill: false,
                hidden: true
            });
        });

        new Chart(ctx, {
            type: 'line',
            data: {
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'nearest',
                    intersect: false,
                    axis: 'xy'
                },
                scales: {
                    x: {
                        type: 'linear',
                        title: {
                            display: true,
                            text: 'NP-likeness'
                        }
                    },
                    y: {
                        type: 'linear',
                        title: {
                            display: true,
                            text: 'Density'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        enabled: false
                    },
                    legend: {
                        position: 'right',
                        align: 'start',
                        labels: {
                            textDecoration: 'none',
                            font: {
                                lineHeight: 1,
                            },
                            boxWidth: 15,
                            padding: 8,
                            filter: function(legendItem, data) {
                                const dataset = data.datasets[legendItem.datasetIndex];
                                if (dataset.hidden) {
                                    legendItem.fillStyle = '#999';
                                    legendItem.strokeStyle = '#999';
                                } else {
                                    legendItem.fillStyle = dataset.borderColor;
                                    legendItem.strokeStyle = dataset.borderColor;
                                }
                                return true;
                            }
                        },
                        onClick: function(e, legendItem, legend) {
                            const index = legendItem.datasetIndex;
                            const ci = legend.chart;

                            if (index === 0) return;

                            const dataset = ci.data.datasets[index];
                            dataset.hidden = !dataset.hidden;

                            if (!dataset.hidden) {
                                dataset.borderColor = `hsl(${(index-1) * 30}, 70%, 50%)`;
                            }

                            ci.update();
                        }
                    }
                }
            },
            plugins: [{
                id: 'legendMargin',
                beforeInit(chart) {
                    const fitValue = chart.legend.fit;
                    chart.legend.fit = function fit() {
                        fitValue.bind(chart.legend)();
                        return this.height;
                    }
                }
            }]
        });

        // Add custom styles after chart creation
        setTimeout(() => {
            const legendContainer = document.querySelector('.chartjs-plugin-legend');
            if (legendContainer) {
                Object.assign(legendContainer.style, {
                    maxHeight: '400px',
                    overflowY: 'auto',
                    paddingRight: '10px'
                });
            }
        }, 100);
    </script>

    <style>
        /* Target Chart.js legend container */
        .chartjs-plugin-legend {
            max-height: 400px !important;
            overflow-y: auto !important;
        }

        /* Scrollbar styling */
        .chartjs-plugin-legend::-webkit-scrollbar {
            width: 6px;
        }

        .chartjs-plugin-legend::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .chartjs-plugin-legend::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }

        .chartjs-plugin-legend::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</div>