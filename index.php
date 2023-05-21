<?php
define('INCLUDED_FROM_INDEX', true);
include("functions.php");

// Retrieve all entries
$entries = getAllEntries();

$entries_with_geo_coords = get_entries_with_geo_coordinates($entries);
?>

<!DOCTYPE html>
<html>
	<head>
		<title>MongoDB-GUI</title>
		<script>
			var PHP_SELF = '<?php echo basename($_SERVER['PHP_SELF']); ?>';
		</script>
		<?php include("headers.php"); ?>
		<script>
			var focus_log = {};

			// Initialize JSON Editor for each entry
			function initJsonEditors() {
				<?php foreach ($entries as $entry): ?>
				const editor_<?php echo $entry->_id; ?> = new JSONEditor(
				document.getElementById('jsoneditor_<?php echo $entry->_id; ?>'),
					{
						onFocus: function () {
							focus_log["<?php echo $entry->_id; ?>"] = true;
						},
						mode: 'tree',
						onBlur: function () {
							if("<?php echo $entry->_id; ?>" in focus_log && focus_log["<?php echo $entry->_id; ?>"] == true) {
								const updatedJson = editor_<?php echo $entry->_id; ?>.get();
								const jsonData = JSON.stringify(updatedJson, null, 2);
								const entryId = '<?php echo $entry->_id; ?>';
								updateEntry(entryId, jsonData);
								focus_log["<?php echo $entry->_id; ?>"] = false;
							}
						}
					}
				);

				    editor_<?php echo $entry->_id; ?>.set(<?php echo json_encode($entry, JSON_UNESCAPED_UNICODE); ?>);
			    <?php endforeach; ?>
			}

			$(document).ready(function () {
				initJsonEditors();

				// Check if the 'search' parameter exists in the URL
				var urlParams = new URLSearchParams(window.location.search);
				if (urlParams.has('search')) {
					var searchParam = urlParams.get('search');
					try {
						var query = JSON.parse(decodeURIComponent(searchParam));

						// Set the query rules in the query builder
						$("#builder-basic").queryBuilder("setRules", query);

						// Trigger the search
						searchEntries();
					} catch (e) {
						alert("ERROR: Could not parse search string from url");
						console.error("ERROR: Could not parse search string from url");
						console.error(e);
					}
				}
			});
		</script>
	</head>
	<body>
		<div id="search_stuff">
			<h3>Search</h3>
			<form>
				<div id="builder-basic"></div>
				<button onclick="update_current_query(event);searchEntries()">Search</button>
				<button onclick="resetSearch(event)">Reset Search</button>
				<div id="current_query"></div>
			</form>
		</div>


		<h3><?php print $GLOBALS["databaseName"].".".$GLOBALS["collectionName"]; ?> on <?php print $GLOBALS["mongodbHost"].":".$GLOBALS["mongodbPort"]; ?></h3>

		<div id="entry_list">
			<?php foreach ($entries as $entry): ?>
				<div id="entry_<?php echo $entry->_id; ?>">
					<div id="jsoneditor_<?php echo $entry->_id; ?>"></div>
					<button onclick="deleteEntry('<?php echo $entry->_id; ?>', event)">Delete</button>
				</div>
			<?php endforeach; ?>
		</div>

		<div id="chart"></div>

		<div id="chart_two"></div>

		<div id="map" style="height: 400px;"></div>

		<!-- Button to add a new entry -->
		<button onclick="addNewEntry(event)">Add New Entry</button>
<?php
		include("import.php");
		$optionsAndFilters = generateQueryBuilderOptions();
		$options = $optionsAndFilters["options"];
		$filters = $optionsAndFilters["filters"];


		// Define the fields and aggregation functions
$analyze_fields = array(
    'geocoords' => array(
        'aggregation' => 'none', // Options: 'none', 'average', 'range', 'histogram'
        'analysis' => function ($values) {
            // Your custom analysis function for 'geocoords'
            // Example: Return the list of coordinates as-is
            return $values;
        }
    ),
    'a' => array(
        'aggregation' => 'count', // Options: 'count', 'distinct', 'custom'
        'analysis' => function ($values) {
            // Your custom analysis function for 'a'
            // Example: Count the occurrences of each value
            $valueCounts = array_count_values($values);
            return $valueCounts;
        }
    ),
    'b' => array(
        'aggregation' => 'none',
        'analysis' => function ($values) {
            // Your custom analysis function for 'b'
            // Example: Return the values as-is
            return $values;
        }
    ),
    // Add more fields and their corresponding configurations here
);

$jsCode = generateVisualizationCode($entries, $analyze_fields);
?>
<script>
			"use strict";

			//jsCode {
<?php
			print $jsCode;
?>
			//} jsCode


			<?php include("initialize_query_builder.php"); ?>
			<?php include("sample_map.php"); ?>
			<?php include("sample_analyze.php"); ?>
		</script>
	</body>
</html>
