<?php
	include("functions.php");

$filter = [
	'name' => $GLOBALS["collectionName"],
	'type' => 'collection',
];

// Selecting collection using MongoDB\Driver\Command
$command = new MongoDB\Driver\Command([
    'listCollections' => 1,
    'filter' => $filter
]);

$cursor = $GLOBALS["mongoClient"]->executeCommand($GLOBALS["databaseName"], $command);

if ($cursor->valid()) {
    $collection = $cursor->current()->name;
    echo "Collection found: " . $collection;
} else {
    echo "Collection not found. Here's what I tried:<br>\n";
    print("Filter: " . json_encode($filter) . "<br>\n");
    print("Database name: " . $GLOBALS["databaseName"] . "<br>\n");
    return;
}

	// Access the selected collection
	$query = new MongoDB\Driver\Query([]);

	$cursor = $GLOBALS["mongoClient"]->executeQuery($GLOBALS["namespace"], $query);

	foreach ($cursor as $document) {
		// Process each document in the collection
		var_dump($document);
	}
?>
