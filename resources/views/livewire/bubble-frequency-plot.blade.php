{{-- resources/views/livewire/word-bubble-chart.blade.php --}}
<div>
    <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl mb-1">
        {!! $name_corrections[$firstColumnName] ?? ucfirst(str_replace('_', ' ', $firstColumnName)) !!} vs {!! $name_corrections[$secondColumnName] ?? ucfirst(str_replace('_', ' ', $secondColumnName)) !!}
    </h2>
    <div id="{{$chartId}}" class="chart-container border rounded-lg" style="height: 600px; position: relative; overflow: auto;">
        <div class="zoom-controls" style="position: sticky; top: 10px; right: 10px; z-index: 1000; display: flex; gap: 5px; justify-content: flex-end; padding-right: 10px;">
            <button class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">+</button>
            <button class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">-</button>
            <button class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">Reset</button>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.8.5/d3.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const data = @json($chartData);

            // Get container width
            const container = d3.select('#{{$chartId}}');
            const containerWidth = container.node().getBoundingClientRect().width;
            const width = Math.max(containerWidth, 1000); // Minimum width to ensure scrolling
            const height = Math.max(600, width); // Minimum height to ensure scrolling

            // Create SVG with larger dimensions
            const svg = container
                .append('svg')
                .attr('width', width)
                .attr('height', height)
                .style('display', 'block'); // Ensure proper sizing

            // Add zoom container
            const g = svg.append('g');

            // Add zoom behavior
            const zoom = d3.zoom()
                .scaleExtent([0.5, 5])
                .on('zoom', (event) => {
                    g.attr('transform', event.transform);
                });

            svg.call(zoom);

            // Add zoom controls functionality
            const zoomButtons = container.selectAll('button');
            
            // Zoom in button
            zoomButtons.nodes()[0].addEventListener('click', () => {
                svg.transition()
                    .duration(300)
                    .call(zoom.scaleBy, 1.3);
            });

            // Zoom out button
            zoomButtons.nodes()[1].addEventListener('click', () => {
                svg.transition()
                    .duration(300)
                    .call(zoom.scaleBy, 0.7);
            });

            // Reset button
            zoomButtons.nodes()[2].addEventListener('click', () => {
                svg.transition()
                    .duration(300)
                    .call(zoom.transform, d3.zoomIdentity);
            });

            // Calculate radius based on total value
            const radiusScale = d3.scaleSqrt()
                .domain([0, d3.max(data, d => d.first_column_count + d.second_column_count)])
                .range([30, 80]);

            // Create force simulation
            const simulation = d3.forceSimulation(data)
                .force('charge', d3.forceManyBody().strength(5))
                .force('center', d3.forceCenter(width / 2, height / 2))
                .force('collision', d3.forceCollide().radius(d => radiusScale(d.first_column_count + d.second_column_count) + 2));

            // Function to determine if color is dark
            function isColorDark(color) {
                const rgb = d3.rgb(color);
                const luminance = (0.299 * rgb.r + 0.587 * rgb.g + 0.114 * rgb.b) / 255;
                return luminance < 0.5;
            }

            // Create pie generator for split circles
            const pie = d3.pie()
                .value(d => d.value)
                .sort(null);

            // Create arc generator
            const arc = d3.arc()
                .innerRadius(0);

            // Create color scale based on total count
            const colorScale = d3.scaleSequential()
                .domain([
                    0,
                    d3.max(data, d => d.first_column_count + d.second_column_count)
                ])
                .interpolator(d3.interpolateBlues);

            // Create container for each bubble
            const bubbles = g.selectAll('.bubble')
                .data(data)
                .enter()
                .append('g')
                .attr('class', 'bubble');

            // Add split circles
            bubbles.each(function(d) {
                const radius = radiusScale(d.first_column_count + d.second_column_count);
                const g = d3.select(this);
                const baseColor = colorScale(d.first_column_count + d.second_column_count);

                // Prepare data for pie
                const pieData = pie([{
                        value: d.first_column_count,
                        color: baseColor
                    },
                    {
                        value: d.second_column_count,
                        color: baseColor.replace('rgb(', 'rgba(').replace(')', ', 0.7)')
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

                // Determine text color based on background
                const textColor = isColorDark(baseColor) ? '#ffffff' : '#333333';

                // Add word label
                g.append('text')
                    .attr('text-anchor', 'middle')
                    .attr('dy', '-0.2em')
                    .style('font-size', `${radius * 0.3}px`)
                    .style('fill', textColor)
                    .text(d.word);

                // Add values label
                g.append('text')
                    .attr('text-anchor', 'middle')
                    .style('font-size', `${radius * 0.25}px`)
                    .style('fill', textColor)
                    .style('font-weight', 'bold')
                    .text(d => d.column_values);
            });

            // Update bubble positions on simulation tick
            simulation.on('tick', () => {
                bubbles.attr('transform', d => `translate(${d.x},${d.y})`);
            });
        });
    </script>

    <style>
        .chart-container {
            scroll-behavior: smooth;
        }

        .bubble:hover {
            opacity: 0.8;
            cursor: pointer;
        }

        .bubble text {
            pointer-events: none;
        }

        .zoom-controls {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 0.5rem;
            padding: 5px;
        }
    </style>
</div>