"use strict";

function log (...args) { console.log(args); }

function getQueryParam(param) {
	const urlParams = new URLSearchParams(window.location.search);
	return urlParams.get(param);
}

function removeDuplicates(options) {
	var uniqueOptions = [];

	for (var i = 0; i < options.length; i++) {
		var option = options[i];
		var isDuplicate = false;

		for (var j = i + 1; j < options.length; j++) {
			if (option.id === options[j].id && option.label === options[j].label) {
				isDuplicate = true;
				break;
			}
		}

		if (!isDuplicate) {
			uniqueOptions.push(option);
		}
	}

	return uniqueOptions;
}


function load_all_entries () {
	$.ajax({
		url: PHP_SELF,
		type: 'POST',
		data: {
			reset_search: true
		},
		success: function (response) {
			var data = JSON.parse(response);

			if (data !== null && data.success) {
				toastr.success(data.success);

				// Update the entry list with all entries
				$('#entry_list').html(data.entries);

				// Reinitialize JSON editors
				initJsonEditors();
			} else if (data.error) {
				toastr.error(data.error);
			}
		},
		error: function () {
			toastr.error('Error resetting search.');
		}
	});

}
function searchEntries() {
	var rules = $("#builder-basic").queryBuilder("getRules");

	if (rules !== null) {
		// Convert the query object to a URL parameter string
		var queryParam = encodeURIComponent(JSON.stringify(rules));

		// Update the URL with the search parameter
		var newUrl = updateQueryStringParameter(window.location.href, 'search', queryParam);
		history.pushState({ path: newUrl }, '', newUrl);

		var query = convertRulesToMongoQuery(rules);

		$.ajax({
			url: PHP_SELF,
				type: 'POST',
				data: {
				search_query: JSON.stringify(query)
			},
			success: function (response) {
				var matchingEntries = JSON.parse(response);

				if (matchingEntries.length > 0) {
					// Clear the existing entry list
					$('#entry_list').empty();

					// Update JSON editors for matching entries
					matchingEntries.forEach(function (entry) {
						// Append the updated entry to the container
						$('#entry_list').append('<div id="entry_' + entry._id + '">' +
							'<div id="jsoneditor_' + entry._id + '"></div>' +
							'<button onclick="deleteEntry(\'' + entry._id + '\')">Delete</button>' +
							'</div>');

						// Initialize JSON Editor for the updated entry
						const newEditor = new JSONEditor(
							document.getElementById('jsoneditor_' + entry._id),
							{
								mode: 'tree',
									onBlur: function () {
										const updatedJson = newEditor.get();
										const newJsonData = JSON.stringify(updatedJson, null, 2);
										updateEntry(entry._id, newJsonData);
									}
							}
						);
						newEditor.set(entry);
					});
				} else {
					toastr.info('No matching entries found.');
					load_all_entries();
				}
			},
			error: function () {
				toastr.error('Error searching entries.');
			}
		});
	} else {
		toastr.info('Could not get search rules.');
	}
}
function updateQueryStringParameter(url, key, value) {
	var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
	var separator = url.indexOf('?') !== -1 ? "&" : "?";

	if (url.match(re)) {
		return url.replace(re, '$1' + key + "=" + value + '$2');
	} else {
		return url + separator + key + "=" + value;
	}
}



function resetSearch(e) {
	e.preventDefault();
	e.stopPropagation();
	// Reset the query builder
	$('#builder-basic').queryBuilder('reset');

	// Clear the current query display
	$("#current_query").empty();

	// Load all entries
	load_all_entries();
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
	case 'greater_equal':
		return '$gte';
	case 'less_equal':
		return '$lte';
	case 'in':
		return '$in';
	case 'and':
		return '$and';
	case 'or':
		return '$or';
	case 'not':
		return '$not';
	case 'exists':
		return '$exists';
	case 'type':
		return '$type';
	case 'elem_match':
		return '$elemMatch';
	case 'size':
		return '$size';
	default:
		return operator;
	}
}


function update_current_query(e) {
	e.preventDefault();
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
				if (rule.type == 'integer') {
					value = parseInt(value);
				} else if (rule.type == 'double') {
					value = parseFloat(value);
				} else if (rule.type == 'string') {
					value = "" + value;
				} else if (rule.type == 'boolean') {
					if(value == "true" || value == true) {
						value = true;
					} else {
						value = false;
					}
				} else {
					console.error("Unknown rule type", rule.type, rule);
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


function deleteEntry(entryId, event=null) {
	if(event) {
		event.stopPropagation();
	}
	$.ajax({
		url: PHP_SELF,
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
				if('editor_' + entryId in window) {
					window['editor_' + entryId].destroy();
					delete window['editor_' + entryId];
				}
			} else if (data.error) {
				toastr.error(data.error);
			}
		},
		error: function () {
			toastr.error('Error deleting entry.');
		}
	});
}

function addNewEntry(event) {
	event.stopPropagation();
	const jsonData = {}; // Set your initial data here
	$.ajax({
	url: PHP_SELF,
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



function updateEntry(entryId, jsonData) {
	$.ajax({
		url: PHP_SELF,
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
