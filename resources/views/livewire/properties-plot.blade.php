<div class="w-full">
    <div>
        <h2 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl mb-3">
            {!! $name_corrections[$name] ?? ucfirst(str_replace('_', ' ', $name)) !!}
        </h2>
        <div class="flex flex-col">
            <div class="flex items-start gap-2" style="height: 400px;">
                <!-- Chart Container -->
                <div class="flex-1 relative h-full">
                    <canvas id="plot-{{ $name }}" class="w-full h-full"></canvas>
                </div>
                
                <!-- Chart Controls - positioned outside chart area -->
                <div class="flex flex-col gap-1 pt-2">
                    <div class="group relative">
                        <button id="reset-zoom-{{ $name }}"
                            class="px-2 py-2 text-gray-400 hover:text-gray-600 transition-colors bg-white/90 backdrop-blur-sm rounded-md shadow-md border border-gray-200">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                        </button>
                        <div class="absolute bottom-full right-0 mb-2 px-3 py-2 bg-gray-800 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-10 whitespace-nowrap">
                            Reset zoom to original view
                            <div class="absolute top-full right-4 w-0 h-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-gray-800"></div>
                        </div>
                    </div>
                    <div class="group relative">
                        <button class="px-2 py-2 text-gray-400 hover:text-gray-600 transition-colors bg-white/90 backdrop-blur-sm rounded-md shadow-md border border-gray-200"
                            title="Chart Controls">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607ZM10.5 7.5v6m3-3h-6" />
                            </svg>
                        </button>
                        <div class="absolute bottom-full right-0 mb-2 px-3 py-2 bg-gray-800 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-10 whitespace-nowrap">
                            Scroll to zoom, click and drag to pan
                            <div class="absolute top-full right-4 w-0 h-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-gray-800"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="legend-{{ $name }}" class="chart-legend-container w-full mt-2">
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Wait a bit for the zoom plugin to load
            setTimeout(() => {

                const plotCanvas = document.getElementById('plot-{{ $name }}');

                // Data from Livewire/PHP
                const plotData = @js($property);

                const datasets = [{
                    label: 'COCONUT Data',
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
                                    text: "{{ $name == 'van_der_walls_volume' ? 'Van der Waals volume' : $name_corrections[$name]  ?? ucfirst(str_replace('_', ' ', $name)) }}"
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
                            htmlLegend: {
                                containerID: 'legend-{{ $name }}'
                            },
                            legend: {
                                display: false
                            },
                            tooltip: {
                                enabled: true,
                                mode: 'nearest',
                                intersect: false,
                                callbacks: {
                                    title: function(context) {
                                        return "{{ ucfirst(str_replace('_', ' ', $name)) }}";
                                    },
                                    label: function(context) {
                                        const xValue = context.parsed.x.toFixed(3);
                                        const yValue = context.parsed.y.toFixed(6);
                                        return [
                                            `Value: ${xValue}`,
                                            `Density: ${yValue}`
                                        ];
                                    },
                                },
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: 'white',
                                bodyColor: 'white',
                                borderColor: 'rgba(255, 255, 255, 0.1)',
                                borderWidth: 1,
                                xAlign: 'centre',
                                yAlign: 'top'
                            },
                            zoom: {
                                pan: {
                                    enabled: true,
                                    mode: 'xy',
                                    threshold: 10
                                },
                                zoom: {
                                    wheel: {
                                        enabled: true,
                                        speed: 0.1
                                    },
                                    pinch: {
                                        enabled: true
                                    },
                                    mode: 'xy'
                                }
                            }
                        },
                        interaction: {
                            intersect: false,
                        }
                    },
                });

                // Reset zoom functionality
                document.getElementById('reset-zoom-{{ $name }}').addEventListener('click', function() {
                    chart.resetZoom();
                });
            }, 100); // Close setTimeout with 100ms delay
        });
    </script>

</div>