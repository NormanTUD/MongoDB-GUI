<?php
$enable_search = 0;

include("functions.php");

// Function to generate JSON structure for jQuery QueryBuilder
function generateQueryBuilderRules() {
    $fields = getAllFields();
    $jsonStructure = [
        "condition" => "AND",
        "rules" => []
    ];

    $i = 0;
    foreach ($fields as $field) {
        // Determine the data type of the field
        $fieldType = getFieldType($field);

	$type = "text";

        // Define operators based on the data type
        $operators = [];
        switch ($fieldType) {
            case 'int':
            case 'integer':
            case 'float':
                $operators = ['equal', 'not_equal', 'less', 'less_or_equal', 'greater', 'greater_or_equal'];
		$type = "number";
                break;
            case 'string':
                $operators = ['equal', 'not_equal', 'contains', 'starts_with', 'ends_with'];
                break;
            // Add additional cases for other data types if needed
        }

	if($i == 0) {
		$jsonStructure['rules'][] = [
		    "id" => $field,
		    "field" => $field,
		    "type" => $fieldType,
		    "input" => $type,
		    "operators" => $operators,
		    "value" => ""
		];
	}

	$i++;

/*
        $jsonStructure['rules'][] = [
            "id" => $field . '.*',
            "field" => $field . '.*',
            "type" => $fieldType,
            "input" => $type,
            "operators" => $operators,
            "value" => ""
        ];
*/
    }

    return json_encode($jsonStructure, JSON_PRETTY_PRINT);
}

function getFieldType($field) {
	// Query the collection to retrieve the value type of the field
	$pipeline = [
		[
			'$project' => [
				'valueType' => ['$type' => '$' . $field]
			]
		],
		[
			'$limit' => 1
		]
	];

	$command = new MongoDB\Driver\Command([
		'aggregate' => $GLOBALS["collectionName"],
		'pipeline' => $pipeline,
		'cursor' => new stdClass(),
	]);

	$cursor = $GLOBALS["mongoClient"]->executeCommand($GLOBALS["databaseName"], $command);
	$result = current($cursor->toArray());

	$fieldType = 'string'; // Default data type if not determined

	if (isset($result->valueType)) {
		switch ($result->valueType) {
		case 'double':
			$fieldType = 'float';
			break;
		case 'int':
			$fieldType = 'integer';
			break;
			// Add cases for other value types as needed
		}
	}

	return $fieldType;
}


function generateQueryBuilderFilters() {
	// Query the collection to retrieve field names
	$pipeline = [
		[
			'$project' => [
				'fields' => ['$objectToArray' => '$$ROOT']
			]
		],
		[
			'$unwind' => '$fields'
		],
		[
			'$group' => [
				'_id' => null,
				'fields' => ['$addToSet' => '$fields.k']
			]
		]
	];

	$command = new MongoDB\Driver\Command([
		'aggregate' => $GLOBALS["collectionName"],
		'pipeline' => $pipeline,
		'cursor' => new stdClass(),
	]);

	$cursor = $GLOBALS["mongoClient"]->executeCommand($GLOBALS["databaseName"], $command);
	$result = current($cursor->toArray());

	$fields = [];
	if (isset($result->fields)) {
		foreach ($result->fields as $field) {
			$fieldType = getFieldType($field);

			$operators = [];
			switch ($fieldType) {
			case 'integer':
			case 'int':
			case 'float':
				$operators = ['equal', 'not_equal', 'less', 'less_or_equal', 'greater', 'greater_or_equal'];
				break;
			case 'string':
				$operators = ['equal', 'not_equal', 'contains', 'starts_with', 'ends_with'];
				break;
				// Add additional cases for other data types if needed
			}

			$filter = [
				'id' => $field,
				'label' => $field,
				'type' => $fieldType,
				'input' => 'text',
				'operators' => $operators,
			];

			$fields[] = $filter;

			$filterSubentry = [
				'id' => $field . '.*',
				'label' => $field . '.*',
				'type' => $fieldType,
				'input' => 'text',
				'operators' => $operators,
			];

			$fields[] = $filterSubentry;
		}
	}

	return json_encode($fields, JSON_PRETTY_PRINT);
}

function getAllFields() {
	// Retrieve all entries from the collection
	$query = new MongoDB\Driver\Query([]);
	$cursor = $GLOBALS["mongoClient"]->executeQuery($GLOBALS["namespace"], $query);
	$entries = $cursor->toArray();

	$fields = [];

	// Iterate over the entries to extract the fields
	foreach ($entries as $entry) {
		$entryData = (array)$entry;
		$fields = array_merge($fields, array_keys($entryData));
	}

	// Remove duplicate fields and sort them alphabetically
	$fields = array_unique($fields);
	sort($fields);

	return $fields;
}

// Function to retrieve all entries from the collection
function getAllEntries() {
	$query = new MongoDB\Driver\Query([]);
	try {
		$cursor = $GLOBALS["mongoClient"]->executeQuery($GLOBALS["namespace"], $query);
	} catch (\Throwable $e) { // For PHP 7
		$serverIP = $_SERVER['SERVER_ADDR'];
		print "There was an error connecting to MongoDB. Are you sure you bound it to 0.0.0.0?<br>\n";
		print "Try, in <code>/etc/mongod.conf</code>, to change the line\n<br>";
		print "<code>bindIp: 127.0.0.1</code>\n<br>";
		print "or:<br>\n";
		print "<code>bindIp: $serverIP</code>\n<br>";
		print "to\n<br>";
		print "<code>bindIp: 0.0.0.0</code>\n<br>";
		print "and then try sudo service mongod restart";
		print "\n<br>\n<br>\n<br>\n";
		print "Error:<br>\n<br>\n";
		print($e);
	}
	$entries = $cursor->toArray();
	return $entries;
}

// Handle form submission for updating an entry
if(isset($_SERVER['REQUEST_METHOD'])) {
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		// Handle form submission for deleting an entry
		if (isset($_POST['delete_entry_id'])) {
			$entryId = $_POST['delete_entry_id'];
			$response = deleteEntry($entryId);
			echo $response;
			exit();
		}

		// Handle form submission for adding a new entry
		if (isset($_POST['new_entry_data'])) {
			$newData = json_decode($_POST['new_entry_data'], true);
			$entryId = (string) new MongoDB\BSON\ObjectID();
			$response = updateEntry($entryId, $newData);
			echo $response;
			exit();
		}

		if(isset($_POST["entry_id"])) {
			$entryId = $_POST['entry_id'];
			$newData = json_decode($_POST['json_data'], true);

			$response = updateEntry($entryId, $newData);
			echo $response;
			exit();
		}
	}
}

// Retrieve all entries
$entries = getAllEntries();
?>

<!DOCTYPE html>
<html>
	<head>
		<title>MongoDB-GUI</title>
		<script src="jquery-3.6.0.min.js"></script>
		<link rel="stylesheet" href="style.css"/>
		<link rel="stylesheet" href="jsoneditor.min.css"/>
		<script src="jsoneditor.min.js"></script>
		<link rel="stylesheet" href="toastr.min.css"/>
		<script src="query-builder.standalone.min.js"></script>
		<link href="query-builder.default.min.css" rel="stylesheet">
		<script src="toastr.min.js"></script>
		<script>
			function log (...args) { console.log(args); }

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

			function deleteEntry(entryId, event=null) {
				if(event) {
					event.stopPropagation();
				}
				$.ajax({
					url: '<?php echo basename($_SERVER['PHP_SELF']); ?>',
						type: 'POST',
						data: {
						delete_entry_id: entryId
					},
					success: function (response) {
						var data = JSON.parse(response);
						if (data.success) {
							toastr.success(data.success);
							// Remove the deleted entry from the page
							$('#entry_' + entryId).remove();
							// Remove the deleted entry's JSON Editor instance
							window['editor_' + entryId].destroy();
							delete window['editor_' + entryId];
						} else if (data.error) {
							toastr.error(data.error);
						}
					},
					error: function () {
						toastr.error('Error deleting entry.');
					}
				});
			}



			// Function to add a new entry via AJAX
			function addNewEntry(event) {
				event.stopPropagation();
				const jsonData = {}; // Set your initial data here
				$.ajax({
				url: '<?php echo basename($_SERVER['PHP_SELF']); ?>',
					type: 'POST',
					data: {
						new_entry_data: JSON.stringify(jsonData)
					},
					success: function (response) {
						var data = JSON.parse(response);
						if (data.success) {
							toastr.success(data.success);
							// Append the new entry to the container
							$('#entry_list').append('<div id="entry_' + data.entryId + '">' +
								'<div id="jsoneditor_' + data.entryId + '"></div>' +
								'<button onclick="deleteEntry(\'' + data.entryId + '\')">Delete</button>' +
								'</div>');
							const newEditor = new JSONEditor(
								document.getElementById('jsoneditor_' + data.entryId),
								{
									mode: 'tree',
										onBlur: function () {
											const updatedJson = newEditor.get();
											const newJsonData = JSON.stringify(updatedJson, null, 2);
											updateEntry(data.entryId, newJsonData);
										}
								}
							);
							newEditor.set(jsonData);
						} else if (data.error) {
							toastr.error(data.error);
						}
					},
					error: function () {
						toastr.error('Error adding new entry.');
					}
				});
			}


			// Function to update an entry via AJAX
			function updateEntry(entryId, jsonData) {
				$.ajax({
					url: '<?php echo basename($_SERVER['PHP_SELF']); ?>',
						type: 'POST',
						data: {
							entry_id: entryId,
							json_data: jsonData
						},
					success: function (response) {
						var data = JSON.parse(response);
						if (data.success) {
							toastr.success(data.success);
						} else if (data.error) {
							toastr.error(data.error);
						}
					},
					error: function () {
						toastr.error('Error updating entry.');
					}
				});
			}

			// Call the initialization function
			$(document).ready(function () {
				initJsonEditors();
			});

		</script>
	</head>
	<body>
		<!-- Display entries -->
<?php
		if($enable_search) {
?>
			<h2>Search</h2>
			<form>
				<div id="builder-basic"></div>
				<button onclick="update_current_query(event)">Create search query</button>
				<div id="current_query"></div>
			</form>
<?php
	}
?>
		<div id="entry_list">
			<?php foreach ($entries as $entry): ?>
				<div id="entry_<?php echo $entry->_id; ?>">
					<div id="jsoneditor_<?php echo $entry->_id; ?>"></div>
					<button onclick="deleteEntry('<?php echo $entry->_id; ?>', event)">Delete</button>
				</div>
			<?php endforeach; ?>
		</div>

		<!-- Button to add a new entry -->
		<button onclick="addNewEntry(event)">Add New Entry</button>
<?php
	if($enable_search) {
?>
		<script>
			var jsonString = <?php echo generateQueryBuilderRules(); ?>; // Assuming $jsonString contains the generated JSON

			$('#builder-basic').queryBuilder({
				plugins: [],
				filters: <?php print(generateQueryBuilderFilters($GLOBALS["collectionName"])); ?>,
				rules: jsonString,
			});

			$('#btn-reset').on('click', function () {
				$('#builder-basic').queryBuilder('reset');
			});

			$('#btn-set').on('click', function () {
				$('#builder-basic').queryBuilder('setRules', JSON.parse(jsonString));
			});

			$('#btn-get').on('click', function () {
				var result = $('#builder-basic').queryBuilder('getRules');

				if (!$.isEmptyObject(result)) {
					alert(JSON.stringify(result, null, 2));
				}
			});

			function update_current_query (e) {
				event.preventDefault();
				e.stopPropagation();
				var rules = $("#builder-basic").queryBuilder("getRules");

				if(rules !== null) {
					delete rules["valid"];
					var rules_string = JSON.stringify(rules);
					$("#current_query").html("<pre>" + rules_string + "</pre>");
				} else {
					$("#current_query").html("<pre>Could not get rules. Some search settings are probably missing. Look out for red highlighted lines.</pre>");
				}
			}
		</script>
<?php
	}
?>
	</body>
</html>
