<?php
	include("functions.php");

?>
	<h3><?php print $GLOBALS["databaseName"].".".$GLOBALS["collectionName"]; ?> on <?php print $GLOBALS["mongodbHost"].":".$GLOBALS["mongodbPort"]; ?></h3>
<?php
	// Build the query
	$query = new MongoDB\Driver\Query([]);

	// Execute the query
	$cursor = $GLOBALS["mongoClient"]->executeQuery($GLOBALS["namespace"], $query);
	#check_cursor_object($cursor);

	// Process the cursor results
	foreach ($cursor as $document) {
		// Process each document
		echo "<pre>";
		print_r($document);
		echo "</pre>";
	}
?>
