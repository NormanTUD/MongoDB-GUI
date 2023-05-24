<?php
if (!defined('INCLUDED_FROM_INDEX')) {
    die('This file must be included from index.php');
}

require 'translations.php';
require 'questions.php';

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

require_once 'MongoDBHelper.php';

try {
	$connection = fsockopen($GLOBALS["mongodbHost"], $GLOBALS["mongodbPort"], $errno, $errstr, 5);
	if (!$connection) {
		echo "Unable to connect to ".$GLOBALS["mongodbHost"].":".$GLOBALS["mongodbPort"]." and port specified";
		return;
	}
	fclose($connection);
} catch (\Throwable $e) {
	echo "Unable to connect to ".$GLOBALS["mongodbHost"].":".$GLOBALS["mongodbPort"]." and port specified";
	echo "Is a mongodb instance running?";
	echo $e;
	exit(1);
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

function convertNumericStrings($data) {
	if (is_array($data)) {
		$result = [];
		foreach ($data as $key => $value) {
			$result[$key] = convertNumericStrings($value);
		}
		return $result;
	} elseif (is_object($data)) {
		$result = [];
		foreach ($data as $key => $value) {
			$result[$key] = convertNumericStrings($value);
		}
		return $result;
	} elseif (is_string($data)) {
		if (is_numeric($data)) {
			if (strpos($data, '.') !== false) {
				return floatval($data);
			} else {
				return intval($data);
			}
		}
	}

	return $data;
}

function generateQueryBuilderFilter() {
	$query = new MongoDB\Driver\Query([], ['projection' => ['_id' => 0]]);
	$cursor = $GLOBALS["mdh"]->executeQuery($query);

	$filters = [];
	$rules = [];

	foreach ($cursor as $document) {
		$documentArray = json_decode(json_encode($document), true);
		traverseDocument($documentArray, '', $filters, $rules);
	}

	$filters = removeDuplicates($filters);
	$rules = removeDuplicates($rules);

	$output = [
		'filters' => $filters,
		'rules' => $rules,
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
		$filter['operators'] = ['equal', 'not_equal', 'greater', 'less', 'less_or_equal', 'greater_or_equal'];
	} elseif ($type === 'array') {
		#$filter['input'] = 'radio';
		#$filter['operators'] = ['in', 'not_in'];
	} elseif ($type === 'boolean') {
		$filter['input'] = 'radio';
		$filter['operators'] = ['equal', 'not_equal'];
		$filter['values'] = ['True', 'False'];
	/*
	} else {
		die("Invalid datatype: $type");
	 */
	}
	return $filter;
}

function traverseDocument($data, $prefix, &$filters, &$rules) {
	foreach ($data as $key => $value) {
		$path = $prefix . $key;


		$option = [
			'id' => $path,
			'label' => $path
		];

		if(preg_match("/\./", $path)) {
			$generalized_path = preg_replace("/^.*\./", "*.", $path);
			$generalized_path_id = str_replace('.*.', '.#.', $generalized_path);
			$generalized_option = [
				'id' => $generalized_path,
				'label' => $generalized_path
			];
			$rules[] = $generalized_option;
			$filters[] = get_filters($generalized_path, $value); //$filter;
		}

		

		$filters[] = get_filters($path, $value); //$filter;
		$rules[] = $option;

		if (is_array($value) || is_object($value)) {
			traverseDocument($value, $path . '.', $filters, $rules);
		}
	}
}

function getDataType($value, $is_recursion=0) {
	if (is_numeric($value)) {
		if (preg_match('/^[+-]?\d+$/', $value)) {
			return 'integer';
		} elseif (is_float($value)) {
			return 'double';
		} elseif (preg_match('/^[+-]?\d+(?:\.\d+)?$/', $value)) {
			return 'double';
		} else {
			dier("Unhandled numeric type, >$value<");
		}
	} elseif (is_string($value)) {
		return 'string';
	} elseif ($value instanceof DateTime || $value instanceof MongoDB\BSON\UTCDateTime) {
		return 'datetime';
	} elseif ($value instanceof MongoDB\BSON\Timestamp) {
		return 'time';
	} elseif (is_bool($value)) {
		return 'boolean';
	} elseif (is_array($value)) {
		return null;
		//return 'array';
	} elseif ($is_recursion = 0) {
		return getDataType(json_decode(json_encode($value), true), 1);
	}

	return 'string'; // Default to string if data type cannot be determined
}

function getAllEntries() {
	$query = new MongoDB\Driver\Query([]);
	try {
		$cursor = $GLOBALS["mdh"]->executeQuery($query);
		$entries = $cursor->toArray();
		return $entries;
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
	return null;
}

// Function to retrieve language translations
function getTranslation($key, $option = false) {
	$span = true;
	global $language;
	$lang = 'en'; // Default language is English

	if (isset($_GET['lang']) && isset($language[$_GET['lang']])) {
		$lang = htmlentities($_GET['lang']);
	}

	if (isset($language[$lang])) {
		if (isset($language[$lang][$key])) {
			if ($span) {
				if($option) {
					return '<option class="TRANSLATEME_'.$key.'">'.$key.'</option>';
				} else {
					return '<span value="'.$key.'" class="TRANSLATEME_'.$key.'">'.$key.'</span>';
				}
			} else {
				return $language[$lang][$key];
			}
		} else {
			throw new Exception("Unknown language key: \$language[$lang][" . htmlentities($key) . "]");
		}
	} else {
		throw new Exception("Unknown language shortcut: " . htmlentities($lang));
	}
}

function generateFormFields($questions) {
    $html = '';
    
    foreach ($questions as $group) {
        $html .= '<h2>' . getTranslation($group['group'], true) . '</h2>';
        
        foreach ($group['questions'] as $index => $question) {
            $html .= generateFormField($question);
        }
    }
    
    return $html;
}

function generateFormField($question) {
	$html = '';

	if ($question['input_type'] === 'text' || $question['input_type'] === 'number') {
		$html .= '<h3>' . getTranslation($question['question'], true) . '</h3>';
		if(isset($question["fields"]) && is_array($question["fields"])) {
			foreach ($question['fields'] as $field) {
				$html .= '<label for="' . $question['name'] . '_' . $field['name'] . '">' . getTranslation($field['label'], true) . '</label>';
				$html .= '<input type="text" name="' . $question['name'] . '_' . $field['name'] . '"';
				if (isset($field["required"]) && $field['required']) {
					$html .= ' required';
				}
				$html .= '>';
			}
		} else {
			$html .= '<input type="' . $question['input_type'] . '" name="' . $question['name'] . '"';
			if (isset($question["required"]) && $question['required']) {
				$html .= ' required';
			}
			$html .= '>';
		}
	} elseif ($question['input_type'] === 'radio') {
		$html .= '<h3>' . getTranslation($question['question'], true) . '</h3>';
		foreach ($question['options'] as $option) {
			$html .= '<input type="radio" id="' . $question['name'] . '" name="' . $question['name'] . '" value="' . $option . '">' . getTranslation($option, true);
		}
	} elseif ($question['input_type'] === 'checkbox') {
		$html .= '<h3>' . getTranslation($question['question'], true) . '</h3>';
		foreach ($question['options'] as $option) {
			$html .= '<input type="checkbox" name="' . $question['name'] . '[]" value="' . $option . '">' . getTranslation($option, true);
		}
	} elseif ($question['input_type'] === 'select') {
		$html .= '<h3>' . getTranslation($question['question'], true) . '</h3>';
		$html .= '<select name="' . $question['name'] . '">';
		//$html .= getTranslation('select_option', true);
		foreach ($question['options'] as $option) {
			$html .= getTranslation($option, true);
		}
		$html .= '</select>';
	}

	return $html;
}

// Function to validate and process the form submission
function process_autoform($questions) {
	$html = '';

	$response = [];
	$errors = [];

	foreach ($questions as $group) {
		foreach ($group['questions'] as $question) {
			$name = $question['name'];
			if(isset($question["fields"])) {
				$base_name = $question["name"];
				foreach ($question["fields"] as $tq_name) {
					$key = $base_name.'_'.$tq_name["name"];
					$tq_value = $_POST[$key] ?? '';

					if (isset($question['required']) && $question['required'] && empty($tq_value)) {
						$errors[] = getTranslation('required_question') . getTranslation($question['question']);
					}

					if ($question['input_type'] === 'number' && !is_numeric($tq_value)) {
						$errors[] = getTranslation('invalid_response') . getTranslation($question['question']);
					}

					$response[$key] = $tq_value;
				}
			} else {
				$value = $_POST[$name] ?? '';

				if (isset($question['required']) && $question['required'] && empty($value)) {
					$errors[] = getTranslation('required_question') . getTranslation($question['question']);
				}

				if ($question['input_type'] === 'number' && !is_numeric($value)) {
					$errors[] = getTranslation('invalid_response') . getTranslation($question['question']);
				}

				$response[$name] = $value;
			}
		}
	}

	if (!empty($errors)) {
		// Display error messages
		$html .= '<h2>'.getTranslation("errors").'</h2>';
		$html .= '<ul>';
		foreach ($errors as $error) {
			$html .= '<li>' . $error . '</li>';
		}
		$html .= '</ul>';
	} else {
		// Display form submission data
		$html .= '<h2>' . getTranslation('form_submission') . '</h2>';
		$html .= '<ul>';
		foreach ($response as $name => $value) {
			if(is_array($value)) {
				$html .= '<li><strong>' . $name . '</strong>: ' . join(', ', $value) . '</li>';
			} else {
				$html .= '<li><strong>' . $name . '</strong>: ' . $value . '</li>';
			}
		}
		$html .= '</ul>';
	}

	return ["html" => $html, "json" => json_encode($response), "errors" => $errors];
}

// Handle form submission for updating an entry
if(isset($_SERVER['REQUEST_METHOD'])) {
	if (isset($_GET['filters_and_rules'])) {
		$filtersAndFilters = generateQueryBuilderFilter();
		echo json_encode($filtersAndFilters);
		exit;
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (isset($_POST['reset_search'])) {
			$entries = json_decode(json_encode(getAllEntries()), true);
			echo json_encode(array('success' => 'Search reset successfully.', 'entries' => $entries));
			exit;
		}

		if (isset($_POST['search_query'])) {
			$searchQuery = json_decode($_POST['search_query'], true);
			$matchingEntries = $GLOBALS["mdh"]->find($searchQuery);
			echo json_encode($matchingEntries);
			exit;
		}

		// Handle form submission for deleting an entry
		if (isset($_POST['delete_entry_id'])) {
			$entryId = $_POST['delete_entry_id'];
			$response = $GLOBALS["mdh"]->deleteEntry($entryId);
			echo $response;
			exit();
		}

		if(isset($_POST["auto_submit_form"]) && $_POST["auto_submit_form"] == "1") {
			$full = process_autoform($questions);

			$post = json_decode(json_encode($_POST), true);
			unset($post['auto_submit_form']);
			$entryId = (string) new MongoDB\BSON\ObjectID();
			$entryId = $GLOBALS["mdh"]->createId($entryId);
			$response = $GLOBALS["mdh"]->insertDocument($entryId, $post);

			$full['inserter'] = $response;

			echo json_encode($full);		
			exit();
		}

		// Handle form submission for adding a new entry
		if (isset($_POST['new_entry_data'])) {
			$newData = json_decode($_POST['new_entry_data'], true);
			$entryId = (string) new MongoDB\BSON\ObjectID();
			$response = $GLOBALS["mdh"]->insertDocument($entryId, $newData);
			echo $response;
			exit();
		}

		if(isset($_POST["entry_id"])) {
			$entryId = $_POST['entry_id'];
			$newData = json_decode($_POST['json_data'], true);

			$response = $GLOBALS["mdh"]->replaceDocument($entryId, $newData);
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
				$GLOBALS["mdh"]->insertDocument($document);
			}
		}
	}
}

function get_entries_with_geo_coordinates ($entries) {
	$entries_with_geo_coords = [];
	$entries = json_decode(json_encode($entries), true);

	foreach ($entries as $entry) {

		if (isset($entry["geocoords"]) && isset($entry["geocoords"]["lat"]) && isset($entry["geocoords"]["lon"])) {
			$lat = $entry["geocoords"]["lat"];
			$lon = $entry["geocoords"]["lon"];

			// Perform additional checks on lat and lon values if needed

			$entries_with_geo_coords[] = $entry;
		}
	}

	return $entries_with_geo_coords;
}


function find_lat_lon_variables_recursive($entry, $original_entry=null) {
	if(is_null($original_entry)) {
		$original_entry = json_decode(json_encode($entry), true);
	}
	$entry = json_decode(json_encode($entry), true);
	$lat_lon_variables = [];
	$geo_coord_regex = '/^-?\d{1,3}(?:\.\d+)?$/';

	$keywords = [
		["lat", "lon"],
		["latitude", "longitude"]
	];

	if (is_array($entry) || is_object($entry)) {
		foreach ($entry as $key => $value) {
			foreach ($keywords as $kw) {
				$ll = [];

				$lat_name = $kw[0];
				$lon_name = $kw[1];

				if (is_array($value) || is_object($value)) {
					$nested_variables = find_lat_lon_variables_recursive($value, $original_entry);
					$lat_lon_variables = array_merge($lat_lon_variables, $nested_variables);
				} elseif ($key === $lat_name && preg_match($geo_coord_regex, $value) && isset($entry[$lon_name]) && preg_match($geo_coord_regex, $value)) {
					$ll = ['lat' => $value, 'lon' => $entry[$lon_name], 'original_entry' => $original_entry];
				}
				if (count($ll)) {
					$lat_lon_variables[] = $ll;
				}
			}
		}
	} else {
		return [];
	}

	return $lat_lon_variables;
}

function removeDuplicates($r) {
	$uniqueOptions = [];

	for ($i = 0; $i < count($r); $i++) {
		$option = $r[$i];
		$isDuplicate = false;

		for ($j = $i + 1; $j < count($r); $j++) {
			if ($option['id'] === $r[$j]['id'] && $option['label'] === $r[$j]['label']) {
				$isDuplicate = true;
				break;
			}
		}

		if (!$isDuplicate) {
			$uniqueOptions[] = $option;
		}
	}

	return $uniqueOptions;
}

function stderr ($msg) {
	fwrite(STDERR, $msg + "\n");
}
?>
