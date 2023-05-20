<?php
include("functions.php");
?>

<h3><?php echo $GLOBALS["databaseName"] . "." . $GLOBALS["collectionName"]; ?> on <?php echo $GLOBALS["mongodbHost"] . ":" . $GLOBALS["mongodbPort"]; ?></h3>

<?php
	$optionsAndFilters = generateQueryBuilderOptions($mongoClient, $namespace, $databaseName, $collectionName, $mongodbHost, $mongodbPort);

	echo json_encode($optionsAndFilters);
?>
