<?php
if (!defined('INCLUDED_FROM_INDEX')) {
    die('This file must be included from index.php');
}
?>
			var data = [{
				x: <?php echo json_encode($propertyLabels); ?>,
				y: <?php echo json_encode($propertyOccurrences); ?>,
				type: 'bar'
			}];

			Plotly.newPlot('chart', data);
