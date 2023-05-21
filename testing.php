<?php
	define('INCLUDED_FROM_INDEX', true);
	include("functions.php");
	// Define a counter for the number of started tests
	$GLOBALS['started_tests'] = 0;

	// Define a counter for the number of failed tests
	$GLOBALS['failed_tests'] = 0;

	// Function to print differences between expected and actual values
	function print_diffs($name, $expected, $actual)
	{
		$message = "ERROR: $name failed! Expected: " . print_r($expected, true) . ", got: " . print_r($actual, true);
		return $message;
	}

	// Function to increment the started_tests counter
	function increase_started_tests()
	{
		if (array_key_exists('started_tests', $GLOBALS)) {
			$GLOBALS['started_tests']++;
		} else {
			$GLOBALS['started_tests'] = 1;
		}
	}

	// Function to increment the failed_tests counter
	function test_failed()
	{
		if (array_key_exists('failed_tests', $GLOBALS)) {
			$GLOBALS['failed_tests']++;
		} else {
			$GLOBALS['failed_tests'] = 1;
		}
	}

	// Function to check if two values are equal and perform appropriate actions
	function is_equal($name, $expected, $actual)
	{
		increase_started_tests();

		if ($expected === $actual) {
			echo "OK: $name\n";
			return true;
		} else {
			$message = print_diffs($name, $expected, $actual);
			trigger_error($message, E_USER_WARNING);
			test_failed();
			return false;
		}
	}

	// Function to check if two values are unequal and perform appropriate actions
	function is_unequal($name, $expected, $actual)
	{
		increase_started_tests();

		if ($expected !== $actual) {
			echo "OK: $name\n";
			return true;
		} else {
			$message = print_diffs($name, $expected, $actual);
			trigger_error($message, E_USER_WARNING);
			test_failed();
			return false;
		}
	}

	// Function to check if a string matches a regular expression and perform appropriate actions
	function regex_matches($name, $string, $regex)
	{
		increase_started_tests();

		if (is_string($string)) {
			if (preg_match($regex, $string)) {
				echo "OK: $name\n";
				return true;
			} else {
				$message = "ERROR: $name failed! Expected string:\n====>\n$string\n<===\nto match regex:\n====>\n$regex\n<====\n";
				trigger_error($message, E_USER_WARNING);
				test_failed();
				return false;
			}
		} else {
			$message = "Expected string:\n====>\n$string\n<====\nbut received " . gettype($string);
			trigger_error($message, E_USER_WARNING);
			test_failed();
			return false;
		}
	}

	// Function to check if a string does not match a regular expression and perform appropriate actions
	function regex_fails($name, $string, $regex)
	{
		increase_started_tests();

		if (is_string($string)) {
			if (!preg_match($regex, $string)) {
				echo "OK: $name\n";
				return true;
			} else {
				$message = "ERROR: $name failed! Expected string:\n====>\n$string\n<===\nnot to match regex:\n====>\n$regex\n<====\n";
				trigger_error($message, E_USER_WARNING);
				test_failed();
				return false;
			}
		} else {
			$message = "Expected string:\n====>\n$string\n<====\nbut received " . gettype($string);
			trigger_error($message, E_USER_WARNING);
			test_failed();
			return false;
		}
	}

	// Function to print the test results and exit
	function done_testing()
	{
		if ($GLOBALS['started_tests']) {
			echo "\nNumber of started tests: " . $GLOBALS['started_tests'] . "\n";
		} else {
			echo "Seemingly no tests done!\n";
		}

		if (isset($GLOBALS['failed_tests'])) {
			echo "Failed tests: " . $GLOBALS['failed_tests'] . "\n";
			exit(1);
		}
	}

	// Register a shutdown function to call done_testing()
	register_shutdown_function('done_testing');

	// Usage example:
	is_equal("Test 1", 5, 5);
	is_unequal("Test 2", "Hello", "World");
	regex_matches("Test 3", "OpenAI", "/Open/");
	regex_fails("Test 4", "Open", "/AI/");

	function test_find_lat_lon_variables_recursive() {
		$entry = [
			'lat' => '10.123',
			'lon' => '-20.456',
			'other' => 'data'
		];

		$result = find_lat_lon_variables_recursive($entry);
		$expected = [['lat' => '10.123', 'lon' => '-20.456', 'original_entry' => $entry]];

		is_equal("find_lat_lon_variables_recursive 1", $result, $expected);

		$nestedEntry = [
			'nested' => [
				'latitude' => '30.789',
				'longitude' => '-40.987',
				'other' => 'nested data'
			]
		];

		$result = find_lat_lon_variables_recursive($entry);
		$expected = [
			['lat' => '10.123', 'lon' => '-20.456', 'original_entry' => $entry],
		];

		is_equal("find_lat_lon_variables_recursive 2", $result, $expected);
	}

	test_find_lat_lon_variables_recursive();

	// Test 1: Numeric values
	is_equal("Test 1.1", getDataType(42), "integer");
	is_equal("Test 1.2", getDataType(3.14), "double");

	// Test 2: String value
	is_equal("Test 2", getDataType("Hello, World!"), "string");

	// Test 3: DateTime object
	$date = new DateTime();
	is_equal("Test 3", getDataType($date), "datetime");

	// Test 4: MongoDB\BSON\UTCDateTime object
	$utcDate = new MongoDB\BSON\UTCDateTime();
	is_equal("Test 4", getDataType($utcDate), "datetime");

	/*
	// Test 5: MongoDB\BSON\Timestamp object
	$timestamp = new MongoDB\BSON\Timestamp();
	is_equal("Test 5", getDataType($timestamp), "time");
	 */

	// Test 6: Boolean value
	is_equal("Test 6", getDataType(true), "boolean");

	// Test 7: Unknown value (default to string)
	$unknown = new stdClass();
	is_equal("Test 7", getDataType($unknown), "string");

?>
