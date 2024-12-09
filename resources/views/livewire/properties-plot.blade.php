<div class="w-full">
    <div>
        <h2 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl mb-3">
            {{ ucfirst(str_replace('_', ' ', $name)) }} (Density Plot)
        </h2>
        <div class="flex flex-col">
            <div style="height: 400px;" class="w-full">
                <canvas id="plot-{{ $name }}"></canvas>
            </div>
            <div id="legend-{{ $name }}" class="chart-legend-container w-full mt-2">
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const plotCanvas = document.getElementById('plot-{{ $name }}');
            
            // Data from Livewire/PHP
            const plotData = @js($property);

            const datasets = [{
                label: 'COCONUT',
                data: plotData.overall.density_data.map(point => ({
                    x: point.x,
                    y: point.y
                })),
                borderColor: 'black',
                borderWidth: 2,
                pointRadius: 0,
                tension: 0.4,
                fill: false
            }];

            Object.entries(plotData.collections).forEach(([name, collection], index) => {
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

            const chart = new Chart(plotCanvas, {
                type: 'line',
                data: {
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            type: 'linear',
                            title: {
                                display: true,
                                text: '{{ ucfirst(str_replace('_', ' ', $name)) }}'
                            },
                            ticks: {
                                stepSize: 1
                            },
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
                        htmlLegend: {
                            containerID: 'legend-{{ $name }}'
                        },
                        legend: {
                            display: false
                        }
                    }
                },
                // plugins: [{
                //     id: 'htmlLegend',
                //     beforeInit(chart) {
                //         const container = document.getElementById('legend-{{ $name }}');
                //         if (!container) return;

                //         // Clear existing legend
                //         container.innerHTML = '';

                //         const ul = document.createElement('ul');
                //         ul.style.listStyle = 'none';
                //         ul.style.padding = 0;
                //         ul.style.display = 'flex';
                //         ul.style.flexWrap = 'wrap';
                //         ul.style.gap = '8px';

                //         chart.data.datasets.forEach((dataset, i) => {
                //             const li = document.createElement('li');
                //             li.style.marginBottom = '4px';
                //             li.style.opacity = dataset.hidden ? '0.3' : '1';
                //             li.style.display = 'flex';
                //             li.style.alignItems = 'center';
                //             li.style.minWidth = '120px';

                //             const button = document.createElement('button');
                //             button.style.border = 'none';
                //             button.style.background = dataset.borderColor;
                //             button.style.width = '12px';
                //             button.style.height = '12px';
                //             button.style.marginRight = '6px';
                //             button.style.cursor = 'pointer';

                //             const label = document.createTextNode(dataset.label);

                //             li.onclick = () => {
                //                 dataset.hidden = !dataset.hidden;
                //                 chart.update();
                //                 li.style.opacity = dataset.hidden ? '0.3' : '1';
                //             };
                //             li.style.cursor = 'pointer';
                //             li.appendChild(button);
                //             li.appendChild(label);
                //             ul.appendChild(li);
                //         });

                //         container.appendChild(ul);
                //     }
                // }]
            });
        });
    </script>

    <!-- <style>
        .chart-legend-container {
            padding: 8px;
            border-top: 1px solid #eee;
        }

        .chart-legend-container ul {
            margin: 0;
            padding: 0;
        }
    </style> -->
</div>