<div>
   <div id="density-plot"></div>
</div>

 <!-- Include D3.js -->
 <script src="https://d3js.org/d3.v7.min.js"></script>

<script>
    
    const data = @js($data);
    console.log(data);

    const margin = {
        top: 20,
        right: 80,
        bottom: 50,
        left: 60
    };
    const width = 1200 - margin.left - margin.right;
    const height = 500 - margin.top - margin.bottom;

    // Create SVG container
    const svg = d3.select("#density-plot")
        .append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
        .append("g")
        .attr("transform", `translate(${margin.left},${margin.top})`);

    // Set up scales
    const xScale = d3.scaleLinear().range([0, width]);
    const yScale = d3.scaleLinear().range([height, 0]);

    // Set up domains
    xScale.domain(d3.extent(data, d => d.x));
    yScale.domain(d3.extent(data, d => d.y));
        
     // Add X axis
     svg.append("g")
        .attr("transform", `translate(0,${height})`)
        .call(d3.axisBottom(xScale))
        .append("text")

    // Add Y axis
    svg.append("g")
        .call(d3.axisLeft(yScale))

    // Create line generator
    const line = d3.line()
        .x(d => xScale(d.x))
        .y(d => yScale(d.y))
        .curve(d3.curveBasis);

    // Draw overall density line
    svg.append("path")
        .datum(data)
        .attr("d", line)
        .style("fill", "none")
        .style("stroke", "#000")
        .style("stroke-width", 2)
        .style("opacity", 0.5);

</script>