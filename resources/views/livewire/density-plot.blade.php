<div>
    <div id="density-plot"></div>
</div>

<!-- Include D3.js -->
<script src="https://d3js.org/d3.v7.min.js"></script>

<script>
    // Get the data and prepare it for plotting
    const overallData = @js($data);
    const collectionsData = @js($collections);

    // Transform collections data into a format suitable for D3
    const collections = Object.entries(collectionsData).map(([name, data]) => ({
        name,
        values: data.density_data
    }));

    // Specify the chart's dimensions
    const width = 1690;
    const height = 650;
    const marginTop = 100;
    const marginRight = 150; // Increased for legend
    const marginBottom = 30;
    const marginLeft = 100;

    // Create the SVG container with viewBox for responsiveness
    const svg = d3.select("#density-plot")
        .append("svg")
        .attr("width", width)
        .attr("height", height)
        .attr("viewBox", [0, 0, width, height])
        .attr("style", "max-width: 100%; height: auto; overflow: visible; font: 10px sans-serif;");

    // Get all x and y values for proper scaling
    const allValues = [overallData, ...collections.map(d => d.values)].flat();

    // Create scales with nice round numbers
    const xScale = d3.scaleLinear()
        .domain(d3.extent(allValues, d => d.x)).nice()
        .range([marginLeft, width - marginRight]);

    const yScale = d3.scaleLinear()
        .domain([0, d3.max(allValues, d => d.y)]).nice()
        .range([height - marginBottom, marginTop]);

    // Color scale for collections
    const colorScale = d3.scaleOrdinal(d3.schemeCategory10);

    // Add the horizontal axis
    svg.append("g")
        .attr("transform", `translate(0,${height - marginBottom + marginTop})`)
        .call(d3.axisBottom(xScale).ticks(width / 80).tickSizeOuter(0))
        .call(g => g.append("text")
            .attr("x", width - marginRight)
            .attr("y", -8)
            .attr("fill", "currentColor")
            .attr("text-anchor", "end")
            .text("NP-likeness →"));

    // Add the vertical axis
    svg.append("g")
        .attr("transform", `translate(${marginLeft},${marginTop})`)
        .call(d3.axisLeft(yScale))
        .call(g => g.select(".domain").remove())
        .call(g => g.selectAll(".tick line").clone()
            .attr("x2", width - marginLeft - marginRight)
            .attr("stroke-opacity", 0.1))
        .call(g => g.append("text")
            .attr("x", -marginLeft)
            .attr("y", 10)
            .attr("fill", "currentColor")
            .attr("text-anchor", "start")
            .text("↑ Density"));

    // Create line generator
    const line = d3.line()
        .x(d => xScale(d.x))
        .y(d => yScale(d.y))
        .curve(d3.curveBasis);

    // Draw the overall density line
    const overallPath = svg.append("path")
        .datum(overallData)
        .attr("fill", "none")
        .attr("stroke", "black")
        .attr("stroke-width", 2.5)
        .attr("stroke-linejoin", "round")
        .attr("stroke-linecap", "round")
        .attr("d", line)
        .attr("data-name", "Overall");

    // Draw collection lines
    const collectionPaths = svg.append("g")
        .selectAll("path")
        .data(collections)
        .join("path")
        .attr("fill", "none")
        // .attr("stroke", "steelblue")
        .attr("stroke", d => colorScale(d.name))
        .attr("stroke-width", 1)
        .attr("stroke-linejoin", "round")
        .attr("stroke-linecap", "round")
        .attr("d", d => line(d.values))
        .attr("data-name", d => d.name)
        .style("mix-blend-mode", "multiply");

    // Add legend
    const legend = svg.append("g")
        .attr("font-family", "sans-serif")
        .attr("font-size", 10)
        .attr("text-anchor", "start")
        .selectAll("g")
        .data(['Overall', ...collections.map(d => d.name)])
        .join("g")
        .attr("transform", (d, i) => `translate(${width - marginRight + 10},${marginTop + i * 20})`);

    legend.append("line")
        .attr("x1", 0)
        .attr("x2", 20)
        .attr("stroke", d => d === 'Overall' ? "black" : colorScale(d));

    legend.append("text")
        .attr("x", 25)
        .attr("y", 0.31)
        .attr("dy", "0.35em")
        .text(d => d);

    // Add interactive tooltip
    const dot = svg.append("g")
        .attr("display", "none");

    dot.append("circle")
        .attr("r", 2.5);

    dot.append("text")
        .attr("text-anchor", "middle")
        .attr("y", -8);

    // Add interactive behaviors
    svg.on("pointerenter", pointerentered)
        .on("pointermove", pointermoved)
        .on("pointerleave", pointerleft)
        .on("touchstart", event => event.preventDefault());

    function pointermoved(event) {
        const [xm, ym] = d3.pointer(event);

        // Helper function to interpolate points along the curve
        function interpolatePoints(points, numPoints = 10) {
            const interpolated = [];
            for (let i = 0; i < points.length - 1; i++) {
                const p1 = points[i];
                const p2 = points[i + 1];

                // Create numPoints points between p1 and p2
                for (let j = 0; j < numPoints; j++) {
                    const t = j / numPoints;
                    interpolated.push({
                        x: p1.x + (p2.x - p1.x) * t,
                        y: p1.y + (p2.y - p1.y) * t
                    });
                }
            }
            // Add the last point
            interpolated.push(points[points.length - 1]);
            return interpolated;
        }

        // Create interpolated datasets
        const allPoints = [{
                name: 'Overall',
                points: interpolatePoints(overallData, 20) // Increase points for smoother curves
            },
            ...collections.map(c => ({
                name: c.name,
                points: interpolatePoints(c.values, 20)
            }))
        ];

        // Find the closest point using Euclidean distance
        let minDistance = Infinity;
        let closestPoint = null;
        let closestName = null;

        allPoints.forEach(({
            name,
            points
        }) => {
            points.forEach(point => {
                const xPos = xScale(point.x);
                const yPos = yScale(point.y);
                const distance = Math.sqrt(Math.pow(xPos - xm, 2) + Math.pow(yPos - ym, 2));

                if (distance < minDistance) {
                    minDistance = distance;
                    closestPoint = point;
                    closestName = name;
                }
            });
        });

        if (closestPoint) {
            const x = xScale(closestPoint.x);
            const y = yScale(closestPoint.y);

            dot.attr("transform", `translate(${x},${y})`);
            dot.select("circle")
                .attr("fill", closestName === 'Overall' ? "black" : colorScale(closestName));
            dot.select("text")
                .text(`${closestName}: ${closestPoint.y.toFixed(4)}`)
                .attr("dy", "-10px");

            svg.selectAll("path")
                .style("opacity", d => {
                    const pathName = d?.name || (d === overallData ? "Overall" : null);
                    return pathName === closestName ? 1 : 0.2;
                });
        }
    }

    function pointerentered() {
        dot.attr("display", null);
    }

    function pointerleft() {
        dot.attr("display", "none");
        // Reset line opacities
        svg.selectAll("path").style("opacity", 1);
    }
</script>