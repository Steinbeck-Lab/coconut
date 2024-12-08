<div>
    <div id="density-plot"></div>
</div>

<!-- Include D3.js -->
<script src="https://d3js.org/d3.v7.min.js"></script>

<script>
    const data = @js($data);
    const collections = @js($collections);
    console.log(data);
    console.log(collections);

    // Specify the chart's dimensions
    const width = 928;
    const height = 600;
    const marginTop = 20;
    const marginRight = 100;
    const marginBottom = 30;
    const marginLeft = 40;

    // Create the SVG container with viewBox for responsiveness
    const svg = d3.select("#density-plot")
        .append("svg")
        .attr("width", width)
        .attr("height", height)
        .attr("viewBox", [0, 0, width, height])
        .attr("style", "max-width: 100%; height: auto; overflow: visible; font: 10px sans-serif;");

    // Create scales with nice round numbers
    const xScale = d3.scaleLinear()
        .domain(d3.extent(data, d => d.x)).nice()
        .range([marginLeft, width - marginRight]);

    const yScale = d3.scaleLinear()
        .domain(d3.extent(data, d => d.y)).nice()
        .range([height - marginBottom, marginTop]);

    // Add the horizontal axis
    svg.append("g")
        .attr("transform", `translate(0,${height - marginBottom})`)
        .call(d3.axisBottom(xScale).ticks(width / 80).tickSizeOuter(0))
        .call(g => g.append("text")
            .attr("x", width - marginRight)
            .attr("y", -8)
            .attr("fill", "currentColor")
            .attr("text-anchor", "end")
            .text("ALogP →"));

    // Add the vertical axis
    svg.append("g")
        .attr("transform", `translate(${marginLeft},0)`)
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

    // Create line generator with curve
    const line = d3.line()
        .x(d => xScale(d.x))
        .y(d => yScale(d.y))
        .curve(d3.curveBasis);

    // Draw the density line
    const path = svg.append("path")
        .datum(data)
        .attr("fill", "none")
        .attr("stroke", "steelblue")
        .attr("stroke-width", 1.5)
        .attr("stroke-linejoin", "round")
        .attr("stroke-linecap", "round")
        .attr("d", line);

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
        
        // Find the closest point in the data
        const bisect = d3.bisector(d => d.x).left;
        const x0 = xScale.invert(xm);
        const i = bisect(data, x0, 1);
        const d0 = data[i - 1];
        const d1 = data[i];
        const d = x0 - d0.x > d1.x - x0 ? d1 : d0;

        const x = xScale(d.x);
        const y = yScale(d.y);

        dot.attr("transform", `translate(${x},${y})`);
        dot.select("text").text(`Density: ${d.y.toFixed(4)}`);
    }

    function pointerentered() {
        dot.attr("display", null);
    }

    function pointerleft() {
        dot.attr("display", "none");
    }
</script>