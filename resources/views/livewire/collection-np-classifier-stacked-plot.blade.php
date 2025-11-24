<div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
    <!-- Header -->
    <div class="px-6 py-4 bg-gradient-to-r from-sky-500 to-sky-700 border-b">
        <h2 class="text-xl font-bold text-white">NP Classifier Distribution by Collection</h2>
    </div>
    
    <!-- Filters Panel -->
    <div class="p-5 bg-gray-50 border-b">
        <h3 class="text-lg font-medium text-gray-700 mb-4 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            Chart Filters
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Search Input -->
            <!-- <div class="space-y-2">
                <label for="searchTerm" class="block text-sm font-medium text-gray-700">Search</label>
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" wire:model.debounce.300ms="searchTerm" id="searchTerm" class="focus:ring-sky-500 focus:border-sky-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md" placeholder="Search collections or classes">
                </div>
            </div> -->
            
            <!-- Collections -->
            <div class="space-y-2">
                <label for="selectedCollections" class="block text-sm font-medium text-gray-700">Collections</label>
                <select wire:model="selectedCollections" id="selectedCollections" multiple class="focus:ring-sky-500 focus:border-sky-500 block w-full sm:text-sm border-gray-300 rounded-md h-36">
                    @foreach($collections as $collection)
                    <option value="{{ $collection->id }}">{{ $collection->title }}</option>
                    @endforeach
                </select>
            </div>
            
            <!-- Max Classes -->
            <div class="space-y-2">
                <label for="limitClasses" class="block text-sm font-medium text-gray-700">Max Classes</label>
                <select wire:model="limitClasses" id="limitClasses" class="focus:ring-sky-500 focus:border-sky-500 block w-full sm:text-sm border-gray-300 rounded-md">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100 (recommended)</option>
                </select>
            </div>
            
            <!-- Sort Classes By -->
            <div class="space-y-2">
                <label for="sortBy" class="block text-sm font-medium text-gray-700">Sort Classes By</label>
                <select wire:model="sortBy" id="sortBy" class="focus:ring-sky-500 focus:border-sky-500 block w-full sm:text-sm border-gray-300 rounded-md">
                    <option value="count">Count</option>
                    <option value="alphabetical">Alphabetical</option>
                </select>
            </div>
            
            <!-- Sort Scope -->
            <div class="space-y-2">
                <label for="sortScope" class="block text-sm font-medium text-gray-700">Sort Scope</label>
                <select wire:model="sortScope" id="sortScope" class="focus:ring-sky-500 focus:border-sky-500 block w-full sm:text-sm border-gray-300 rounded-md">
                    <option value="global">Across Collections</option>
                    <option value="local">Within Each Collection</option>
                </select>
            </div>
        </div>
        
        <!-- Buttons -->
        <div class="mt-5 flex space-x-3">
            <button id="np-classifier-apply-filters" wire:click="updateFilters" type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-sky-600 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-colors duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Apply Filters
            </button>
            <button onclick="resetFilters()" type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-colors duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Reset
            </button>
        </div>
    </div>
    
    <!-- Chart section -->
    <div class="p-6 relative">
        <!-- Loading indicator -->
        <div id="chart-loading-indicator" class="hidden absolute inset-0 flex justify-center items-center bg-white bg-opacity-90 z-20 rounded-lg">
            <div class="flex flex-col items-center">
                <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-grey-600"></div>
                <p class="mt-4 text-sky-700 font-medium">Generating chart...</p>
            </div>
        </div>
        
        <!-- Livewire loading indicator (for data processing) -->
        <!-- <div wire:loading wire:target="updateFilters" class="absolute inset-0 flex justify-center items-center bg-white bg-opacity-90 z-20 rounded-lg">
            <div class="flex flex-col items-center">
                <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-sky-600"></div>
                <p class="mt-4 text-sky-700 font-medium">Processing data...</p>
            </div>
        </div> -->
        
        <!-- Chart title -->
        <!-- <h3 class="text-lg font-medium text-gray-800 mb-6 text-center" id="chart-title">
            NP Classifier Distribution 
            <span class="font-normal text-gray-600">({{ $sortScope == 'global' ? 'Sorted Across All Collections' : 'Sorted Within Each Collection' }}, by {{ $sortBy == 'count' ? 'Count' : 'Name' }})</span>
        </h3> -->
        
        <!-- Chart container -->
        <div id="np-classifier-chart-container" 
             wire:key="classifier-chart-{{ count($classifierData['classes'] ?? []) }}-{{ $sortScope }}-{{ $sortBy }}" 
             class="w-full rounded-lg border border-gray-200 bg-white"
             style="min-height: 500px;">
        </div>
        
        <!-- Legend container -->
        <div id="legend-container" class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200 flex flex-wrap justify-center gap-3"></div>
    </div>
    
    <input type="hidden" id="np-classifier-data" value="{{ json_encode($classifierData) }}">
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.8.5/d3.min.js"></script>
<script nonce="{{ csp_nonce() }}">
    function resetFilters() {
        // Show loading indicator
        showChartLoading();
        
        // For Livewire, we need to reset each filter individually
        @this.set('searchTerm', '');
        @this.set('selectedCollections', []);
        @this.set('limitClasses', 10);
        @this.set('sortBy', 'count');
        @this.set('sortScope', 'global');
        @this.updateFilters();
    }
    
    // Function to update chart title based on current filters
    function updateChartTitle() {
        const sortScope = @this.sortScope;
        const sortBy = @this.sortBy;
        const scopeText = sortScope === 'global' ? 'Sorted Across All Collections' : 'Sorted Within Each Collection';
        const sortText = sortBy === 'count' ? 'Count' : 'Name';
        
        // document.getElementById('chart-title').innerHTML = `
        //     NP Classifier Distribution 
        //     <span class="font-normal text-gray-600">(${scopeText}, by ${sortText})</span>
        // `;
    }
    
    // Show chart loading indicator
    function showChartLoading() {
        const loadingIndicator = document.getElementById('chart-loading-indicator');
        if (loadingIndicator) {
            loadingIndicator.classList.remove('hidden');
        }
    }
    
    // Hide chart loading indicator
    function hideChartLoading() {
        const loadingIndicator = document.getElementById('chart-loading-indicator');
        if (loadingIndicator) {
            loadingIndicator.classList.add('hidden');
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        // Initial render
        renderChartFromHiddenInput();

        // Add click listener to the Apply Filters button
        document.querySelector('button[wire\\:click="updateFilters"]').addEventListener('click', function() {
            // Create a promise that resolves when the data is updated
            waitForDataUpdate()
                .then(chartData => {
                    renderChart(chartData);
                })
                .catch(error => {
                    console.error("Error waiting for data update:", error);
                });
        });

        const applyButton = document.getElementById('np-classifier-apply-filters');
        if (applyButton) {
            applyButton.addEventListener('click', function() {
                showChartLoading();
            });
        }
        
        // Override the original renderChart function to handle loading state
        const originalRenderChart = window.renderChart;
        if (typeof originalRenderChart === 'function') {
            window.renderChart = function(chartData) {
                try {
                    // Show loading indicator before chart rendering starts
                    showChartLoading();
                    
                    // Call the original render function
                    const result = originalRenderChart(chartData);
                    
                    // Hide loading indicator after chart is rendered
                    setTimeout(hideChartLoading, 300); // Small delay to ensure DOM updates
                    
                    return result;
                } catch (error) {
                    console.error("Error rendering chart:", error);
                    hideChartLoading();
                    
                    // Show error message in chart container
                    const chartContainer = document.getElementById('np-classifier-chart-container');
                    if (chartContainer) {
                        chartContainer.innerHTML = `
                            <div class="flex flex-col items-center justify-center h-full p-8 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-red-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h4 class="text-lg font-medium text-gray-800 mb-2">Chart Rendering Error</h4>
                                <p class="text-gray-600">There was a problem rendering the chart. Please try again or contact support if the issue persists.</p>
                            </div>
                        `;
                    }
                }
            };
        }

        document.addEventListener('livewire:load', function() {
        Livewire.on('filtersUpdated', updateChartTitle);
        
        // Handle Livewire rendering completed
        Livewire.hook('message.processed', (message, component) => {
            if (component.fingerprint.name === 'collection-np-classifier-stacked-plot') {
                // Hide loading indicator after Livewire updates
                setTimeout(hideChartLoading, 500);
            }
        });
    });

        // Keep track of the current data for comparison
        let currentDataString = document.getElementById('np-classifier-data')?.value || '';

        function waitForDataUpdate() {
            return new Promise((resolve, reject) => {
                const startTime = Date.now();
                const maxWaitTime = 3000; // 3 seconds max wait

                function checkForUpdate() {
                    const dataElement = document.getElementById('np-classifier-data');
                    if (!dataElement) {
                        return reject(new Error("Data element not found"));
                    }

                    const newDataString = dataElement.value;

                    // Check if data has changed
                    if (newDataString !== currentDataString) {
                        currentDataString = newDataString;
                        try {
                            const chartData = JSON.parse(newDataString);
                            resolve(chartData);
                        } catch (e) {
                            reject(e);
                        }
                        return;
                    }

                    // Check if we've waited too long
                    if (Date.now() - startTime > maxWaitTime) {
                        reject(new Error("Timeout waiting for data update"));
                        return;
                    }

                    // Check again in a short while
                    setTimeout(checkForUpdate, 100);
                }

                // Start checking
                setTimeout(checkForUpdate, 300); // Initial delay to let Livewire start processing
            });
        }

        function renderChartFromHiddenInput() {
            const dataElement = document.getElementById('np-classifier-data');
            if (!dataElement) return;

            try {
                const chartData = JSON.parse(dataElement.value);
                renderChart(chartData);
            } catch (e) {
                console.error("Error parsing initial chart data:", e);
            }
        }

        function renderChart(chartData) {
            if (!chartData || !chartData.data || chartData.data.length === 0) {
                d3.select("#np-classifier-chart-container").html("<div style='text-align:center; padding:50px;'>No data available with current filter settings</div>");
                return;
            }

            // Check if the classes property exists and is not empty
            if (!chartData.classes || chartData.classes.length === 0) {
                d3.select("#np-classifier-chart-container").html("<div style='text-align:center; padding:50px;'>No classifier classes found with current filter settings</div>");
                return;
            }

            // Check for collectionSortedClasses
            if (!chartData.collectionSortedClasses) {
                chartData.collectionSortedClasses = {};
                // Create default sorted classes for each collection
                chartData.data.forEach(collection => {
                    chartData.collectionSortedClasses[collection.title] = chartData.classes;
                });
            }

            // Clear previous chart
            d3.select("#np-classifier-chart-container").html("");
            d3.select("#legend-container").html("");

            const data = chartData.data;
            const keys = chartData.classes;
            const sortScope = chartData.sortScope || 'global';
            const sortBy = chartData.sortBy || 'count';
            const collectionSortedClasses = chartData.collectionSortedClasses || {};

            // Set dimensions and margins
            const margin = {
                top: 40,
                right: 30,
                bottom: 120,
                left: 60
            };
            const width = document.getElementById('np-classifier-chart-container').offsetWidth - margin.left - margin.right;
            const height = 600 - margin.top - margin.bottom;

            // Color scale with a wide range for many categories
            const color = d3.scaleOrdinal()
                .domain(keys)
                .range(d3.quantize(t => d3.interpolateSpectral(1 - t), keys.length));

            // Create SVG
            const svg = d3.select("#np-classifier-chart-container")
                .append("svg")
                .attr("width", width + margin.left + margin.right)
                .attr("height", height + margin.top + margin.bottom)
                .append("g")
                .attr("transform", `translate(${margin.left},${margin.top})`);

            // Add title with sort scope information
            const sortScopeDesc = sortScope === 'global' ? 'Sorted Across All Collections' : 'Sorted Within Each Collection';
            const sortByDesc = sortBy === 'count' ? 'by Count' : 'Alphabetically';
            svg.append("text")
                .attr("x", width / 2)
                .attr("y", -margin.top / 2)
                .attr("text-anchor", "middle")
                .style("font-size", "16px")
                .style("font-weight", "bold")
                .text(`NP Classifier Distribution (${sortScopeDesc}, ${sortByDesc})`);

            // X axis
            const x = d3.scaleBand()
                .domain(data.map(d => d.title))
                .range([0, width])
                .padding(0.2);

            // Add X axis
            svg.append("g")
                .attr("transform", `translate(0,${height})`)
                .call(d3.axisBottom(x).tickSizeOuter(0))
                .selectAll("text")
                .attr("transform", "translate(-10,0)rotate(-45)")
                .style("text-anchor", "end")
                .style("font-size", "10px");

            // Stack the data - we need this for the baseline Y values
            const stackedData = d3.stack()
                .keys(keys)
                (data);

            // Y axis
            const y = d3.scaleLinear()
                .domain([0, d3.max(stackedData[stackedData.length - 1], d => d[1])])
                .range([height, 0]);

            // Add Y axis
            svg.append("g")
                .call(d3.axisLeft(y));

            // Add Y axis label
            svg.append("text")
                .attr("transform", "rotate(-90)")
                .attr("y", -margin.left + 15)
                .attr("x", -height / 2)
                .attr("text-anchor", "middle")
                .text("Count");

            // Create tooltip
            const tooltip = d3.select("body")
                .append("div")
                .style("position", "absolute")
                .style("background-color", "white")
                .style("border", "1px solid #ddd")
                .style("border-radius", "4px")
                .style("padding", "10px")
                .style("display", "none")
                .style("z-index", "10");

            // Function to get the y position for a class in a specific collection
            function getYForClass(collection, className) {
                let yVal = 0;
                // If we're using local sorting, we need to calculate the position based on the collection's sorted order
                if (sortScope === 'local' && collectionSortedClasses[collection.title]) {
                    const sortedClasses = collectionSortedClasses[collection.title];
                    for (let i = 0; i < sortedClasses.length; i++) {
                        const cls = sortedClasses[i];
                        // If we've reached our target class, stop
                        if (cls === className) break;
                        // Add the height of this class to our accumulator
                        yVal += (collection[cls] || 0);
                    }
                    return yVal;
                } else {
                    // For global sorting, use the standard D3 stack data
                    // Find the stack that corresponds to the class name
                    const stackIndex = keys.indexOf(className);
                    if (stackIndex > 0) {
                        // For classes after the first one, use the value from the previous stack as the baseline
                        const previousStack = stackedData[stackIndex - 1];
                        // Find the data point for this collection in the previous stack
                        const collectionIndex = data.findIndex(d => d.title === collection.title);
                        return previousStack[collectionIndex][1]; // Use the upper value
                    }
                    return 0; // First class starts at 0
                }
            }

            // Function to get the height for a class in a specific collection
            function getHeightForClass(collection, className) {
                return collection[className] || 0;
            }

            // Prepare the data for more efficient rendering
            const barData = [];

            // First, check that we have all necessary data
            if (!chartData.data || !chartData.classes || !chartData.collectionSortedClasses) {
                return;
            }

            // Process data for each collection
            data.forEach(collection => {
                const collectionTitle = collection.title;

                // Skip if we don't have sorting information for this collection
                if (!collectionSortedClasses[collectionTitle]) {
                    return;
                }

                const classesToDraw = sortScope === 'local' ?
                    collectionSortedClasses[collectionTitle] :
                    keys;

                let accumulatedHeight = 0;

                // Create bar data for each class in this collection
                classesToDraw.forEach(className => {
                    // Make sure the class exists in our dataset
                    if (!keys.includes(className)) return;

                    const value = collection[className] || 0;

                    // Skip zero-value classes to reduce rendering overhead
                    if (value <= 0) return;

                    if (sortScope === 'local') {
                        // For local sorting, calculate positions manually
                        barData.push({
                            collection: collectionTitle,
                            className: className,
                            x: x(collectionTitle),
                            y: y(accumulatedHeight + value),
                            height: y(accumulatedHeight) - y(accumulatedHeight + value),
                            width: x.bandwidth(),
                            value: value
                        });

                        accumulatedHeight += value;
                    } else {
                        // For global sorting, use standard stack layout
                        const stackIndex = keys.indexOf(className);
                        const collectionIndex = data.findIndex(d => d.title === collectionTitle);

                        if (stackIndex >= 0 && collectionIndex >= 0 && stackedData[stackIndex] && stackedData[stackIndex][collectionIndex]) {
                            const stackItem = stackedData[stackIndex][collectionIndex];

                            barData.push({
                                collection: collectionTitle,
                                className: className,
                                x: x(collectionTitle),
                                y: y(stackItem[1]),
                                height: y(stackItem[0]) - y(stackItem[1]),
                                width: x.bandwidth(),
                                value: value
                            });
                        }
                    }
                });
            });

            // Draw all rectangles in one go for better performance
            svg.selectAll("rect")
                .data(barData)
                .join("rect")
                .attr("x", d => d.x)
                .attr("y", d => d.y)
                .attr("height", d => d.height)
                .attr("width", d => d.width)
                .attr("fill", d => color(d.className))
                .on("mouseover", function(event, d) {
                    tooltip.style("display", "block")
                        .html(`
                    <div><strong>Collection:</strong> ${d.collection}</div>
                    <div><strong>Class:</strong> ${d.className}</div>
                    <div><strong>Count:</strong> ${d.value}</div>
                `)
                        .style("left", (event.pageX + 10) + "px")
                        .style("top", (event.pageY - 25) + "px");
                    d3.select(this).attr("stroke", "#000").attr("stroke-width", 2);
                })
                .on("mouseout", function() {
                    tooltip.style("display", "none");
                    d3.select(this).attr("stroke", null);
                });

            // Create legend container
            const legendDiv = d3.select("#legend-container")
                .style("display", "flex")
                .style("flex-wrap", "wrap")
                .style("margin-top", "20px");

            // Add legend items
            keys.forEach(key => {
                const legendItem = legendDiv.append("div")
                    .style("display", "flex")
                    .style("align-items", "center")
                    .style("margin-right", "15px")
                    .style("margin-bottom", "5px");

                legendItem.append("div")
                    .style("width", "15px")
                    .style("height", "15px")
                    .style("background-color", color(key))
                    .style("margin-right", "5px");

                legendItem.append("div")
                    .text(key)
                    .style("font-size", "12px");
            });

            // Add zoom capability
            const zoom = d3.zoom()
                .scaleExtent([1, 10])
                .on("zoom", (event) => {
                    svg.attr("transform", event.transform);
                });

            d3.select("#np-classifier-chart-container svg")
                .call(zoom);
        }
    });
    document.addEventListener('livewire:load', function() {
        Livewire.hook('message.processed', function(message, component) {
            if (component.fingerprint.name === 'collection-np-classifier-stacked-plot') {
                // Re-render the chart with the latest data
                const chartData = @json($classifierData);
                renderChart(chartData);
            }
        });
    });
</script>