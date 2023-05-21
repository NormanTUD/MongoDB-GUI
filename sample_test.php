<?php
	if (!defined('INCLUDED_FROM_INDEX')) {
	    die('This file must be included from index.php');
	}

	// Group JSON structures by nested structure
	$groups = [];
	$groupedData = [];

	// Helper function to recursively traverse the data and build grouping keys
	function buildGroupingKey($data, $path = '') {
		$keyValuePairs = [];

		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$subPath = $path . '[' . $key . ']';
				$subKey = buildGroupingKey($value, $subPath);
				$keyValuePairs[] = $subKey;
			}
		} else {
			$keyValuePairs[] = $path . '=' . $data;
		}

		return implode('-', $keyValuePairs);
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

	/*
	// Display the grouped JSON structures
	foreach ($groups as $group => $data) {
		echo "Parameter-Group: $group<br>\n";
		/*
		foreach ($data as $item) {
			echo json_encode($item) . "<br>\n";
		}
		 * /
		echo "<br>\n";
	}
	 */

	echo "There are ".count($groups)." different groups of documents";
?>
