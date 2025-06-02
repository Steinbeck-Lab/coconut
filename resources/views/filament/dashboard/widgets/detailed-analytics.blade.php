@php
    $analyticsData = $this->getAnalyticsData();
@endphp

<x-filament-widgets::widget class="w-full">
    <div class="grid grid-cols-1 gap-6 md:grid-cols-3 w-full">
        @foreach($analyticsData as $key => $data)
        <div class="relative overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
            <div class="p-6">
                @if($data['percentage'] !== null)
                <!-- 2-Column Layout with Progress -->
                <div class="flex items-center justify-between">
                    <!-- Column 1: Title & Value -->
                    <div class="flex-1">
                        <div class="mb-3">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ 
                                    match($key) {
                                        'organisms_iri' => 'Organisms with IRI',
                                        'molecules_organisms' => 'Molecules with Organisms', 
                                        'molecules_citations' => 'Molecules with Citations',
                                        'distinct_geo_locations' => 'Distinct Geo Locations',
                                        'molecules_geolocations' => 'Molecules with Geo Locations',
                                        'revoked_molecules' => 'Revoked Molecules',
                                        default => 'Unknown'
                                    }
                                }}
                            </h3>
                        </div>
                        
                        <!-- Value and Description -->
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $data['value'] }}
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ $data['percentage'] }}{{ $data['description'] }}
                            </p>
                        </div>
                    </div>
                    
                    <!-- Column 2: Progress Circle -->
                    <div class="flex-shrink-0 ml-4">
                        <div class="relative w-16 h-16">
                            <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 36 36">
                                <circle
                                    cx="18"
                                    cy="18"
                                    r="15.915"
                                    fill="transparent"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    class="text-gray-200 dark:text-gray-700"
                                />
                                <circle
                                    cx="18"
                                    cy="18"
                                    r="15.915"
                                    fill="transparent"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                    stroke-dasharray="{{ $data['percentage'] }}, 100"
                                    stroke-linecap="round"
                                    class="text-{{ $data['color'] }}-500"
                                />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-sm font-bold text-{{ $data['color'] }}-600 dark:text-{{ $data['color'] }}-400">
                                    {{ $data['percentage'] }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                @else
                <!-- 2-Column Layout without Progress -->
                <div class="flex items-center">
                    <!-- Column 1: Title & Value -->
                    <div class="flex-1">
                        <div class="mb-3">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ 
                                    match($key) {
                                        'organisms_iri' => 'Organisms with IRI',
                                        'molecules_organisms' => 'Molecules with Organisms', 
                                        'molecules_citations' => 'Molecules with Citations',
                                        'distinct_geo_locations' => 'Distinct Geo Locations',
                                        'molecules_geolocations' => 'Molecules with Geo Locations',
                                        'revoked_molecules' => 'Revoked Molecules',
                                        default => 'Unknown'
                                    }
                                }}
                            </h3>
                        </div>
                        
                        <!-- Value and Description -->
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $data['value'] }}
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ $data['description'] }}
                            </p>
                        </div>
                    </div>
                    
                    <!-- Column 2: Empty space for alignment -->
                    <div class="w-16 flex-shrink-0 ml-4"></div>
                </div>
                @endif
            </div>
            
            <!-- Colored bottom border -->
            <div class="h-1 bg-gradient-to-r from-{{ $data['color'] }}-400 to-{{ $data['color'] }}-600"></div>
        </div>
        @endforeach
    </div>
</x-filament-widgets::widget> 