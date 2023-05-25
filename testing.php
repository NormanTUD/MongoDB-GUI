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
	function is_false($name, $expected)
	{
		increase_started_tests();

		if ($expected === false) {
			echo "OK: $name\n";
			return true;
		} else {
			$message = "Is true, should be false";
			trigger_error($message, E_USER_WARNING);
			test_failed();
			return false;
		}
	}

	// Function to check if two values are equal and perform appropriate actions
	function is_true($name, $expected)
	{
		increase_started_tests();

		if ($expected === true) {
			echo "OK: $name\n";
			return true;
		} else {
			$message = "Is false, should be true";
			trigger_error($message, E_USER_WARNING);
			test_failed();
			return false;
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

		if ($GLOBALS['failed_tests']) {
			echo "Failed tests: " . $GLOBALS['failed_tests'] . "\n";
			exit(1);
		}
	}

	// Register a shutdown function to call done_testing()
	register_shutdown_function('done_testing');

	// Usage example:
	is_equal("Basis Tests for the framework (1)", 5, 5);
	is_unequal("Basis Tests for the framework (2)", "Hello", "World");
	regex_matches("Basis Tests for the framework (3)", "OpenAI", "/Open/");
	regex_fails("Basis Tests for the framework (4)", "Open", "/AI/");

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
	is_equal("getDataType(42)", getDataType(42), "integer");
	is_equal("getDataType(3.14)", getDataType(3.14), "double");

	// Test 2: String value
	is_equal("getDataType('Hello, World!')", getDataType("Hello, World!"), "string");

	// Test 3: DateTime object
	$date = new DateTime();
	is_equal("getDataType(new DateTime)", getDataType($date), "datetime");

	// Test 4: MongoDB\BSON\UTCDateTime object
	$utcDate = new MongoDB\BSON\UTCDateTime();
	is_equal("getDataType(new MongoDB\BSON\UTCDateTime())", getDataType($utcDate), "datetime");

	/*
	// Test 5: MongoDB\BSON\Timestamp object
	$timestamp = new MongoDB\BSON\Timestamp();
	is_equal("Test 5", getDataType($timestamp), "time");
	 */

	// Test 6: Boolean value
	is_equal("getDataType(true)", getDataType(true), "boolean");

	// Test 7: Unknown value (default to string)
	$unknown = new stdClass();
	is_equal("getDataType(new stdClass())", getDataType($unknown), "string");




	// Test 1: String value
	$path1 = 'name';
	$value1 = 'John Doe';
	$expected1 = [
		'id' => $path1,
		'label' => $path1,
		'type' => 'string',
		'operators' => ['equal', 'not_equal', 'contains']
	];
	is_equal("Test get_filters(path1, value1)", get_filters($path1, $value1), $expected1);

	// Test 2: Numeric value (integer)
	$path2 = 'age';
	$value2 = 25;
	$expected2 = [
		'id' => $path2,
		'label' => $path2,
		'type' => 'integer',
		'operators' => ['equal', 'not_equal', 'greater', 'less', 'less_or_equal', 'greater_or_equal']
	];
	is_equal("Test get_filters(path2, value2)", get_filters($path2, $value2), $expected2);

	// Test 3: Numeric value (double)
	$path3 = 'price';
	$value3 = 9.99;
	$expected3 = [
		'id' => $path3,
		'label' => $path3,
		'type' => 'double',
		'operators' => ['equal', 'not_equal', 'greater', 'less', 'less_or_equal', 'greater_or_equal']
	];
	is_equal("Test get_filters(path3, value3)", get_filters($path3, $value3), $expected3);

	// Test 4: Boolean value
	$path4 = 'active';
	$value4 = true;
	$expected4 = [
		'id' => $path4,
		'label' => $path4,
		'type' => 'boolean',
		'input' => 'radio',
		'operators' => ['equal', 'not_equal'],
		'values' => ['True', 'False']
	];
	is_equal("Test get_filters(path4, value4)", get_filters($path4, $value4), $expected4);

	/*
	// Test 5: Array value
	$path5 = 'tags';
	$value5 = ['red', 'blue', 'green'];
	$expected5 = [
		'id' => $path5,
		'label' => $path5,
		'type' => 'array',
		'operators' => ['equal', 'not_equal']
	];

	$expected5 = [
		"id" => "tags",
		"label" => "tags",
		"type" => "array",
		"input" => "radio",
		"operators" => ["in", "not_in"]
	];

	is_equal("get_filters(path5, value5)", get_filters($path5, $value5), $expected5);
	 */

	// Create an instance of MongoDBHelper
	$mongodbHelper = new MongoDBHelper();


	// Test insertDocument() method
	$document = ['name' => 'John', 'age' => 30];
	$result = $mongodbHelper->insertDocument($document);
	$result = json_decode($result, true);
	unset($result["entryId"]);
	$result = json_encode($result);
	regex_matches("Insert Document", $result, '/"success":"Entry created successfully/');

	// Test getAllEntries() method
	$entries = $mongodbHelper->getAllEntries();
	is_unequal("Get All Entries", 0, count($entries));

	// Test find() method
	$searchQuery = ['name' => 'John'];
	$result = json_decode(json_encode($mongodbHelper->find($searchQuery)), true);
	is_unequal("Search Entries", 0, count($result));

	$entryId = $result[0]["_id"]["\$oid"];







	// Test insertValue() method
	$documentId = $entryId; // Use the entryId obtained from the previous test
	$key = 'age';
	$value = 35;
	$result = $mongodbHelper->insertValue($documentId, $key, $value);
	$expected = '{"success":"Value inserted successfully.","documentId":{"$oid":"' . $documentId . '"}}';
	$real = json_decode($result, true);
	$realDocumentId = $real['documentId'];

	// Check success message
	is_equal("Insert Value - Success", "Value inserted successfully.", $real['success']);

	// Check if documentId exists in the real result
	is_true("Insert Value - documentId exists", isset($real['documentId']));

	// Check if the documentId matches the expected value
	is_true("Insert Value - documentId matches expected", $realDocumentId === $documentId);

	// Test find() method after update
	$searchQuery = ['_id' => $mongodbHelper->createId($documentId )];
	$result = $mongodbHelper->find($searchQuery);
	if(count($result)) {
		$updatedEntry = $result[0];
		is_equal("Search Entries after inserting", $value, $updatedEntry['age']);
	} else {
		dier("Searching after inserting failed, id: $documentId");
	}

	$newDocument = [
		'name' => 'John Doe',
		'age' => 350,
		'email' => 'john.doe@example.com'
	];

	// Test replaceDocument() method
	$result = $mongodbHelper->replaceDocument($documentId, $newDocument);
	$expected = '{"success":"Document replaced successfully.","documentId":{"$oid":"'.$documentId.'"}}';
	$real = json_decode($result, true);

	// Check success message
	if(isset($real["success"])) {
		is_equal("Replace Document - Success", "Document updated successfully.", $real['success']);
	} else {
		dier($real["error"]);
	}

	// Check if documentId exists in the real result
	is_true("Replace Document - documentId exists", isset($real['documentId']));

	// Check if the documentId matches the expected value
	$realDocumentId = $real['documentId'];
	is_true("Replace Document - documentId matches expected", $realDocumentId === $documentId);

	// Test find() method after update
	$result = $mongodbHelper->findById($documentId);
	if (count($result)) {
		$updatedEntry = $result[0];
		is_equal("Search Entries after Update", $newDocument['age'], $updatedEntry['age']);
	} else {
		dier("!!! Searching after update failed, not found: $documentId");
	}

	$result = $mongodbHelper->deleteEntry($entryId);
	$expected = "{\"success\":\"Entry deleted successfully.\",\"entryId\":{\"\$oid\":\"$entryId\"}}";
	$real = json_decode($result, true);
	is_equal("Delete Entry", $expected, json_encode($real));

	// Check success message
	is_equal("Delete Entry - Success", "Entry deleted successfully.", $real['success']);

	// Check if entryId exists in the real result
	is_true("Delete Entry - entryId exists", isset($real['entryId']));

	// Check if entryId exists in the real result
	is_true("Delete Entry - entryId exists", isset($real['entryId']));

	// Check if the entryId matches the expected value
	$realEntryId = $real['entryId'];
	is_true("Delete Entry - entryId matches expected", isset($realEntryId['$oid']) && $realEntryId['$oid'] === $documentId);

?>
