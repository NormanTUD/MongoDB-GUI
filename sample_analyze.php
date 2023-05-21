			var data = [{
				x: <?php echo json_encode($propertyLabels); ?>,
				y: <?php echo json_encode($propertyOccurrences); ?>,
				type: 'bar'
			}];

			Plotly.newPlot('chart', data);
