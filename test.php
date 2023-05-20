<?php
	include("functions.php");

?>
	<h3><?php print $GLOBALS["databaseName"].".".$GLOBALS["collectionName"]; ?> on <?php print $GLOBALS["mongodbHost"].":".$GLOBALS["mongodbPort"]; ?></h3>
<?php
	// Specify the database and collection
	$databaseName = $GLOBALS["databaseName"];
	$collectionName = $GLOBALS["collectionName"];

	// Build the query
	$query = new MongoDB\Driver\Query([]);

	// Execute the query
	$cursor = $GLOBALS["mongoClient"]->executeQuery("$databaseName.$collectionName", $query);
	#check_cursor_object($cursor);

	// Process the cursor results
	foreach ($cursor as $document) {
		// Process each document
		echo "<pre>";
		print_r($document);
		echo "</pre>";
	}
?>
