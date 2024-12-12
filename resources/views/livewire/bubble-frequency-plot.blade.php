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

            // Get container width
            const container = d3.select('#{{$chartId}}');
            const width = container.node().getBoundingClientRect().width;
            const height = width; // Keep it square, or use a specific height if preferred

            // const width = 1200;
            // const height = 1200;

            // Create SVG
            const svg = container
                .append('svg')
                .attr('viewBox', `0 0 ${width} ${height}`)
                .attr('preserveAspectRatio', 'xMidYMid meet')
                .style('width', '100%')
                .style('height', 'auto');

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

            // Create color scale based on total count
            const colorScale = d3.scaleSequential()
                .domain([
                    0,
                    d3.max(data, d => d.first_column_count + d.second_column_count)
                ])
                .interpolator(d3.interpolateBlues);

            // Add split circles
            bubbles.each(function(d) {
                const radius = radiusScale(d.first_column_count + d.second_column_count);
                const g = d3.select(this);



                // Prepare data for pie
                const pieData = pie([{
                        value: d.first_column_count,
                        color: colorScale(d.first_column_count + d.second_column_count)
                    },
                    {
                        value: d.second_column_count,
                        color: colorScale(d.first_column_count + d.second_column_count).replace('rgb(', 'rgba(').replace(')', ', 0.7)') // Slightly transparent for contrast
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
                    // .attr('dy', '-0.2em') // Move text up a bit from center
                    .style('font-size', `${radius * 0.25}px`)
                    .style('fill', '#666')
                    .style('font-weight', 'bold')
                    .text(d => d.column_values);
            });

            // Update bubble positions on simulation tick
            simulation.on('tick', () => {
                bubbles.attr('transform', d => `translate(${d.x},${d.y})`);
            });

            // Create color scale based on total count ranges
function getColor(totalCount) {
    if (totalCount <= 1000) return '#AED6F1';  // Light blue
    if (totalCount <= 2500) return '#5DADE2';  // Medium blue
    if (totalCount <= 5000) return '#27AE60';  // Green
    if (totalCount <= 7500) return '#F4D03F';  // Yellow
    if (totalCount <= 10000) return '#E67E22'; // Orange
    return '#E74C3C';                          // Red
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