<?php
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
	throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler("exception_error_handler");
ini_set('display_errors', '1');

// MongoDB connection settings
$GLOBALS["mongodbHost"] = getEnvOrDie('DB_HOST', 'db_host');
$GLOBALS["mongodbPort"] = getEnvOrDie('DB_PORT', 'db_port');
$GLOBALS["databaseName"] = getEnvOrDie('DB_NAME', 'db_name');
$GLOBALS["collectionName"] = getEnvOrDie('DB_COLLECTION', 'db_collection');

// Connect to MongoDB
$GLOBALS["mongoClient"] = new MongoDB\Driver\Manager("mongodb://".$GLOBALS["mongodbHost"].":".$GLOBALS["mongodbPort"]);
$GLOBALS["namespace"] = $GLOBALS["databaseName"].".".$GLOBALS['collectionName'];

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
