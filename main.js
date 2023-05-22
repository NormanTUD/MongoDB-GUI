"use strict";
var focus_log = {};

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
			'reset_search': true
		},
		success: function (response) {
			var data = JSON.parse(response);

			if (data !== null && data.success) {
				toastr.success(data.success);

				// Update the entry list with all entries
				$('#entry_list').html(data.entries);

				// Reinitialize JSON editors
				data.entries.forEach(function (entry) {
					initJsonEditor(entry);

				});

				var entries = data.entries;

				var entries_with_geo_coords = findLatLonVariablesRecursive(entries);

				updateMap(entries_with_geo_coords);
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
						var entry_id = entry._id;


						// Initialize JSON Editor for the updated entry
						initJsonEditor(entry);
					});

					// Update the map with the new matching entries

					var entries_with_geo_coords = findLatLonVariablesRecursive(matchingEntries);

					updateMap(entries_with_geo_coords);

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

// Initialize JSON Editor for each entry
function initJsonEditor(entry) {
	var entry_id = entry["_id"]["oid"];
	if(!entry_id) {
		entry_id = entry["_id"]["$oid"];
	}

	const containerId = 'jsoneditor_' + entry_id;
	let container = document.getElementById(containerId);

	if (!container) {
		// Create the container element if it doesn't exist
		container = document.createElement('div');
		container.id = containerId;
		document.getElementById('entry_list').appendChild(container);
	}

	var full_entry = '<div id="entry_' + entry_id + '">' +
			'<div id="jsoneditor_' + entry_id + '"></div>' +
			'<button onclick="deleteEntry(\'' + entry_id + '\')">Delete</button>' +
		'</div>'

	$('#entry_list').append(full_entry);

	const editor = new JSONEditor(
		container,
		{
			onFocus: function () {
				focus_log[entry_id] = true;
			},
			mode: 'tree', // view, form
			onBlur: function () {
				if (entry_id in focus_log && focus_log[entry_id] == true) {
					const updatedJson = editor.get();
					const jsonData = JSON.stringify(updatedJson, null, 2);
					const entryId = entry_id;
					updateEntry(entryId, jsonData);
					focus_log[entry._id] = false;
				}
			}
		}
	);


	editor.set(entry);
}

function updateMap(entries) {
	// Create an array to store heatmap data
	var heatmapData = [];

	// Iterate through the entries
	for (var i = 0; i < entries.length; i++) {
		var entry = entries[i];
		var lat = parseFloat(entry.lat);
		var lon = parseFloat(entry.lon);

		// Add the coordinates to the heatmap data
		heatmapData.push([lat, lon]);
	}

	// Clear the existing map markers and heatmap layer
	markerCluster.clearLayers();
	map.removeLayer(heatLayer);

	// Add the new markers and heatmap layer to the map
	for (var i = 0; i < entries.length; i++) {
		var entry = entries[i];
		var lat = entry.lat;
		var lon = entry.lon;

		// Create a marker and add it to the marker cluster group
		var marker = L.marker([lat, lon]);
		markerCluster.addLayer(marker);
	}

	// Create a new heatmap layer with the updated heatmap data
	heatLayer = L.heatLayer(heatmapData, {
		radius: 25, // Adjust the radius as per your preference
		blur: 15, // Adjust the blur as per your preference
		gradient: {
			0.4: 'blue', // Define the colors and positions in the gradient
			0.6: 'cyan',
			0.7: 'lime',
			0.8: 'yellow',
			1.0: 'red'
		}
	});

	// Add the marker cluster group and the new heatmap layer to the map
	markerCluster.addTo(map);
	heatLayer.addTo(map);

	// Fit the map bounds to include both markers and heatmap layer
	try {
		map.fitBounds(markerCluster.getBounds());
		$("#map").show();
	} catch (e) {
		$("#map").hide();
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

function resetSearch(e=false) {
	log("resetSearch");
	if(e) {
		e.preventDefault();
		e.stopPropagation();
	}

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
	case 'greater_or_equal':
		return '$gte';
	case 'less_or_equal':
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
				$('#json_editor_' + entryId).remove();
				// Remove the deleted entry's JSON Editor instance
				if('editor_' + entryId in window) {
					window['editor_' + entryId].destroy();
					delete window['editor_' + entryId];
				}
			} else if (data.error) {
				toastr.error(data.error);
			} else {
				console.error("??? case ???", data);
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

function findLatLonVariablesRecursive(entry, originalEntry = null) {
	if (originalEntry === null) {
		originalEntry = JSON.parse(JSON.stringify(entry));
	}

	const latLonVariables = [];
	const geoCoordRegex = /^[-+]?\d{1,3}(?:\.\d+)?$/;

	const keywords = [
		["lat", "lon"],
		["latitude", "longitude"]
	];

	if (Array.isArray(entry) || typeof entry === "object") {
		for (const key in entry) {
			const value = entry[key];
			for (const kw of keywords) {
				let latLon = {};

				const latName = kw[0];
				const lonName = kw[1];

				if (Array.isArray(value) || typeof value === "object") {
					const nestedVariables = findLatLonVariablesRecursive(value, originalEntry);
					latLonVariables.push(...nestedVariables);
				} else if (key === latName && geoCoordRegex.test(value) && Object.keys(entry).includes(lonName) && geoCoordRegex.test(entry[lonName])) {
					latLon = {
						lat: parseFloat(value),
						lon: parseFloat(entry[lonName]),
						originalEntry: originalEntry
					};
				}

				if (Object.keys(latLon).length !== 0) {
					latLonVariables.push(latLon);
				}
			}
		}
	} else {
		console.error("Entry is not an array/object");
	}
	
	//log("latLonVariables", latLonVariables);
	var no_duplicates = removeDuplicatesFromJSON(latLonVariables);
	//log("no_duplicates", no_duplicates);

	return no_duplicates;
}

function removeDuplicatesFromJSON(arr) {
	const uniqueEntries = [];
	const seenIds = new Set();

	for (const entry of arr) {
		const id = JSON.stringify(entry);

		if (!seenIds.has(id)) {
			uniqueEntries.push(entry);
			seenIds.add(id);
		}
	}

	return uniqueEntries;
}
