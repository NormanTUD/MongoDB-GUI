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
		// Convert the document to an associative array
		$document = (array) $document;

		// Remove the "stdClass Object" prefix
		$document = array_map(function ($value) {
			return is_object($value) ? (array) $value : $value;
		}, $document);

		// Process each document
		echo "<pre>";
		print_r($document);
		echo "</pre>";
	}
?>
