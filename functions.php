<?php
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
	throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler("exception_error_handler");
ini_set('display_errors', '1');

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
