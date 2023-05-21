<?php
	if (!defined('INCLUDED_FROM_INDEX')) {
	    die('This file must be included from index.php');
	}

	// Group JSON structures by nested structure
	$groups = [];
	$groupedData = [];

	// Helper function to recursively traverse the data and build grouping keys
	function buildGroupingKey($data) {
		if(is_array($data)) {
			$keys = array_keys($data);
			sort($keys);
			$key = implode('-', $keys);
			return $key;
		} else {
			dier($data);
		}
	}

	// Group JSON structures by nested structure
	foreach ($entries as $data) {
		$data = json_decode(json_encode($data), true);
		$groupingKey = buildGroupingKey($data);
		if (!isset($groups[$groupingKey])) {
			$groups[$groupingKey] = [];
		}
		$groups[$groupingKey][] = $data;
	}

	// Display the grouped JSON structures
	foreach ($groups as $group => $data) {
		echo "Parameter-Group: $group<br>\n";
		/*
		foreach ($data as $item) {
			echo json_encode($item) . "<br>\n";
		}
		 */
		echo "<br>\n";
	}
?>
