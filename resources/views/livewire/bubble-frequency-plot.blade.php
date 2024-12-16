{{-- resources/views/livewire/word-bubble-chart.blade.php --}}
<div class="mb-10 ">
    <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl mb-5">
        {!! $name_corrections[$columnName] ?? ucfirst(str_replace('_', ' ', $columnName)) !!}
    </h2>
    <div id="{{$chartId}}" class="chart-container border rounded-lg ml-10 mr-5" style="height: 600px; position: relative;">
        <div class="zoom-controls" style="position: absolute; top: 10px; right: 10px; z-index: 1000; display: flex; gap: 5px;">
            <button class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">+</button>
            <button class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">-</button>
            <button class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded">Reset</button>
        </div>
        <div class="svg-container" style="width: 100%; height: 100%; overflow: auto;">
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.8.5/d3.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const data = @json($chartData);
            const columnName = @json($columnName);
            const searchNames = @json($search_names);

            // Slugify function
            function slugify(text) {
                return text
                    .toString()
                    .toLowerCase()
                    .trim()
                    .replace(/\s+/g, '-') // Replace spaces with -
                    .replace(/[^\w\-]+/g, '') // Remove all non-word chars
                    .replace(/\-\-+/g, '-') // Replace multiple - with single -
                    .replace(/^-+/, '') // Trim - from start of text
                    .replace(/-+$/, ''); // Trim - from end of text
            }

            // Get container dimensions
            const container = d3.select('#{{$chartId}} .svg-container');
            const containerWidth = container.node().getBoundingClientRect().width;
            const containerHeight = container.node().getBoundingClientRect().height;

            // Set base dimensions for the visualization
            const baseWidth = Math.max(containerWidth, 1000);
            const baseHeight = Math.max(containerHeight, 1000);

            // Create SVG with base dimensions
            const svg = container
                .append('svg')
                .attr('width', baseWidth)
                .attr('height', baseHeight)
                .style('min-width', '100%')
                .style('min-height', '100%');

            // Add zoom container
            const g = svg.append('g')
                .attr('transform', `translate(${baseWidth/2},${baseHeight/2})`);

            // Add zoom behavior
            const zoom = d3.zoom()
                .scaleExtent([0.5, 5])
                .on('zoom', (event) => {
                    g.attr('transform', event.transform);

                    // Update SVG dimensions based on zoom
                    const scale = event.transform.k;
                    svg
                        .attr('width', baseWidth * scale)
                        .attr('height', baseHeight * scale);
                });

            svg.call(zoom);

            // Add zoom controls functionality
            const zoomButtons = d3.select('#{{$chartId}}').selectAll('button');

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
                .attr('class', 'bubble')
                .on('click', function(event, d) {
                    // Construct the URL with the slugified column_values
                    const url = `/search?q=${searchNames[columnName]}%3A${slugify(d.column_values)}&page=1&type=filters`;
                    // Open in new tab
                    window.open(url, '_blank');
                });

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

        .svg-container {
            scroll-behavior: smooth;
        }
    </style>
</div>