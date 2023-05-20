<?php
include("functions.php");

// Function to insert a document
function insertDocument($document)
{
	$bulkWrite = new MongoDB\Driver\BulkWrite();
	$bulkWrite->insert($document);

	try {
		$GLOBALS["mongoClient"]->executeBulkWrite($GLOBALS["namespace"], $bulkWrite);
		return json_encode(['success' => 'Entry created successfully.']);
	} catch (Exception $e) {
		return json_encode(['error' => 'Error creating entry: ' . $e->getMessage()]);
	}
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['data'])) {
	$data = $_POST['data'];

	// Detect data format
	$jsonData = json_decode($data, true);
	if ($jsonData !== null && json_last_error() === JSON_ERROR_NONE) {
		echo insertDocument($jsonData);
	} else {
		$lines = explode(PHP_EOL, $data);
		$headers = str_getcsv(array_shift($lines));

		$documents = [];
		foreach ($lines as $line) {
			$row = str_getcsv($line);
			$document = array_combine($headers, $row);
			$documents[] = $document;
		}

		if (!empty($documents)) {
			echo insertDocument($documents);
		} else {
			echo json_encode(['error' => 'Invalid data format']);
		}
	}
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Submit Text</title>
</head>
<body>
    <form method="post">
	<textarea name="data" rows="10" cols="50"></textarea>
	<br>
	<input type="submit" value="Submit">
    </form>
</body>
</html>
