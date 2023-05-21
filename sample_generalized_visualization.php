<?php
	if (!defined('INCLUDED_FROM_INDEX')) {
	    die('This file must be included from index.php');
	}


	// Define the fields and aggregation functions
	$analyze_fields = array(
		'age' => array(
			'aggregation' => 'average', // Options: 'none', 'average', 'range', 'histogram'
			'analysis' => function ($values) {
				return avg($values);
			}
		),
		'a' => array(
			'aggregation' => 'count', // Options: 'count', 'distinct', 'custom'
			'analysis' => function ($values) {
				return $values;
			}
		)
	);

	$jsCode = generateVisualizationCode($entries, $analyze_fields);
	print "// generateVisualizationCode\n";
	print $jsCode;
	print "// generateVisualizationCode\n";
?>
