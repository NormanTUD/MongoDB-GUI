<?php
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
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
?>
