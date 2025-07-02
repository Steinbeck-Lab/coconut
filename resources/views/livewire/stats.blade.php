<div>
    <div class="mt-16 min-h-screen py-16 isolate">
       
        <!-- Statistics Section -->
        <div class="bg-gray-900 mb-16">
            <div class="mx-auto max-w-7xl">
                <div class="grid grid-cols-1 gap-px bg-white/5 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Total Molecules Section -->
                    <a href="/search" class="bg-gray-900 py-6 px-10 relative group">
                        <p class="text-sm font-medium leading-6 text-gray-400 flex items-center">
                            <svg fill="currentColor" version="1.1" class="w-5 h-5 inline mr-1" viewBox="0 0 399.998 399.997">
                                <g><g><path d="M292.41,236.617l-42.814-27.769c5.495-15.665,4.255-33.162-3.707-48.011l35.117-31.373c19.292,12.035,45.001,9.686,61.771-7.085c19.521-19.52,19.521-51.171,0-70.692c-19.522-19.521-51.175-19.521-70.694,0c-15.378,15.378-18.632,38.274-9.788,56.848l-35.121,31.378c-16.812-11.635-38.258-13.669-56.688-6.078l-40.5-55.733c14.528-19.074,13.095-46.421-4.331-63.849c-19.004-19.004-49.816-19.004-68.821,0c-19.005,19.005-19.005,49.818,0,68.822c13.646,13.646,33.374,17.491,50.451,11.545l40.505,55.738c-20.002,23.461-18.936,58.729,3.242,80.906c0.426,0.426,0.864,0.825,1.303,1.237l-39.242,68.874c-16.31-3.857-34.179,0.564-46.899,13.286c-19.521,19.522-19.521,51.175,0,70.694c19.521,19.521,51.173,19.521,70.693,0c19.317-19.315,19.508-50.503,0.593-70.069l39.239-68.867c19.705,5.658,41.737,0.978,57.573-14.033l42.855,27.79c-2.736,12.706,0.821,26.498,10.696,36.372c15.469,15.469,40.544,15.469,56.012,0C329.831,226.518,307.908,225.209,292.41,236.617z"/></g></g>
                            </svg>
                            Unique Molecules
                            <svg class="w-4 h-4 ml-1 text-gray-500 hover:text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </p>
                        <!-- Tooltip -->
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-800 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-10 w-72">
                            <div class="font-semibold mb-1">Unique Molecules Included:</div>
                            <div class="space-y-1 text-left">
                                <div>• Molecules without stereocenters</div>
                                <div>• Molecules with preserved stereochemistry</div>
                                <div>• Molecules with stereocenters where absolute stereochemistry is not defined</div>
                            </div>
                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-gray-800"></div>
                        </div>
                        <p class="mt-2 flex items-baseline gap-x-2">
                            <span class="text-4xl font-semibold tracking-tight text-white">{{ $totalMolecules }}</span>
                        </p>
                    </a>
                    <!-- Total Collections Section -->
                    <a href="/collections" class="bg-gray-900 py-6 px-10">
                        <p class="text-sm font-medium leading-6 text-gray-400">
                            <svg class="w-5 h-5 inline" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M2.5 3.5a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1h-11zm2-2a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1h-7zM0 13a1.5 1.5 0 0 0 1.5 1.5h13A1.5 1.5 0 0 0 16 13V6a1.5 1.5 0 0 0-1.5-1.5h-13A1.5 1.5 0 0 0 0 6v7zm1.5.5A.5.5 0 0 1 1 13V6a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5h-13z" />
                            </svg>
                            Total Collections
                        </p>
                        <p class="mt-2 flex items-baseline gap-x-2">
                            <span class="text-4xl font-semibold tracking-tight text-white">{{ $totalCollections }}</span>
                        </p>
                    </a>
                    <!-- Unique Organisms Section -->
                    <div class="bg-gray-900 py-6 px-10">
                        <p class="text-sm font-medium leading-6 text-gray-400">
                        <svg fill="currentColor" class="w-5 h-5 inline" version="1.1" id="Layer_1"
                            xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                            viewBox="0 0 512 512" xml:space="preserve">
                            <g>
                                <g>
                                    <circle cx="162.914" cy="197.818" r="11.636" />
                                </g>
                            </g>
                            <g>
                                <g>
                                    <circle cx="221.095" cy="221.091" r="11.636" />
                                </g>
                            </g>
                            <g>
                                <g>
                                    <circle cx="209.459" cy="139.636" r="11.636" />
                                </g>
                            </g>
                            <g>
                                <g>
                                    <path d="M453.823,290.909c-6.435,0-11.636,5.213-11.636,11.636c0,25.67-20.876,46.545-46.545,46.545
   c-44.905,0-81.455,36.538-81.455,81.455c0,32.081-26.1,58.182-58.182,58.182c-32.081,0-58.182-26.1-58.182-58.182v-11.636
   c0-6.423-5.201-11.636-11.636-11.636c-44.905,0-81.455-36.538-81.455-81.455V139.636c0-44.916,36.55-81.455,81.455-81.455
   s81.455,36.538,81.455,81.455v186.182c0,28.998-15.604,56.017-40.727,70.54c-5.574,3.212-7.471,10.333-4.259,15.895l17.455,30.231
   c3.212,5.562,10.345,7.459,15.895,4.259c5.574-3.212,7.482-10.333,4.259-15.895l-11.951-20.713
   c8.518-6.295,15.825-13.905,22.004-22.307l20.911,12.067c1.827,1.059,3.828,1.559,5.807,1.559c4.026,0,7.936-2.083,10.089-5.818
   c3.212-5.574,1.303-12.684-4.259-15.895l-20.911-12.067c4.585-10.473,7.494-21.679,8.448-33.292l27.462-9.158
   c6.086-2.036,9.391-8.623,7.354-14.72c-2.036-6.086-8.553-9.414-14.72-7.354l-19.584,6.516v-30.394h23.273
   c6.435,0,11.636-5.213,11.636-11.636S320.621,256,314.186,256h-23.273v-49.792l26.95-8.983c6.086-2.036,9.391-8.622,7.354-14.72
   c-2.036-6.097-8.553-9.414-14.72-7.354l-19.584,6.516v-30.394h23.273c6.435,0,11.636-5.213,11.636-11.636S320.621,128,314.186,128
   h-23.959c-1.187-10.659-3.991-20.841-8.134-30.301l20.771-11.985c5.562-3.212,7.471-10.321,4.259-15.895
   c-3.223-5.574-10.345-7.482-15.895-4.259l-20.852,12.044c-6.237-8.448-13.696-15.895-22.144-22.144l12.044-20.852
   c3.212-5.574,1.303-12.684-4.259-15.895c-5.562-3.212-12.684-1.303-15.895,4.259l-11.985,20.771
   c-9.472-4.166-19.654-6.959-30.313-8.145V11.636C197.823,5.213,192.621,0,186.186,0S174.55,5.213,174.55,11.636v23.959
   c-11.497,1.28-22.388,4.48-32.465,9.181l-23.156-24.064c-4.445-4.631-11.811-4.771-16.442-0.314
   c-4.631,4.457-4.771,11.823-0.314,16.454l19.607,20.375c-7.482,5.865-14.115,12.719-19.77,20.364L81.156,65.559
   c-5.562-3.223-12.684-1.315-15.907,4.259c-3.223,5.574-1.303,12.684,4.271,15.895l20.771,11.985
   c-4.154,9.46-6.959,19.642-8.145,30.301H58.186c-6.435,0-11.636,5.213-11.636,11.636s5.201,11.636,11.636,11.636h23.273v38.156
   l-26.95,8.983c-6.086,2.036-9.391,8.623-7.354,14.72c1.617,4.876,6.156,7.959,11.031,7.959c1.21,0,2.455-0.198,3.677-0.605
   l19.596-6.516V256H58.186c-6.435,0-11.636,5.213-11.636,11.636s5.201,11.636,11.636,11.636h23.273v30.394l-19.596-6.528
   c-6.144-2.06-12.684,1.268-14.72,7.354c-2.036,6.097,1.257,12.684,7.354,14.72l27.357,9.123c0.954,11.799,3.91,23.005,8.46,33.385
   l-20.806,12.009c-5.562,3.223-7.471,10.333-4.259,15.907c2.164,3.735,6.063,5.818,10.089,5.818c1.978,0,3.98-0.5,5.807-1.559
   l20.783-11.997c6.505,8.809,14.394,16.5,23.284,22.9l-13.766,33.036c-2.479,5.935,0.326,12.742,6.26,15.22
   c1.466,0.617,2.979,0.908,4.48,0.908c4.561,0,8.879-2.7,10.74-7.168l12.695-30.452c9.076,3.828,18.769,6.447,28.928,7.575v0.628
   c0,44.916,36.55,81.455,81.455,81.455s81.455-36.538,81.455-81.455c0-32.081,26.1-58.182,58.182-58.182
   c38.505,0,69.818-31.313,69.818-69.818C465.459,296.122,460.258,290.909,453.823,290.909z" />
                                </g>
                            </g>
                            <g>
                                <g>
                                    <path d="M174.55,256c-25.67,0-46.545,20.876-46.545,46.545s20.876,46.545,46.545,46.545c25.67,0,46.545-20.876,46.545-46.545
   S200.22,256,174.55,256z M174.55,325.818c-12.835,0-23.273-10.438-23.273-23.273s10.438-23.273,23.273-23.273
   s23.273,10.438,23.273,23.273S187.385,325.818,174.55,325.818z" />
                                </g>
                            </g>
                        </svg>
                            Unique Organisms
                        </p>
                        <p class="mt-2 flex items-baseline gap-x-2">
                            <span class="text-4xl font-semibold tracking-tight text-white">{{ $uniqueOrganisms }}</span>
                        </p>
                    </div>
                    <!-- Citations Mapped Section -->
                    <div class="bg-gray-900 py-6 px-10">
                        <p class="text-sm font-medium leading-6 text-gray-400">
                            <svg class="w-5 h-5 inline" viewBox="0 0 16 16" fill="currentColor">
                                <path fill-rule="evenodd" d="M6 1h6v7a.5.5 0 0 1-.757.429L9 7.083 6.757 8.43A.5.5 0 0 1 6 8V1z" />
                                <path d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2z" />
                                <path d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1z" />
                            </svg>
                            Citations Mapped
                        </p>
                        <p class="mt-2 flex items-baseline gap-x-2">
                            <span class="text-4xl font-semibold tracking-tight text-white">{{ $citationsMapped }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Analytics Section -->
        <div class="mx-auto max-w-7xl px-6 lg:px-8 mb-16">
            <div class="mx-auto max-w-2xl lg:max-w-none">
                <div class="text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                        Detailed Analytics
                    </h2>
                    <p class="mt-4 text-lg leading-8 text-gray-600">
                        Comprehensive breakdown of database relationships and coverage
                    </p>
                </div>
                <dl class="mt-16 grid grid-cols-1 gap-0.5 overflow-hidden rounded-2xl text-center sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Organisms Statistics -->
                    <div class="flex bg-gray-400/5 p-8">
                        <div class="flex-shrink-0 mr-6">
                            <canvas id="organismsIriChart" width="80" height="80"></canvas>
                        </div>
                        <div class="flex-1 text-left">
                            <dt class="text-sm font-semibold leading-6 text-gray-600">Organisms with IRI</dt>
                            <dd class="text-3xl font-semibold tracking-tight text-gray-900 mt-2">
                                {{ number_format($organismsWithIri) }}
                            </dd>
                            <div class="text-xs text-gray-500 mt-2">
                                {{ round(($organismsWithIri / max($uniqueOrganisms, 1)) * 100, 1) }}% of total organisms
                            </div>
                        </div>
                    </div>

                    <!-- Molecule Relationships -->
                    <div class="flex bg-gray-400/5 p-8">
                        <div class="flex-shrink-0 mr-6">
                            <canvas id="moleculesOrganismsChart" width="80" height="80"></canvas>
                        </div>
                        <div class="flex-1 text-left">
                            <dt class="text-sm font-semibold leading-6 text-gray-600">Molecules with Organisms</dt>
                            <dd class="text-3xl font-semibold tracking-tight text-gray-900 mt-2">
                                {{ number_format($moleculesWithOrganisms) }}
                            </dd>
                            <div class="text-xs text-gray-500 mt-2">
                                {{ round(($moleculesWithOrganisms / max($totalMolecules, 1)) * 100, 1) }}% of total molecules
                            </div>
                        </div>
                    </div>

                    <div class="flex bg-gray-400/5 p-8">
                        <div class="flex-shrink-0 mr-6">
                            <canvas id="moleculesCitationsChart" width="80" height="80"></canvas>
                        </div>
                        <div class="flex-1 text-left">
                            <dt class="text-sm font-semibold leading-6 text-gray-600">Molecules with Citations</dt>
                            <dd class="text-3xl font-semibold tracking-tight text-gray-900 mt-2">
                                {{ number_format($moleculesWithCitations) }}
                            </dd>
                            <div class="text-xs text-gray-500 mt-2">
                                {{ round(($moleculesWithCitations / max($totalMolecules, 1)) * 100, 1) }}% of total molecules
                            </div>
                        </div>
                    </div>

                    <!-- Geographic Statistics -->
                    <div class="flex flex-col bg-gray-400/5 p-8 justify-center">
                        <dt class="text-sm font-semibold leading-6 text-gray-600">Distinct Geo Locations</dt>
                        <dd class="text-3xl font-semibold tracking-tight text-gray-900 mt-2">
                            {{ number_format($distinctGeoLocations) }}
                        </dd>
                    </div>

                    <div class="flex bg-gray-400/5 p-8">
                        <div class="flex-shrink-0 mr-6">
                            <canvas id="moleculesGeoChart" width="80" height="80"></canvas>
                        </div>
                        <div class="flex-1 text-left">
                            <dt class="text-sm font-semibold leading-6 text-gray-600">Molecules with Geo Locations</dt>
                            <dd class="text-3xl font-semibold tracking-tight text-gray-900 mt-2">
                                {{ number_format($moleculesWithGeolocations) }}
                            </dd>
                            <div class="text-xs text-gray-500 mt-2">
                                {{ round(($moleculesWithGeolocations / max($totalMolecules, 1)) * 100, 1) }}% of total molecules
                            </div>
                        </div>
                    </div>

                    <!-- Database Quality -->
                    <a href="/dashboard/molecules?tableFilters[advanced_filter_builder][or_group][mwj][type]=filter_group&activePresetView=revoked&currentPresetView=revoked" class="flex flex-col bg-gray-400/5 p-8 hover:bg-gray-400/10 transition-colors justify-center">
                        <dt class="text-sm font-semibold leading-6 text-gray-600">Revoked Molecules</dt>
                        <dd class="text-3xl font-semibold tracking-tight text-gray-900 mt-2">
                            {{ number_format($revokedMolecules) }}
                        </dd>
                        <div class="text-xs text-gray-500 mt-2">
                            {{ round(($revokedMolecules / max($totalMolecules + $revokedMolecules, 1)) * 100, 1) }}% of all molecules
                        </div>
                    </a>
                </dl>
            </div>
        </div>

        <div class="mx-auto max-w-6xl pb-32 px-8 w-full">
            <livewire:density-plot />
        </div>
        <div class="mx-auto max-w-6xl pb-32 px-8 w-full">
            <livewire:annotation-score-plot />
        </div>
        <div class="mx-auto max-w-6xl pb-32 px-8 w-full">
            <div class=" grid grid-cols-1 md:grid-cols-2 gap-2 p-2">
                @foreach ($properties_json_data as $name => $property)
                    @if ($name != 'np_likeness')
                        @livewire('properties-plot', [
                        'property' => $property,
                        'name' => $name
                        ])
                    @endif
                @endforeach
            </div>
        </div>
        <div class="mx-auto max-w-6xl pb-32 px-8 w-full">
            @foreach ($bubble_frequency_json_data as $chartName => $chartData)
            @livewire('bubble-frequency-plot', [
                'chartName' => $chartName,
                'chartData' => $chartData,
                ])
            @endforeach
        </div>
        <div class="mx-auto max-w-6xl pb-32 px-8 w-full">
            @livewire('collection-overlap')
        </div>
        <div class="mx-auto max-w-6xl pb-32 px-8 w-full">
            @livewire('collection-np-classifier-stacked-plot')
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Chart Configuration Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Plugin to display text in center of doughnut chart
        const centerTextPlugin = {
            id: 'centerText',
            beforeDraw: function(chart) {
                if (!chart.config.options.plugins.centerText || !chart.config.options.plugins.centerText.text) {
                    return;
                }
                
                const width = chart.width;
                const height = chart.height;
                const ctx = chart.ctx;
                
                ctx.restore();
                const fontSize = (height / 150).toFixed(2);
                ctx.font = fontSize + "em Arial";
                ctx.textBaseline = "middle";
                ctx.fillStyle = "#374151";
                
                const percentage = chart.config.options.plugins.centerText.text;
                const textX = Math.round((width - ctx.measureText(percentage).width) / 2);
                const textY = height / 2;
                
                ctx.fillText(percentage, textX, textY);
                ctx.save();
            }
        };

        Chart.register(centerTextPlugin);

        // Helper function to create chart
        function createChart(canvasId, data, backgroundColor, percentage) {
            return new Chart(document.getElementById(canvasId), {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: data,
                        backgroundColor: backgroundColor,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false },
                        centerText: { text: percentage + '%' }
                    },
                    cutout: '60%'
                }
            });
        }

        // Create all charts with calculated data
        const organismsWithIri = {{ $organismsWithIri }};
        const uniqueOrganisms = {{ $uniqueOrganisms }};
        const organismsPercentage = Math.round((organismsWithIri / Math.max(uniqueOrganisms, 1)) * 100 * 10) / 10;
        createChart('organismsIriChart', 
            [organismsWithIri, Math.max(uniqueOrganisms - organismsWithIri, 0)],
            ['#3b82f6', '#e5e7eb'],
            organismsPercentage
        );

        const moleculesWithOrganisms = {{ $moleculesWithOrganisms }};
        const totalMolecules = {{ $totalMolecules }};
        const moleculesOrganismsPercentage = Math.round((moleculesWithOrganisms / Math.max(totalMolecules, 1)) * 100 * 10) / 10;
        createChart('moleculesOrganismsChart',
            [moleculesWithOrganisms, Math.max(totalMolecules - moleculesWithOrganisms, 0)],
            ['#10b981', '#e5e7eb'],
            moleculesOrganismsPercentage
        );

        const moleculesWithCitations = {{ $moleculesWithCitations }};
        const moleculesCitationsPercentage = Math.round((moleculesWithCitations / Math.max(totalMolecules, 1)) * 100 * 10) / 10;
        createChart('moleculesCitationsChart',
            [moleculesWithCitations, Math.max(totalMolecules - moleculesWithCitations, 0)],
            ['#f59e0b', '#e5e7eb'],
            moleculesCitationsPercentage
        );

        const moleculesWithGeolocations = {{ $moleculesWithGeolocations }};
        const moleculesGeoPercentage = Math.round((moleculesWithGeolocations / Math.max(totalMolecules, 1)) * 100 * 10) / 10;
        createChart('moleculesGeoChart',
            [moleculesWithGeolocations, Math.max(totalMolecules - moleculesWithGeolocations, 0)],
            ['#06b6d4', '#e5e7eb'],
            moleculesGeoPercentage
        );
    });
</script>