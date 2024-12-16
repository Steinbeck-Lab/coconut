<div>
    <div id="heatmap" class="w-full">
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const data = JSON.parse(@js($collectionsData));

        // Clear any existing chart
        d3.select("#heatmap").selectAll("*").remove();

        // Dynamic sizing
        const containerWidth = document.getElementById('heatmap').offsetWidth;
        const margin = {
            top: 60,
            right: 120,
            bottom: 120,
            left: 360
        };
        const width = containerWidth - margin.left - margin.right;
        const height = Math.min(800, width); // Cap height at 800px

        // Create SVG
        const svg = d3.select("#heatmap")
            .append("svg")
            .attr("width", width + margin.left + margin.right)
            .attr("height", height + margin.top + margin.bottom)
            .append("g")
            .attr("transform", `translate(${margin.left},${margin.top})`);

        // Get collection names
        const collections = Object.keys(data);

        // Create scales
        const x = d3.scaleBand()
            .range([0, width])
            .domain(collections)
            .padding(0.05);

        const y = d3.scaleBand()
            .range([0, height])
            .domain(collections)
            .padding(0.05);

        // Custom color scale - using a more visually appealing gradient
        const color = d3.scaleSequential()
            .interpolator(d3.interpolateBlues)
            .domain([0, 100]);

        // Add X axis
        svg.append("g")
            .attr("transform", `translate(0,${height})`)
            .call(d3.axisBottom(x))
            .selectAll("text")
            .attr("transform", "rotate(-45)")
            .style("text-anchor", "end")
            .attr("dx", "-.8em")
            .attr("dy", ".15em")
            .style("font-size", "12px");

        // Add Y axis
        svg.append("g")
            .call(d3.axisLeft(y))
            .selectAll("text")
            .style("font-size", "12px");

        // Create tooltip
        const tooltip = d3.select("#heatmap")
            .append("div")
            .style("position", "absolute")
            .style("visibility", "hidden")
            .style("background-color", "rgba(0, 0, 0, 0.85)")
            .style("color", "white")
            .style("padding", "8px")
            .style("border-radius", "6px")
            .style("font-size", "14px")
            .style("box-shadow", "0 2px 4px rgba(0,0,0,0.2)");

        // Add cells
        collections.forEach(row => {
            collections.forEach(col => {
                svg.append("rect")
                    .attr("x", x(col))
                    .attr("y", y(row))
                    .attr("width", x.bandwidth())
                    .attr("height", y.bandwidth())
                    .style("fill", color(data[row][col]))
                    .style("stroke", "white")
                    .style("stroke-width", 1)
                    .on("mouseover", function(event) {
                        d3.select(this)
                            .style("stroke", "#2563eb")
                            .style("stroke-width", 2);

                        tooltip
                            .style("visibility", "visible")
                            .html(`
                                    <div class="font-bold mb-1">${row} → ${col}</div>
                                    <div>Overlap: ${data[row][col].toFixed(1)}%</div>
                                `)
                            .style("left", (event.pageX + 10) + "px")
                            .style("top", (event.pageY - 10) + "px");
                    })
                    .on("mouseout", function() {
                        d3.select(this)
                            .style("stroke", "white")
                            .style("stroke-width", 1);
                        tooltip.style("visibility", "hidden");
                    });
            });
        });

        // Add title
        svg.append("text")
            .attr("x", width / 2)
            .attr("y", -margin.top / 2)
            .attr("text-anchor", "middle")
            .style("font-size", "20px")
            .style("font-weight", "bold")
            .text("Collection Overlap Heatmap");

        // Add color scale legend
        const legendWidth = Math.min(400, width * 0.6);
        const legendHeight = 15;

        const legendScale = d3.scaleLinear()
            .domain([0, 100])
            .range([0, legendWidth]);

        const legendAxis = d3.axisBottom(legendScale)
            .ticks(5)
            .tickFormat(d => d + "%");

        const legend = svg.append("g")
            .attr("transform", `translate(${(width - legendWidth) / 2},${height + margin.bottom - 30})`);

        // Create gradient
        const defs = legend.append("defs");
        const gradient = defs.append("linearGradient")
            .attr("id", "heatmap-gradient")
            .attr("x1", "0%")
            .attr("x2", "100%")
            .attr("y1", "0%")
            .attr("y2", "0%");

        // Add gradient stops
        const stops = d3.range(0, 1.1, 0.1);
        stops.forEach(stop => {
            gradient.append("stop")
                .attr("offset", stop * 100 + "%")
                .attr("stop-color", color(stop * 100));
        });

        // Add legend rectangle
        legend.append("rect")
            .attr("width", legendWidth)
            .attr("height", legendHeight)
            .style("fill", "url(#heatmap-gradient)");

        // Add legend axis
        legend.append("g")
            .attr("transform", `translate(0,${legendHeight})`)
            .call(legendAxis);

        // Add legend title
        legend.append("text")
            .attr("x", legendWidth / 2)
            .attr("y", legendHeight + 40)
            .attr("text-anchor", "middle")
            .style("font-size", "12px")
            .text("Overlap Percentage");
    });

    // Add resize handler
    window.addEventListener('resize', () => {
        initHeatmap();
    });
</script>