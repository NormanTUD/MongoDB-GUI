<?php
	include("functions.php");

?>
	<h3><?php print $GLOBALS["databaseName"].".".$GLOBALS["collectionName"]; ?> on <?php print $GLOBALS["mongodbHost"].":".$GLOBALS["mongodbPort"]; ?></h3>
<?php

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

	check_cursor_object($cursor);

	if ($cursor->valid()) {
		$collection = $cursor->current()->name;
		echo "Collection found: " . $collection;
	} else {
		echo "Collection not found. Here's what I tried:<br>\n";
		print("<pre>=================<br>\n");
		print_r($command);
		print("\n=================<br></pre>");
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
