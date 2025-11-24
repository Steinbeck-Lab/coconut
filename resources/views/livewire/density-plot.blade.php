<div>
    <div>
        <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl mb-1">
            NP-Likeness Score
            <span class=" text-sm ">
                <a class="text-sm text-blue-500 hover:text-blue-600" href="http://dx.doi.org/10.1021/ci700286x" target="_blank"><svg class="inline-block w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/></svg></a>
            </span>
        </h2>

        <div class="flex flex-col md:flex-row mt-3.5">
            <div style="height: 550px;" class="w-full md:w-4/5 md:h-full">
                <canvas id="myChart"></canvas>
            </div>
            <div id="chartLegend" class="chart-legend-container w-full md:w-1/5 md:h-full p-4 overflow-auto">
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script nonce="{{ csp_nonce() }}">
        const ctx = document.getElementById('myChart');

        // Data from Livewire/PHP
        const data = @js($data['properties']['np_likeness']);

        const datasets = [{
            label: 'COCONUT Data',
            data: data.overall.density_data.map(point => ({
                x: point.x,
                y: point.y
            })),
            borderColor: 'black',
            borderWidth: 2,
            pointRadius: 0,
            tension: 0.4,
            fill: false
        }];

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

        const myChart = new Chart(ctx, {
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
                            text: 'NP-Likeness Score'
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
                        containerID: 'chartLegend'
                    },
                    legend: {
                        display: false
                    }
                }
            },
            plugins: [{
                id: 'htmlLegend',
                beforeInit(chart) {
                    const container = document.getElementById('chartLegend');
                    if (!container) return;

                    // Clear existing legend
                    container.innerHTML = '';

                    const ul = document.createElement('ul');
                    ul.style.listStyle = 'none';
                    ul.style.padding = 0;

                    chart.data.datasets.forEach((dataset, i) => {
                        const li = document.createElement('li');
                        li.style.marginBottom = '8px';
                        li.style.opacity = dataset.hidden ? '0.3' : '1';

                        const button = document.createElement('button');
                        button.style.border = 'none';
                        button.style.background = dataset.borderColor;
                        button.style.width = '12px';
                        button.style.height = '12px';
                        button.style.marginRight = '10px';
                        button.style.cursor = 'pointer';

                        const label = document.createTextNode(dataset.label);

                        li.onclick = () => {
                            dataset.hidden = !dataset.hidden;
                            chart.update();
                            li.style.opacity = dataset.hidden ? '0.3' : '1';
                        };
                        li.style.cursor = 'pointer';
                        li.appendChild(button);
                        li.appendChild(label);
                        ul.appendChild(li);
                    });

                    container.appendChild(ul);
                }
            }]
        });
    </script>

    <style>
        .chart-legend-container {
            margin-top: 7px;
            max-height: 500px;
            overflow-y: auto;
            padding: 10px;
        }

        .chart-legend-container ul {
            margin: 0;
            padding: 0;
        }

        .chart-legend-container li {
            display: flex;
            align-items: center;
        }

        .chart-legend-container button {
            cursor: pointer;
        }
    </style>

</div>