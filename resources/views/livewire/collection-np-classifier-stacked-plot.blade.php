<div class="collection-np-classifier-container">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">NP Classifier Distribution by Collection</h3>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="searchTerm">Search</label>
                        <input type="text" wire:model.debounce.300ms="searchTerm"
                            class="form-control" placeholder="Search collections or classes">
                    </div>
                </div>
                <div class="col-md-4">
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
            <div id="np-classifier-chart-container" wire:key="classifier-chart-{{ count($classifierData['classes'] ?? []) }}" style="width: 100%; min-height: 600px;"></div>
            <div id="legend-container"></div>
        </div>
        <input type="hidden" id="np-classifier-data" value="{{ json_encode($classifierData) }}">
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.8.5/d3.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Wait a tiny bit for the DOM to be fully ready
        // setTimeout(function() {
        // const chartData = @json($classifierData);
        // renderChart(chartData);
        // }, 10);
        renderChartFromHiddenInput();

        // Add click listener to the Apply Filters button
    document.querySelector('button[wire\\:click="updateFilters"]').addEventListener('click', function() {
        // Check every 100ms for 2 seconds if the data has been updated
        let checkCount = 0;
        const checkInterval = setInterval(function() {
            checkCount++;
            if (checkCount > 20) { // Stop after 2 seconds (20 * 100ms)
                clearInterval(checkInterval);
            }
            renderChartFromHiddenInput();
        }, 100);
    });
    
    function renderChartFromHiddenInput() {
        const dataElement = document.getElementById('np-classifier-data');
        if (!dataElement) return;
        
        try {
            const chartData = JSON.parse(dataElement.value);
            console.log("Chart data from hidden input:", chartData);
            renderChart(chartData);
        } catch (e) {
            console.error("Error parsing chart data:", e);
        }
    }

        // document.getElementById('np-classifier-apply-filters').addEventListener('click', function() {
        //     // Wait a bit for Livewire to process the update
        //     // setTimeout(function() {
        //     const updatedChartData = @json($classifierData);
        //     console.log("Received updated data", updatedChartData);
        //     renderChart(updatedChartData);
        //     // }, 300); // Slightly longer delay to allow Livewire to update
        // });

        // Also listen for Livewire updates
        // document.addEventListener('np-classifier-data-updated', function(event) {
        //     console.log("Received updated data", event.detail.data);
        //     renderChart(event.detail.data);
        // });
        // Livewire.on('chartDataUpdated', function(chartData) {
        //     console.log("Received updated data", chartData);
        //     renderChart(chartData);
        // });



        function renderChart(chartData) {
            // console.log("Rendering chart with data", chartData);
            if (!chartData || !chartData.data || chartData.data.length === 0) {
                console.log("No data available to render chart");
                return;
            }

            // Clear previous chart
            d3.select("#np-classifier-chart-container").html("");
            d3.select("#legend-container").html("");

            const data = chartData.data;
            const keys = chartData.classes;

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

            // Add title
            svg.append("text")
                .attr("x", width / 2)
                .attr("y", -margin.top / 2)
                .attr("text-anchor", "middle")
                .style("font-size", "16px")
                .style("font-weight", "bold")
                .text("NP Classifier Distribution by Collection");

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

            // Stack the data
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

            // Add the bars
            svg.append("g")
                .selectAll("g")
                .data(stackedData)
                .join("g")
                .attr("fill", d => color(d.key))
                .selectAll("rect")
                .data(d => d)
                .join("rect")
                .attr("x", d => x(d.data.title))
                .attr("y", d => y(d[1]))
                .attr("height", d => y(d[0]) - y(d[1]))
                .attr("width", x.bandwidth())
                .on("mouseover", function(event, d) {
                    const key = d3.select(this.parentNode).datum().key;
                    tooltip.style("display", "block")
                        .html(`
                                <div><strong>Collection:</strong> ${d.data.title}</div>
                                <div><strong>Class:</strong> ${key}</div>
                                <div><strong>Count:</strong> ${d.data[key]}</div>
                            `)
                        .style("left", (event.pageX + 10) + "px")
                        .style("top", (event.pageY - 25) + "px");

                    d3.select(this).attr("stroke", "#000").attr("stroke-width", 2);
                })
                .on("mouseout", function() {
                    tooltip.style("display", "none");
                    d3.select(this).attr("stroke", null);
                });

            // Create legend (managed separately for many classes)
            const legendContainer = d3.select("#np-classifier-legend-container");
            const legendItems = legendContainer.selectAll(".legend-item")
                .data(keys)
                .enter()
                .append("div")
                .attr("class", "legend-item")
                .style("display", "flex")
                .style("align-items", "center")
                .style("margin-right", "15px")
                .style("margin-bottom", "5px");

            legendItems.append("div")
                .style("width", "15px")
                .style("height", "15px")
                .style("background-color", d => color(d))
                .style("margin-right", "5px");

            legendItems.append("div")
                .text(d => d)
                .style("font-size", "12px");

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