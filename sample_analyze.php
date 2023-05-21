<?php
	if (!defined('INCLUDED_FROM_INDEX')) {
		die('This file must be included from index.php');
	}
?>
<?php
	// plotly data

	$totalEntries = count($entries);

	// Count the occurrence of each property
	$propertyCounts = array();
	foreach ($entries as $entry) {
	    foreach ($entry as $property => $value) {
		if (!isset($propertyCounts[$property])) {
		    $propertyCounts[$property] = 0;
		}
		$propertyCounts[$property]++;
	    }
	}

	// Identify properties with numerical values
	$numericProperties = array();
	foreach ($entries as $entry) {
	    foreach ($entry as $property => $value) {
		if (is_numeric($value) && !in_array($property, $numericProperties)) {
		    $numericProperties[] = $property;
		}
	    }
	}

	// Generate data for plotting
	$propertyLabels = array_keys($propertyCounts);
	$propertyOccurrences = array_values($propertyCounts);

	// plotly data end

?>
var data = [{
	x: <?php echo json_encode($propertyLabels); ?>,
	y: <?php echo json_encode($propertyOccurrences); ?>,
	type: 'bar'
}];

Plotly.newPlot('chart', data);
