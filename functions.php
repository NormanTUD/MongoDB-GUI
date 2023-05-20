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


function check_cursor_object($cursor) {
    // Check if the cursor is valid
    if (!$cursor instanceof MongoDB\Driver\Cursor) {
        die("Invalid cursor object. Expected MongoDB\Driver\Cursor.");
    }
    
    // Check if the cursor has a valid collection name
    $collection = $cursor->collection;
    if (!is_string($collection) || empty($collection)) {
        die("Invalid or missing collection name.");
    }
    
    // Check if the cursor has a valid command object
    $command = $cursor->command;
    if (!$command instanceof MongoDB\Driver\Command) {
        die("Invalid command object. Expected MongoDB\Driver\Command.");
    }
    
    // Check if the command object has the necessary properties
    $commandProperties = $command->command ?? null;
    if (!is_object($commandProperties) || !property_exists($commandProperties, 'listCollections') || !property_exists($commandProperties, 'filter')) {
        die("Invalid command properties. Expected 'listCollections' and 'filter'.");
    }
    
    // Check if the cursor has a valid server object
    $server = $cursor->server;
    if (!$server instanceof MongoDB\Driver\Server) {
        die("Invalid server object. Expected MongoDB\Driver\Server.");
    }
    
    // Check if the server object has a valid host and port
    $host = $server->host;
    $port = $server->port;
    if (!is_string($host) || empty($host) || !is_int($port) || $port <= 0) {
        die("Invalid server host or port.");
    }
    
    // Additional checks or error handling can be added as needed
    
    // If no errors occurred, return true or perform additional actions
    return true;
}


?>
