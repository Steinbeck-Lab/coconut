{{-- resources/views/livewire/word-bubble-chart.blade.php --}}
<div>
    <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl mb-1">
        {!! $name_corrections[$firstColumnName] ?? ucfirst(str_replace('_', ' ', $firstColumnName)) !!} vs {!! $name_corrections[$secondColumnName] ?? ucfirst(str_replace('_', ' ', $secondColumnName)) !!}
    </h2>
    <div id="{{$chartId}}"></div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.8.5/d3.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const data = @json($chartData);

            const width = 800;
            const height = 600;

            // Create SVG
            const svg = d3.select('#{{$chartId}}')
                .append('svg')
                .attr('width', width)
                .attr('height', height);

            // Calculate radius based on total value
            const radiusScale = d3.scaleSqrt()
                .domain([0, d3.max(data, d => d.first_column_count + d.second_column_count)])
                .range([30, 80]);

            // Create force simulation
            const simulation = d3.forceSimulation(data)
                .force('charge', d3.forceManyBody().strength(5))
                .force('center', d3.forceCenter(width / 2, height / 2))
                .force('collision', d3.forceCollide().radius(d => radiusScale(d.first_column_count + d.second_column_count) + 2));

            // Create container for each bubble
            const bubbles = svg.selectAll('.bubble')
                .data(data)
                .enter()
                .append('g')
                .attr('class', 'bubble');

            // Create pie generator for split circles
            const pie = d3.pie()
                .value(d => d.value)
                .sort(null);

            // Create arc generator
            const arc = d3.arc()
                .innerRadius(0);

            // Add split circles
            bubbles.each(function(d) {
                const radius = radiusScale(d.first_column_count + d.second_column_count);
                const g = d3.select(this);

                // Prepare data for pie
                const pieData = pie([{
                        value: d.first_column_count,
                        color: '#99B3FF'
                    },
                    {
                        value: d.second_column_count,
                        color: '#FFB3B3'
                    }
                ]);

                // Create arcs
                arc.outerRadius(radius);

                g.selectAll('path')
                    .data(pieData)
                    .enter()
                    .append('path')
                    .attr('d', arc)
                    .style('fill', d => d.data.color);

                // Add word label
                g.append('text')
                    .attr('text-anchor', 'middle')
                    .attr('dy', '-0.2em')
                    .style('font-size', `${radius * 0.3}px`)
                    .style('fill', '#333')
                    .text(d.word);

                // Add values label
                g.append('text')
                    .attr('text-anchor', 'middle')
                    .attr('dy', '-0.2em') // Move text up a bit from center
                    .style('font-size', `${radius * 0.25}px`)
                    .style('fill', '#666')
                    .style('font-weight', 'bold')
                    .text(d => d.column_values)
                    .call(wrapText, radius * 1.2);
            });

            // Update bubble positions on simulation tick
            simulation.on('tick', () => {
                bubbles.attr('transform', d => `translate(${d.x},${d.y})`);
            });

            function wrapText(text, width) {
                text.each(function() {
                    const text = d3.select(this);
                    const words = text.text().split(/\s+/);
                    const lineHeight = 1.1;

                    text.text(null);

                    // Calculate total lines for vertical centering
                    let lines = [];
                    let currentLine = [];
                    let tempText = text.append('tspan').attr('x', 0);

                    // First pass: determine number of lines
                    for (let word of words) {
                        currentLine.push(word);
                        tempText.text(currentLine.join(' '));

                        if (tempText.node().getComputedTextLength() > width) {
                            currentLine.pop();
                            lines.push(currentLine.join(' '));
                            currentLine = [word];
                        }
                    }
                    lines.push(currentLine.join(' '));
                    tempText.remove();

                    // Calculate starting position to center vertically
                    const totalLines = lines.length;
                    const startDy = (-((totalLines - 1) * lineHeight) / 2) + 'em';

                    // Second pass: actually create the lines
                    let tspan = text.append('tspan')
                        .attr('x', 0)
                        .attr('dy', startDy)
                        .text(lines[0]);

                    for (let i = 1; i < lines.length; i++) {
                        text.append('tspan')
                            .attr('x', 0)
                            .attr('dy', `${lineHeight}em`)
                            .text(lines[i]);
                    }
                });
            }
        });
    </script>

    <style>
        .bubble:hover {
            opacity: 0.8;
            cursor: pointer;
        }

        .bubble text {
            pointer-events: none;
        }
    </style>
</div>