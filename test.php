<?php
	include("functions.php");


	// Selecting collection using MongoDB\Driver\Command
	$command = new MongoDB\Driver\Command([
		'listCollections' => 1,
		'filter' => [
			'name' => $GLOBALS["collectionName"],
			'type' => 'collection',
		],
	]);

	$cursor = $GLOBALS["mongoClient"]->executeCommand($GLOBALS["databaseName"], $command);

	if ($cursor->valid()) {
		$collection = $cursor->current()->name;
	} else {
		echo "Collection not found.";
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
