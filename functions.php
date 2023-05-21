<?php
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
	print "<pre>\n";
	throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler("exception_error_handler");
ini_set('display_errors', '1');

if (!class_exists('MongoDB\Driver\Manager')) {
	echo "MongoDB driver not found.";
	return;
}

// MongoDB connection settings
$GLOBALS["mongodbHost"] = getEnvOrDie('DB_HOST', 'dbhost');
$GLOBALS["mongodbPort"] = getEnvOrDie('DB_PORT', 'dbport');
$GLOBALS["databaseName"] = getEnvOrDie('DB_NAME', 'dbname');
$GLOBALS["collectionName"] = getEnvOrDie('DB_COLLECTION', 'dbcollection');
$GLOBALS["namespace"] = $GLOBALS["databaseName"].".".$GLOBALS['collectionName'];

// Connect to MongoDB
$GLOBALS["mongoClient"] = new MongoDB\Driver\Manager("mongodb://".$GLOBALS["mongodbHost"].":".$GLOBALS["mongodbPort"]);

if (!isset($GLOBALS["mongoClient"]) || !isset($GLOBALS["databaseName"]) || !isset($GLOBALS["collectionName"]) || !isset($GLOBALS["namespace"])) {
	echo "Incomplete or missing $GLOBALS variables.";
	return;
}

$connection = fsockopen($GLOBALS["mongodbHost"], $GLOBALS["mongodbPort"], $errno, $errstr, 5);
if (!$connection) {
	echo "Unable to connect to ".$GLOBALS["mongodbHost"].":".$GLOBALS["mongodbPort"]." and port specified";
	return;
}
fclose($connection);

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

function getEnvOrDie($name, $fn = 0) {
	$value = getenv($name);
	if (!$value) {
		if($fn && file_exists($fn)) {
			return read_first_line_of_file_or_die($fn);
		} else {
			die("Environment variable '$name' is not set.");
		}
	}
	return $value;
}

// Function to delete an entry by ID
function deleteEntry($entryId) {
	$bulkWrite = new MongoDB\Driver\BulkWrite();
	$filter = ['_id' => new MongoDB\BSON\ObjectID($entryId)];

	$bulkWrite->delete($filter);

	try {
		$GLOBALS["mongoClient"]->executeBulkWrite($GLOBALS["namespace"], $bulkWrite);
		return json_encode(['success' => 'Entry deleted successfully.', 'entryId' => $entryId]);
	} catch (Exception $e) {
		return json_encode(['error' => 'Error deleting entry: ' . $e->getMessage()]);
	}
}



// Function to update an entry by ID
function updateEntry($entryId, $newData) {
	$bulkWrite = new MongoDB\Driver\BulkWrite();
	$filter = ['_id' => new MongoDB\BSON\ObjectID($entryId)];

	// Delete the existing document
	$bulkWrite->delete($filter);

	// Insert the updated document
	$newData['_id'] = new MongoDB\BSON\ObjectID($entryId);
	$bulkWrite->insert($newData);

	try {
		$GLOBALS["mongoClient"]->executeBulkWrite($GLOBALS["namespace"], $bulkWrite);
		return json_encode(['success' => 'Entry updated successfully.', 'entryId' => $entryId]);
	} catch (Exception $e) {
		return json_encode(['error' => 'Error updating entry: ' . $e->getMessage()]);
	}
}

function generateQueryBuilderOptions()
{
	$query = new MongoDB\Driver\Query([], ['projection' => ['_id' => 0]]);
	$cursor = $GLOBALS["mongoClient"]->executeQuery($GLOBALS["namespace"], $query);

	$filters = [];
	$options = [];

	foreach ($cursor as $document) {
		$documentArray = json_decode(json_encode($document), true);
		traverseDocument($documentArray, '', $filters, $options);
	}

	$output = [
		'filters' => $filters,
		'options' => $options,
	];

	return $output;
}

function get_filters ($path, $value) {
	$type = getDataType($value);

	$filter = [
		'id' => $path,
		'label' => $path,
		'type' => $type
	];

	if ($type === 'string') {
		$filter['operators'] = ['equal', 'not_equal', 'contains'];
	} elseif ($type === 'integer' || $type === 'double') {
		$filter['operators'] = ['equal', 'not_equal', 'greater', 'less', 'less_equal', 'greater_equal'];
	} elseif ($type === 'boolean') {
		$filter['input'] = 'radio';
		$filter['operators'] = ['equal', 'not_equal'];
		$filter['values'] = ['True', 'False'];
	} elseif ($type === 'array' || $type === 'object') {
		$filter['operators'] = ['equal', 'not_equal'];
	} else {
		die("Invalid datatype: $type");
	}
	return $filter;
}

function traverseDocument($data, $prefix, &$filters, &$options) {
	foreach ($data as $key => $value) {
		$path = $prefix . $key;


		$option = [
			'id' => $path,
			'label' => $path
		];

		if(preg_match("/\./", $path)) {
			$generalized_path = preg_replace("/^.*\./", "*.", $path);
			$generalized_option = [
				'id' => $generalized_path,
				'label' => $generalized_path
			];
			$options[] = $generalized_option;
			$filters[] = get_filters($generalized_path, $value); //$filter;
		}

		

		$filters[] = get_filters($path, $value); //$filter;
		$options[] = $option;

		if (is_array($value) || is_object($value)) {
			traverseDocument($value, $path . '.', $filters, $options);
		}
	}
}

function getDataType($value) {
	if (is_numeric($value)) {
		if (is_int($value)) {
			return 'integer';
		} elseif (is_float($value)) {
			return 'double';
		}
	} elseif (is_string($value)) {
		return 'string';
	} elseif ($value instanceof DateTime || $value instanceof MongoDB\BSON\UTCDateTime) {
		return 'datetime';
	} elseif ($value instanceof MongoDB\BSON\Timestamp) {
		return 'time';
	} elseif (is_bool($value)) {
		return 'boolean';
	}

	return 'string'; // Default to string if data type cannot be determined
}

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
	if (isset($_POST['reset_search'])) {
		$entries = getAllEntries();
		$entry_html = '';
		foreach ($entries as $entry) {
			$entry_html .= '<div id="entry_'.$entry->_id.'">
				<div id="jsoneditor_'.$entry->_id.'"></div>
				<button onclick="deleteEntry(\''.$entry->_id.'\', event)">Delete</button>
				</div>';
		}
		echo json_encode(array('success' => 'Search reset successfully.', 'entries' => $entry_html));
		exit;
	}

	if (isset($_POST['search_query'])) {
		$searchQuery = json_decode($_POST['search_query'], true);
		$matchingEntries = searchEntries($searchQuery);
		echo json_encode($matchingEntries);
		exit;
	}

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

		if (isset($_POST['data'])) {
			$data = $_POST['data'];

			try {
				$documents[] = json_decode($_POST["data"]);
				if (json_last_error() !== JSON_ERROR_NONE) {
					throw new Exception('Failed to decode JSON: ' . json_last_error_msg());
				}
			} catch (\Throwable $e) {
				// Detect data format
				$lines = explode(PHP_EOL, $data);
				$headers = str_getcsv(array_shift($lines));

				$documents = [];
				foreach ($lines as $line) {
					$row = str_getcsv($line);
					$document = [];
					foreach ($headers as $index => $header) {
						$document[$header] = isset($row[$index]) ? $row[$index] : '';
					}
					$documents[] = $document;
				}
			}

			foreach ($documents as $document) {
				insertDocument($document);
			}
		}
	}
}

function insertDocument($document) {
	if($document) {
		$bulkWrite = new MongoDB\Driver\BulkWrite();
		$bulkWrite->insert($document);

		try {
			$GLOBALS["mongoClient"]->executeBulkWrite($GLOBALS["namespace"], $bulkWrite);
			return json_encode(['success' => 'Entry created successfully.']);
		} catch (Exception $e) {
			return json_encode(['error' => 'Error creating entry: ' . $e->getMessage()]);
		}
	} else {
		dier("Document not defined in insertDocument");
	}
}



function searchEntries($searchQuery) {
	$filter = $searchQuery;
	$options = [];
	$query = new MongoDB\Driver\Query($filter, $options);

	try {
		$cursor = $GLOBALS["mongoClient"]->executeQuery($GLOBALS["namespace"], $query);
		$entries = $cursor->toArray();
		return $entries;
	} catch (\Throwable $e) {
		dier($e);
		// Handle the error appropriately
		// ...
	}
}


// Function to insert a single value into a document
function insertValue($documentId, $key, $value)
{
	$bulkWrite = new MongoDB\Driver\BulkWrite();
	$filter = ['_id' => new MongoDB\BSON\ObjectID($documentId)];
	$update = ['$set' => [$key => $value]];

	$bulkWrite->update($filter, $update);

	try {
		$GLOBALS["mongoClient"]->executeBulkWrite($GLOBALS["namespace"], $bulkWrite);
		return json_encode(['success' => 'Value inserted successfully.', 'documentId' => $documentId]);
	} catch (Exception $e) {
		return json_encode(['error' => 'Error inserting value: ' . $e->getMessage()]);
	}
}

// Function to delete a single value from a document
function deleteValue($documentId, $key)
{
	$bulkWrite = new MongoDB\Driver\BulkWrite();
	$filter = ['_id' => new MongoDB\BSON\ObjectID($documentId)];
	$update = ['$unset' => [$key => '']];

	$bulkWrite->update($filter, $update);

	try {
		$GLOBALS["mongoClient"]->executeBulkWrite($GLOBALS["namespace"], $bulkWrite);
		return json_encode(['success' => 'Value deleted successfully.', 'documentId' => $documentId]);
	} catch (Exception $e) {
		return json_encode(['error' => 'Error deleting value: ' . $e->getMessage()]);
	}
}



class MongoDBDocument {
	private $documentId;
	private $data;

	public function __construct($documentId)
	{
		$this->documentId = $documentId;
		$this->loadData();
	}

	private function loadData()
	{
		$filter = ['_id' => new MongoDB\BSON\ObjectID($this->documentId)];
		$query = new MongoDB\Driver\Query($filter);
		$cursor = $GLOBALS["mongoClient"]->executeQuery($GLOBALS["namespace"], $query);
		$this->data = (array) current($cursor->toArray());
	}

	public function setValue($key, $value)
	{
		$this->data[$key] = $value;
		$this->updateData();
	}

	public function deleteValue($key)
	{
		unset($this->data[$key]);
		$this->updateData();
	}

	private function updateData()
	{
		$bulkWrite = new MongoDB\Driver\BulkWrite();
		$filter = ['_id' => new MongoDB\BSON\ObjectID($this->documentId)];
		$update = ['$set' => $this->data];

		$bulkWrite->update($filter, $update);

		try {
			$GLOBALS["mongoClient"]->executeBulkWrite($GLOBALS["namespace"], $bulkWrite);
		} catch (Exception $e) {
			// Handle update error if needed
		}
	}
}

#$document = new MongoDBDocument('6469552aeb8474be4d0f00b2');
#$document->setValue('key', 'value');
#dier($document);


function generateVisualizationCode($entries, $fields)
{
	$data = array();
	foreach ($fields as $field => $config) {
		$values = array_column($entries, $field);

		// Perform aggregation or analysis based on the configuration
		switch ($config['aggregation']) {
		case 'count':
			$result = count($values);
			break;
		case 'distinct':
			$result = count(array_unique($values));
			break;
		case 'custom':
			$result = $config['analysis']($values);
			break;
		case 'none':
		default:
		$result = $config['analysis']($values);
		break;
		}

		$data[] = array(
			'field' => $field,
			'result' => $result,
		);
	}

	$jsCode = "
	    var data = " . json_encode($data) . ";

	    // Plotting logic using Plotly.js
	    // Customize this part to generate the desired visualization

	    // Example: Generate a bar chart
	    var x = data.map(entry => entry.field);
	    var y = data.map(entry => entry.result);

	    var trace = {
		x: x,
		y: y,
		type: 'bar'
	    };

	    var layout = {
		title: 'General Statistics',
		xaxis: {
		    title: 'Fields'
		},
		yaxis: {
		    title: 'Results'
		}
	    };

	    Plotly.newPlot('chart_two', [trace], layout);
    ";

return $jsCode;
}

?>
