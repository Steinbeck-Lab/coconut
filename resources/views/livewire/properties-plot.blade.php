<div class="w-full">
    <div>
        <h2 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl mb-3">
        {!! $name_corrections[$name] ?? ucfirst(str_replace('_', ' ', $name)) !!}
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
            });
        });
    </script>

</div>