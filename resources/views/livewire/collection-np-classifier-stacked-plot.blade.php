<div class="collection-np-classifier-container">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">NP Classifier Distribution by Collection</h3>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="searchTerm">Search</label>
                        <input type="text" wire:model.debounce.300ms="searchTerm"
                            class="form-control" placeholder="Search collections or classes">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="selectedCollections">Collections</label>
                        <select wire:model="selectedCollections" class="form-control" multiple>
                            @foreach($collections as $collection)
                            <option value="{{ $collection->id }}">{{ $collection->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="limitClasses">Max Classes</label>
                        <select wire:model="limitClasses" class="form-control">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                            <option value="500">500</option>
                            <option value="1000">1000</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="sortBy">Sort Classes By</label>
                        <select wire:model="sortBy" class="form-control">
                            <option value="count">Count</option>
                            <option value="alphabetical">Alphabetical</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="sortScope">Sort Scope</label>
                        <select wire:model="sortScope" class="form-control">
                            <option value="global">Across Collections</option>
                            <option value="local">Within Each Collection</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <button id="np-classifier-apply-filters" wire:click="updateFilters" class="btn btn-primary">Apply Filters</button>
            </div>

            <!-- Loading indicator -->
            <div wire:loading class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>

            <!-- Chart container -->
            <div id="np-classifier-chart-container" wire:key="classifier-chart-{{ count($classifierData['classes'] ?? []) }}-{{ $sortScope }}-{{ $sortBy }}" style="width: 100%; min-height: 600px;"></div>
            <div id="legend-container"></div>
        </div>
        <input type="hidden" id="np-classifier-data" value="{{ json_encode($classifierData) }}">
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.8.5/d3.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initial render
        renderChartFromHiddenInput();

        // Add click listener to the Apply Filters button
        document.querySelector('button[wire\\:click="updateFilters"]').addEventListener('click', function() {
            // Create a promise that resolves when the data is updated
            waitForDataUpdate()
                .then(chartData => {
                    console.log("Data update detected, rendering chart:", chartData);
                    renderChart(chartData);
                })
                .catch(error => {
                    console.error("Error waiting for data update:", error);
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
                console.log("Initial chart render:", chartData);
                renderChart(chartData);
            } catch (e) {
                console.error("Error parsing initial chart data:", e);
            }
        }



        function renderChart(chartData) {
            console.log("Rendering chart with data", chartData);
            if (!chartData || !chartData.data || chartData.data.length === 0) {
                console.log("No data available to render chart");
                d3.select("#np-classifier-chart-container").html("<div style='text-align:center; padding:50px;'>No data available with current filter settings</div>");
                return;
            }

            // Check if the classes property exists and is not empty
            if (!chartData.classes || chartData.classes.length === 0) {
                console.log("No classes available to render chart");
                d3.select("#np-classifier-chart-container").html("<div style='text-align:center; padding:50px;'>No classifier classes found with current filter settings</div>");
                return;
            }

            // Check for collectionSortedClasses
            if (!chartData.collectionSortedClasses) {
                console.log("Missing collection sorted classes data");
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
                console.log("Missing required data for rendering chart:", {
                    hasData: Boolean(chartData.data),
                    hasClasses: Boolean(chartData.classes),
                    hasSortedClasses: Boolean(chartData.collectionSortedClasses)
                });
                return;
            }

            // More detailed debug logging
            console.log("Chart data:", {
                collections: chartData.data.length,
                classes: chartData.classes.length,
                sortScope: chartData.sortScope,
                sortBy: chartData.sortBy,
                sample: chartData.data.length > 0 ? chartData.data[0].title : 'none'
            });

            // Process data for each collection
            data.forEach(collection => {
                const collectionTitle = collection.title;

                // Skip if we don't have sorting information for this collection
                if (!collectionSortedClasses[collectionTitle]) {
                    console.log(`Missing sort order for collection: ${collectionTitle}`);
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

            console.log(`Generated ${barData.length} bar segments for rendering`);

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
        console.log("Livewire loaded");
        Livewire.hook('message.processed', function(message, component) {
            if (component.fingerprint.name === 'collection-np-classifier-stacked-plot') {
                // Re-render the chart with the latest data
                const chartData = @json($classifierData);
                console.log("Data after Livewire update:", chartData);
                renderChart(chartData);
            }
        });
    });
</script>