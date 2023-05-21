<?php
include("functions.php");

// Retrieve all entries
$entries = getAllEntries();

$entries_with_geo_coords = [];
foreach ($entries as $entry) {
	$entry = json_decode(json_encode($entry), true);

	if (isset($entry["geocoords"]) && isset($entry["geocoords"]["lat"]) && isset($entry["geocoords"]["lon"])) {
		$lat = $entry["geocoords"]["lat"];
		$lon = $entry["geocoords"]["lon"];

		// Perform additional checks on lat and lon values if needed

		$entries_with_geo_coords[] = $entry;
	}
}

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

<?php
			print $jsCode;
?>

			var options = removeDuplicates(<?php echo json_encode($options); ?>);
			options = [options[0]];

			var filters = removeDuplicates(<?php print json_encode($filters); ?>);

			if(filters.length) {
				$('#builder-basic').queryBuilder({
					plugins: ["bt-tooltip-errors"],
					filters: filters,
					rules: options
				});
				$("#search_stuff").show();
			} else {
				$("#search_stuff").hide();
				console.log("No DB entries found");
			}

			$('#btn-reset').on('click', function () {
				$('#builder-basic').queryBuilder('reset');
			});

			$('#btn-set').on('click', function () {
				$('#builder-basic').queryBuilder('setRules', JSON.parse(options));
			});

			$('#btn-get').on('click', function () {
				var result = $('#builder-basic').queryBuilder('getRules');

				if (!$.isEmptyObject(result)) {
					alert(JSON.stringify(result, null, 2));
				}
			});

  var events = <?php echo json_encode($entries_with_geo_coords); ?>;

  // Generate iframe with events
  var iframeContent = '';
  for (var i = 0; i < events.length; i++) {
    var event = events[i];
    iframeContent += '<p>Event: ' + JSON.stringify(event) + '</p>';
  }

  var iframe = document.createElement('iframe');
  iframe.setAttribute('srcdoc', iframeContent);
  iframe.style.width = '100%';
  iframe.style.height = '400px';
  document.body.appendChild(iframe);

  // Create a map object
  var map = L.map('map').setView([0, 0], 2); // Set initial center and zoom level

  // Add OpenStreetMap tile layer
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors',
    maxZoom: 18
  }).addTo(map);

  // Create a marker cluster group
  var markerCluster = L.markerClusterGroup();

  // Create an array to store heatmap data
  var heatmapData = [];

  // Iterate through the events
  for (var i = 0; i < events.length; i++) {
    var event = events[i];
    var lat = event.geocoords.lat;
    var lon = event.geocoords.lon;

    // Create a marker and add it to the marker cluster group
    var marker = L.marker([lat, lon]);
    markerCluster.addLayer(marker);

    // Add the coordinates to the heatmap data
    heatmapData.push([lat, lon]);
  }

  // Create a heatmap layer
  var heatLayer = L.heatLayer(heatmapData, {
    radius: 25, // Adjust the radius as per your preference
    blur: 15, // Adjust the blur as per your preference
    gradient: {
      0.4: 'blue', // Define the colors and positions in the gradient
      0.6: 'cyan',
      0.7: 'lime',
      0.8: 'yellow',
      1.0: 'red'
    }
  });

  // Add the marker cluster group and the heatmap layer to the map
  markerCluster.addTo(map);
  heatLayer.addTo(map);

  // Fit the map bounds to include both markers and heatmap layer
  map.fitBounds(markerCluster.getBounds());



          var data = [{
            x: <?php echo json_encode($propertyLabels); ?>,
            y: <?php echo json_encode($propertyOccurrences); ?>,
            type: 'bar'
        }];

        Plotly.newPlot('chart', data);
</script>
	</body>
</html>
