<?php
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
	throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}

function dier ($msg) {
	print_r($msg);
	exit(1);
}

function read_first_line_of_file_or_die ($file) {
	if (file_exists($file)) {
		$handle = fopen($file, "r");
		$firstLine = fgets($handle);
		fclose($handle);

		$firstLine = trim($firstLine);

		// Use the $firstLine variable here
		return $firstLine;
	} else {
		dier("File $file does not exist.");
	}
}

set_error_handler("exception_error_handler");
ini_set('display_errors', '1');

// MongoDB connection settings
$mongodbHost = 'localhost';
$mongodbPort = 27017;
$databaseName = read_first_line_of_file_or_die("dbname");
$collectionName =  read_first_line_of_file_or_die("collname");

// Connect to MongoDB
$mongoClient = new MongoDB\Driver\Manager("mongodb://{$mongodbHost}:{$mongodbPort}");
$namespace = "{$databaseName}.{$collectionName}";

// Function to generate JSON structure for jQuery QueryBuilder
function generateQueryBuilderStructure() {
    $fields = getAllFields();

    $jsonStructure = [
        "condition" => "AND",
        "rules" => []
    ];

    foreach ($fields as $field) {
        // Determine the data type of the field
        $fieldType = getFieldType($field);

        // Define operators based on the data type
        $operators = [];
        switch ($fieldType) {
            case 'int':
            case 'integer':
            case 'float':
                $operators = ['equal', 'not_equal', 'less', 'less_or_equal', 'greater', 'greater_or_equal'];
                break;
            case 'string':
                $operators = ['equal', 'not_equal', 'contains', 'starts_with', 'ends_with'];
                break;
            // Add additional cases for other data types if needed
        }

        $jsonStructure['rules'][] = [
            "id" => $field,
            "field" => $field,
            "type" => $fieldType,
            "input" => "text",
            "operators" => $operators,
            "value" => ""
        ];
    }

    return json_encode($jsonStructure, JSON_PRETTY_PRINT);
}

function getFieldType($field) {
    global $collectionName, $mongoClient, $namespace, $databaseName;

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
        'aggregate' => $collectionName,
        'pipeline' => $pipeline,
        'cursor' => new stdClass(),
    ]);

    $cursor = $mongoClient->executeCommand($databaseName, $command);
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
    global $collectionName, $mongoClient, $namespace, $databaseName;

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
        'aggregate' => $collectionName,
        'pipeline' => $pipeline,
        'cursor' => new stdClass(),
    ]);

    $cursor = $mongoClient->executeCommand($databaseName, $command);
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
        }
    }

    return json_encode($fields, JSON_PRETTY_PRINT);
}

function getAllFields() {
    global $mongoClient, $namespace;

    // Retrieve all entries from the collection
    $query = new MongoDB\Driver\Query([]);
    $cursor = $mongoClient->executeQuery($namespace, $query);
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
    global $mongoClient, $namespace;
    $query = new MongoDB\Driver\Query([]);
    $cursor = $mongoClient->executeQuery($namespace, $query);
    $entries = $cursor->toArray();
    return $entries;
}

// Function to delete an entry by ID
function deleteEntry($entryId) {
    global $mongoClient, $namespace;
    $bulkWrite = new MongoDB\Driver\BulkWrite();
    $filter = ['_id' => new MongoDB\BSON\ObjectID($entryId)];

    $bulkWrite->delete($filter);

    try {
        $mongoClient->executeBulkWrite($namespace, $bulkWrite);
        return json_encode(['success' => 'Entry deleted successfully.', 'entryId' => $entryId]);
    } catch (Exception $e) {
        return json_encode(['error' => 'Error deleting entry: ' . $e->getMessage()]);
    }
}

// Function to update an entry by ID
function updateEntry($entryId, $newData) {
    global $mongoClient, $namespace;
    $bulkWrite = new MongoDB\Driver\BulkWrite();
    $filter = ['_id' => new MongoDB\BSON\ObjectID($entryId)];

    // Delete the existing document
    $bulkWrite->delete($filter);

    // Insert the updated document
    $newData['_id'] = new MongoDB\BSON\ObjectID($entryId);
    $bulkWrite->insert($newData);

    try {
        $mongoClient->executeBulkWrite($namespace, $bulkWrite);
        return json_encode(['success' => 'Entry updated successfully.', 'entryId' => $entryId]);
    } catch (Exception $e) {
        return json_encode(['error' => 'Error updating entry: ' . $e->getMessage()]);
    }
}

// Handle form submission for updating an entry
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

// Retrieve all entries
$entries = getAllEntries();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Entries</title>
    <script src="jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="style.css"/>
    <link rel="stylesheet" href="jsoneditor.min.css"/>
    <script src="jsoneditor.min.js"></script>
    <link rel="stylesheet" href="toastr.min.css"/>

<script src="query-builder.standalone.min.js"></script>
<link href="query-builder.default.min.css" rel="stylesheet">

    <script src="toastr.min.js"></script>
    <script>
        // Initialize JSON Editor for each entry
        function initJsonEditors() {
		<?php foreach ($entries as $entry): ?>
		const editor_<?php echo $entry->_id; ?> = new JSONEditor(
			document.getElementById('jsoneditor_<?php echo $entry->_id; ?>'),
			{
				mode: 'tree',
				onBlur: function () {
					const updatedJson = editor_<?php echo $entry->_id; ?>.get();
					const jsonData = JSON.stringify(updatedJson, null, 2);
					const entryId = '<?php echo $entry->_id; ?>';
					updateEntry(entryId, jsonData);
				}
			}
		);

            editor_<?php echo $entry->_id; ?>.set(<?php echo json_encode($entry, JSON_UNESCAPED_UNICODE); ?>);
            <?php endforeach; ?>
        }

	function deleteEntry(entryId, event) {
		event.stopPropagation();
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
	<h2>Search</h2>
	<div id="builder-basic"></div>
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
		<script>
			var jsonString = <?php echo generateQueryBuilderStructure(); ?>; // Assuming $jsonString contains the generated JSON

			$('#builder-basic').queryBuilder({
				plugins: [],
				filters: <?php print(generateQueryBuilderFilters($collectionName)); ?>,
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
		</script>
	</body>
</html>
