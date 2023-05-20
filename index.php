<?php
include("functions.php");

// Handle form submission for updating an entry
if(isset($_SERVER['REQUEST_METHOD'])) {
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		// Handle form submission for deleting an entry
		if (isset($_POST['delete_entry_id'])) {
			$entryId = $_POST['delete_entry_id'];
			$response = deleteEntry($entryId);
			echo $response;
			exit();
		}

		// Handle form submission for adding a new entry
		if (isset($_POST['new_entry_data'])) {
			$newData = json_decode($_POST['new_entry_data'], true);
			$entryId = (string) new MongoDB\BSON\ObjectID();
			$response = updateEntry($entryId, $newData);
			echo $response;
			exit();
		}

		if(isset($_POST["entry_id"])) {
			$entryId = $_POST['entry_id'];
			$newData = json_decode($_POST['json_data'], true);

			$response = updateEntry($entryId, $newData);
			echo $response;
			exit();
		}
	}
}

// Retrieve all entries
$entries = getAllEntries();
?>

<!DOCTYPE html>
<html>
	<head>
		<title>MongoDB-GUI</title>
		<script src="jquery-3.6.0.min.js"></script>
		<link rel="stylesheet" href="style.css"/>
		<link rel="stylesheet" href="jsoneditor.min.css"/>
		<script src="jsoneditor.min.js"></script>
		<link rel="stylesheet" href="toastr.min.css"/>
		<script src="query-builder.standalone.min.js"></script>
		<link href="query-builder.default.min.css" rel="stylesheet">
		<script src="toastr.min.js"></script>
		<script>
			function log (...args) { console.log(args); }

			var focus_log = {};

			// Initialize JSON Editor for each entry
			function initJsonEditors() {
				<?php foreach ($entries as $entry): ?>
				const editor_<?php echo $entry->_id; ?> = new JSONEditor(
				document.getElementById('jsoneditor_<?php echo $entry->_id; ?>'),
					{
						onFocus: function () {
							focus_log["<?php echo $entry->_id; ?>"] = true;
						},
						mode: 'tree',
						onBlur: function () {
							if("<?php echo $entry->_id; ?>" in focus_log && focus_log["<?php echo $entry->_id; ?>"] == true) {
								const updatedJson = editor_<?php echo $entry->_id; ?>.get();
								const jsonData = JSON.stringify(updatedJson, null, 2);
								const entryId = '<?php echo $entry->_id; ?>';
								updateEntry(entryId, jsonData);
								focus_log["<?php echo $entry->_id; ?>"] = false;
							}
						}
					}
				);

				    editor_<?php echo $entry->_id; ?>.set(<?php echo json_encode($entry, JSON_UNESCAPED_UNICODE); ?>);
			    <?php endforeach; ?>
			}

			function deleteEntry(entryId, event=null) {
				if(event) {
					event.stopPropagation();
				}
				$.ajax({
					url: '<?php echo basename($_SERVER['PHP_SELF']); ?>',
						type: 'POST',
						data: {
						delete_entry_id: entryId
					},
					success: function (response) {
						var data = JSON.parse(response);
						if (data.success) {
							toastr.success(data.success);
							// Remove the deleted entry from the page
							$('#entry_' + entryId).remove();
							// Remove the deleted entry's JSON Editor instance
							window['editor_' + entryId].destroy();
							delete window['editor_' + entryId];
						} else if (data.error) {
							toastr.error(data.error);
						}
					},
					error: function () {
						toastr.error('Error deleting entry.');
					}
				});
			}



			// Function to add a new entry via AJAX
			function addNewEntry(event) {
				event.stopPropagation();
				const jsonData = {}; // Set your initial data here
				$.ajax({
				url: '<?php echo basename($_SERVER['PHP_SELF']); ?>',
					type: 'POST',
					data: {
						new_entry_data: JSON.stringify(jsonData)
					},
					success: function (response) {
						var data = JSON.parse(response);
						if (data.success) {
							toastr.success(data.success);
							// Append the new entry to the container
							$('#entry_list').append('<div id="entry_' + data.entryId + '">' +
								'<div id="jsoneditor_' + data.entryId + '"></div>' +
								'<button onclick="deleteEntry(\'' + data.entryId + '\')">Delete</button>' +
								'</div>');
							const newEditor = new JSONEditor(
								document.getElementById('jsoneditor_' + data.entryId),
								{
									mode: 'tree',
										onBlur: function () {
											const updatedJson = newEditor.get();
											const newJsonData = JSON.stringify(updatedJson, null, 2);
											updateEntry(data.entryId, newJsonData);
										}
								}
							);
							newEditor.set(jsonData);
						} else if (data.error) {
							toastr.error(data.error);
						}
					},
					error: function () {
						toastr.error('Error adding new entry.');
					}
				});
			}


			// Function to update an entry via AJAX
			function updateEntry(entryId, jsonData) {
				$.ajax({
					url: '<?php echo basename($_SERVER['PHP_SELF']); ?>',
						type: 'POST',
						data: {
							entry_id: entryId,
							json_data: jsonData
						},
					success: function (response) {
						var data = JSON.parse(response);
						if (data.success) {
							toastr.success(data.success);
						} else if (data.error) {
							toastr.error(data.error);
						}
					},
					error: function () {
						toastr.error('Error updating entry.');
					}
				});
			}

			// Call the initialization function
			$(document).ready(function () {
				initJsonEditors();
			});

		</script>
	</head>
	<body>
		<h2>Search</h2>
		<form>
			<div id="builder-basic"></div>
			<button onclick="update_current_query(event)">Create search query</button>
			<div id="current_query"></div>
		</form>

		<h3><?php print $GLOBALS["databaseName"].".".$GLOBALS["collectionName"]; ?> on <?php print $GLOBALS["mongodbHost"].":".$GLOBALS["mongodbPort"]; ?></h3>

		<div id="entry_list">
			<?php foreach ($entries as $entry): ?>
				<div id="entry_<?php echo $entry->_id; ?>">
					<div id="jsoneditor_<?php echo $entry->_id; ?>"></div>
					<button onclick="deleteEntry('<?php echo $entry->_id; ?>', event)">Delete</button>
				</div>
			<?php endforeach; ?>
		</div>

		<!-- Button to add a new entry -->
		<button onclick="addNewEntry(event)">Add New Entry</button>
<?php
		$optionsAndFilters = generateQueryBuilderOptions();
		$options = $optionsAndFilters["options"];
		$filters = $optionsAndFilters["filters"];

?>
		<script>
			var options = <?php echo json_encode($options); ?>;

			$('#builder-basic').queryBuilder({
				plugins: [],
				filters: <?php print json_encode($filters); ?>,
				rules: options
			});

			$('#btn-reset').on('click', function () {
				$('#builder-basic').queryBuilder('reset');
			});

			$('#btn-set').on('click', function () {
				$('#builder-basic').queryBuilder('setRules', JSON.parse(options));
			});

			$('#btn-get').on('click', function () {
				var result = $('#builder-basic').queryBuilder('getRules');

				if (!$.isEmptyObject(result)) {
					alert(JSON.stringify(result, null, 2));
				}
			});

			function update_current_query(e) {
				event.preventDefault();
				e.stopPropagation();
				var rules = $("#builder-basic").queryBuilder("getRules");

				if (rules !== null) {
					var query = convertRulesToMongoQuery(rules);
					var query_string = JSON.stringify(query);
					$("#current_query").html("<pre>" + query_string + "</pre>");
				} else {
					$("#current_query").html("<pre>Could not get rules. Some search settings are probably missing. Look out for red highlighted lines.</pre>");
				} 
			}

			function convertRulesToMongoQuery(rules) {
				var condition = rules.condition.toUpperCase();
				var query = {};

				if (rules.rules && rules.rules.length > 0) {
					var subQueries = rules.rules.map(function(rule) {
						if (rule.rules && rule.rules.length > 0) {
							return convertRulesToMongoQuery(rule);
						} else {
							var operator = getMongoOperator(rule.operator);
							var value = rule.value;
							if (rule.type === 'integer') {
								value = parseInt(value);
							} else if (rule.type === 'double') {
								value = parseFloat(value);
							} else if (rule.type === 'boolean') {
								value = (value === 'true');
							}
							var fieldQuery = {};
							fieldQuery[operator] = value;
							return {
							[rule.field]: fieldQuery
							};
						}
					});

					if (condition === 'AND') {
						query = {
						$and: subQueries
					};
					} else if (condition === 'OR') {
						query = {
						$or: subQueries
					};
					}
				}

				return query;
			}

			function getMongoOperator(operator) {
				switch (operator) {
				case 'equal':
					return '$eq';
				case 'not_equal':
					return '$ne';
				case 'contains':
					return '$regex';
				case 'greater':
					return '$gt';
				case 'less':
					return '$lt';
					// Add more operators as needed
				default:
					return operator;
				}
			}
		</script>
	</body>
</html>
