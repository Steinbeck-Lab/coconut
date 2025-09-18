<div>
    <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl mb-5">
        Collection Overlap Heatmap
    </h2>
    <div id="heatmap" class="w-full">
    </div>
</div>

<script>
function initHeatmap() {
    const data = JSON.parse(@js($collectionsData));

    // Clear any existing chart
    d3.select("#heatmap").selectAll("*").remove();

    // Get unique collection names and sort by size
    const collectionNames = [...new Set(
        Object.keys(data.ol_d).map(key => key.split('|')[1])
    )].sort((a, b) => {
        const aKey = Object.keys(data.ol_d).find(k => k.endsWith(a));
        const bKey = Object.keys(data.ol_d).find(k => k.endsWith(b));
        return data.ol_s[bKey][bKey].c1_count - data.ol_s[aKey][aKey].c1_count;
    });

    // Dynamic sizing
    const containerWidth = document.getElementById('heatmap').offsetWidth;
    const margin = {
        top: 10,
        right: 60,
        bottom: 120,
        left: 120
    };
    const width = containerWidth - margin.left - margin.right;
    const height = Math.min(800, width);

    // Create SVG
    const svg = d3.select("#heatmap")
        .append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
        .append("g")
        .attr("transform", `translate(${margin.left},${margin.top})`);

    // Create scales
    const x = d3.scaleBand()
        .range([0, width])
        .domain(collectionNames)
        .padding(0.05);

    const y = d3.scaleBand()
        .range([0, height])
        .domain(collectionNames)
        .padding(0.05);

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
        .style("padding", "12px")
        .style("border-radius", "6px")
        .style("font-size", "14px")
        .style("box-shadow", "0 4px 6px rgba(0,0,0,0.3)")
        .style("max-width", "300px");

    // Add cells
    Object.keys(data.ol_d).forEach(rowKey => {
        const rowName = rowKey.split('|')[1];
        
        Object.keys(data.ol_d[rowKey]).forEach(colKey => {
            const colName = colKey.split('|')[1];
            const stats = data.ol_s[rowKey][colKey];
            
            // The cell value represents how much of the x-axis dataset (colName) 
            // is contained in the y-axis dataset (rowName)
            const overlapPercentage = stats.c2_in_c1_p;

            svg.append("rect")
                .attr("x", x(colName))
                .attr("y", y(rowName))
                .attr("width", x.bandwidth())
                .attr("height", y.bandwidth())
                .style("fill", color(overlapPercentage))
                .style("stroke", "white")
                .style("stroke-width", 1)
                .on("mouseover", function(event) {
                    d3.select(this)
                        .style("stroke", "#2563eb")
                        .style("stroke-width", 2);

                    tooltip
                        .style("visibility", "visible")
                        .html(`
                            <div class="font-bold mb-2">${rowName} ⟷ ${colName}</div>
                            <div>${colName} in ${rowName}: ${overlapPercentage.toFixed(1)}%</div>
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

    // Add vertical legend
    const legendWidth = 20;
    const legendHeight = height * 0.6;

    const legend = svg.append("g")
        .attr("transform", `translate(${width + 40},${(height - legendHeight) / 2})`);

    const defs = legend.append("defs");
    const gradient = defs.append("linearGradient")
        .attr("id", "heatmap-gradient")
        .attr("x1", "0%")
        .attr("x2", "0%")
        .attr("y1", "100%")
        .attr("y2", "0%");

    const stops = d3.range(0, 1.1, 0.1);
    stops.forEach(stop => {
        gradient.append("stop")
            .attr("offset", stop * 100 + "%")
            .attr("stop-color", color(stop * 100));
    });

    legend.append("rect")
        .attr("width", legendWidth)
        .attr("height", legendHeight)
        .style("fill", "url(#heatmap-gradient)");

    const legendScale = d3.scaleLinear()
        .domain([0, 100])
        .range([legendHeight, 0]);

    const legendAxis = d3.axisRight(legendScale)
        .ticks(5)
        .tickFormat(d => d + "%");

    legend.append("g")
        .attr("transform", `translate(${legendWidth},0)`)
        .call(legendAxis);

    legend.append("text")
        .attr("transform", "rotate(-90)")
        .attr("x", -legendHeight / 2)
        .attr("y", -30)
        .attr("text-anchor", "middle")
        .style("font-size", "12px")
        .text("Overlap Percentage");
}

// Initialize the heatmap when the DOM is loaded
document.addEventListener('DOMContentLoaded', initHeatmap);

// Add resize handler
window.addEventListener('resize', () => {
    initHeatmap();
});</script>