<?php
	define('INCLUDED_FROM_INDEX', true);
	include("functions.php");

	// Retrieve all entries
	$entries = getAllEntries();

	$latlng = [];
	foreach ($entries as $entry) {
		$n = find_lat_lon_variables_recursive($entry);
		if(count($n)) {
			$latlng[] = $n;
		}
	}

	#dier($latlng);
?>

<!DOCTYPE html>
<html>
	<head>
		<title>MongoDB-GUI</title>
		<script>
			var PHP_SELF = '<?php echo basename($_SERVER['PHP_SELF']); ?>';
		</script>
		<?php include("headers.php"); ?>
	</head>
	<body>
		<div id="map" style="height: 400px;"></div>

		<div id="search_stuff">
			<form>
				<div id="builder-basic"></div>
				<button onclick="update_current_query(event);searchEntries()">Search</button>
				<button onclick="resetSearch(event)">Reset Search</button>
				<div id="current_query"></div>
			</form>
		</div>


		<b><?php print $GLOBALS["databaseName"].".".$GLOBALS["collectionName"]; ?> on <?php print $GLOBALS["mongodbHost"].":".$GLOBALS["mongodbPort"]; ?></b><br>

		<div id="entry_list">
		</div>
		<button onclick="addNewEntry(event)">Add New Entry</button>

		<div id="chart"></div>
		<div id="chart_two"></div>
<?php
		include("import.php");
?>
		<script>
			"use strict";
			<?php include("initialize_query_builder.php"); ?>
			<?php include("sample_generalized_visualization.php"); ?>
			<?php include("sample_map.php"); ?>
			<?php include("sample_analyze.php"); ?>
		</script>
		<?php include("sample_test.php"); ?>
	</body>
</html>
