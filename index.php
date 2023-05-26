<?php
// TODO: https://ukrbublik.github.io/react-awesome-query-builder/
	define('INCLUDED_FROM_INDEX', true);
	include("functions.php");
?>

<!DOCTYPE html>
<html>
	<head>
		<title>MongoDB-GUI</title>
		<script>
			var PHP_SELF = '<?php echo basename($_SERVER['PHP_SELF']); ?>';
		</script>
		<?php include("headers.php"); ?>
	</head>
	<body>
		<?php include("language_choser.php"); ?>
		<div id="map" style="height: 400px;"></div>

		<div id="search_stuff">
			<form>
				<div id="builder-basic"></div>
				<button onclick="update_current_query(event);searchEntries()"><span class="TRANSLATEME_search" /></button>
				<button onclick="resetSearch(event)"><span class='TRANSLATEME_reset_search' /></button>
				<div id="current_query"></div>
			</form>
		</div>


		<b><?php print $GLOBALS["databaseName"].".".$GLOBALS["collectionName"]; ?> <span class="TRANSLATEME_on"></span> <?php print $GLOBALS["mongodbHost"].":".$GLOBALS["mongodbPort"]; ?></b><br>

		<div id="entry_list">
		</div>
		<button onclick="addNewEntry(event)"><span class='TRANSLATEME_add_new_entry' /></button>

		<div id="countKeysChart"></div>
		<div id="generalizedVisualizationChart"></div>
<?php
		include("import.php");
?>

		<div id="performance_log"></div>

		<div id="bottom_filler"></div>

		<div id="status-bar">
			<p id='l'>Initializing...</p>
		</div>
	</body>
</html>
