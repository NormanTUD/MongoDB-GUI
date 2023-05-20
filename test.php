<?php
include("functions.php");
?>

<h3><?php echo $GLOBALS["databaseName"] . "." . $GLOBALS["collectionName"]; ?> on <?php echo $GLOBALS["mongodbHost"] . ":" . $GLOBALS["mongodbPort"]; ?></h3>

<?php
	$optionsAndFilters = generateQueryBuilderOptions($GLOBALS["mongoClient"], $GLOBALS["namespace"], $GLOBALS["databaseName"], $GLOBALS["collectionName"], $GLOBALS["mongodbHost"], $GLOBALS["mongodbPort"]);

	$options = $optionsAndFilters["options"];
	$filters = $optionsAndFilters["filters"];

	echo json_encode($options);
	echo json_encode($filters);
?>
