"use strict";
var focus_log = {};

var markerCluster = null;
var map = null;
var heatLayer = null;


function log (...args) { console.log(args); }

function l (msg) {
	log(msg);
	$("#l").html(msg);

}

function getQueryParam(param) {
	l("getQueryParam");
	const urlParams = new URLSearchParams(window.location.search);
	return urlParams.get(param);
}

function removeDuplicates(r) {
	l("removeDuplicates");
	var uniqueOptions = [];

	for (var i = 0; i < r.length; i++) {
		var option = r[i];
		var isDuplicate = false;

		for (var j = i + 1; j < r.length; j++) {
			if (option.id === r[j].id && option.label === r[j].label) {
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
	l("load_all_entries");
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
	l("searchEntries");
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
			success: function(response) {
				var matchingEntries = JSON.parse(response);

				if (matchingEntries.length > 0) {
					// Clear the existing entry list
					$('#entry_list').empty();

					// Update JSON editors for matching entries
					matchingEntries.forEach(function(entry) {
						// Append the updated entry to the container
						var entry_id = entry._id;

						// Initialize JSON Editor for the updated entry
						initJsonEditor(entry);
					});

					// Update the map with the new matching entries
					var entries_with_geo_coords = findLatLonVariablesRecursive(matchingEntries);
					updateMap(entries_with_geo_coords);

					// Generate the visualization
					generateVisualization(matchingEntries);
					generatePlotlyData(matchingEntries);
					var groups = groupJSONStructures(matchingEntries);
					if(groups) {
						l("groups: " + groups);
					}
				} else {
					toastr.info('No matching entries found.');
					load_all_entries();
				}
			},
			error: function() {
				toastr.error('Error searching entries.');
			}
		});
	} else {
		toastr.info('Could not get search rules.');
	}
}

function avg(values) {
	l("avg");
	if (values.length === 0) {
		return 0;
	}

	var sum = values.reduce(function (accumulator, currentValue) {
		return accumulator + currentValue;
	}, 0);

	return sum / values.length;
}

function generatePlotlyData(entries) {
	l("generatePlotlyData");
	// Calculate the total number of entries
	var totalEntries = entries.length;

	// Count the occurrence of each property
	var propertyCounts = {};
	entries.forEach(function(entry) {
		Object.keys(entry).forEach(function(property) {
			if (!propertyCounts.hasOwnProperty(property)) {
				propertyCounts[property] = 0;
			}
			propertyCounts[property]++;
		});
	});

	// Identify properties with numerical values
	var numericProperties = [];
	entries.forEach(function(entry) {
		Object.keys(entry).forEach(function(property) {
			if (typeof entry[property] === 'number' && !numericProperties.includes(property)) {
				numericProperties.push(property);
			}
		});
	});

	// Generate data for plotting
	var propertyLabels = Object.keys(propertyCounts);
	var propertyOccurrences = Object.values(propertyCounts);

	// Create the Plotly data array
	var data = [{
		x: propertyLabels,
		y: propertyOccurrences,
		type: 'bar'
	}];

	return data;
}

// Group JSON structures by nested structure
function groupJSONStructures(entries) {
	l("groupJSONStructures");
	var groups = {};

	// Helper function to recursively traverse the data and build grouping keys
	function buildGroupingKey(data, path = '') {
		var keyValuePairs = [];

		if (Array.isArray(data)) {
			data.forEach(function(value, index) {
				var subPath = path + '[' + index + ']';
				var subKey = buildGroupingKey(value, subPath);
				keyValuePairs.push(subKey);
			});
		} else if (typeof data === 'object' && data !== null) {
			Object.keys(data).forEach(function(key) {
				var subPath = path + "['" + key + "']";
				var subKey = buildGroupingKey(data[key], subPath);
				keyValuePairs.push(subKey);
			});
		} else {
			keyValuePairs.push(path + '=' + data);
		}

		return keyValuePairs.join('-');
	}

	// Group JSON structures by nested structure
	entries.forEach(function(data) {
		var groupingKey = buildGroupingKey(data);
		if (!groups.hasOwnProperty(groupingKey)) {
			groups[groupingKey] = [];
		}
		groups[groupingKey].push(data);
	});

	// Count the number of different groups
	var groupCount = Object.keys(groups).length;

	return groupCount;
}


function generateVisualization(entries) {
	l("generateVisualization");
	var analyze_fields = {
		'age (avg)': {
			'aggregation': 'average',
			'column': 'age',
			'analysis': function(values) {
				return avg(values);
			}
		},
		'age (count)': {
			'aggregation': 'count',
			'column': 'age',
			'analysis': function(values) {
				return values;
			}
		}
		// Add more fields and analysis functions as needed
	};

	var data = [];
	Object.entries(analyze_fields).forEach(([field, config]) => {
		var column = config.column;
		var values = entries.map(entry => entry[column]);

		// Perform aggregation or analysis based on the configuration
		switch (config.aggregation) {
			case 'count':
				var result = values.length;
				break;
			case 'distinct':
				var result = [...new Set(values)].length;
				break;
			case 'custom':
				var result = config.analysis(values);
				break;
			case 'none':
			default:
				var result = config.analysis(values);
				break;
		}

		data.push({
			field: field,
			result: result
		});
	});

	// Plotting logic using Plotly.js
	// Customize this part to generate the desired visualization

	// Example: Generate a bar chart
	var x = data.map(entry => entry.field);
	var y = data.map(entry => entry.result);

	var trace = {
		x: x,
		y: y,
		type: 'bar'
	};

	var layout = {
		title: 'General Statistics',
		xaxis: {
			title: 'Fields'
		},
		yaxis: {
			title: 'Results'
		}
	};

	Plotly.newPlot('chart_two', [trace], layout);
}


// Initialize JSON Editor for each entry
function initJsonEditor(entry) {
	l("initJsonEditor");
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
	l("updateMap");
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



	if(markerCluster === null) {
		markerCluster = L.markerClusterGroup();
		map = L.map('map').setView([0, 0], 2); // Set initial center and zoom level
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
		// Add OpenStreetMap tile layer
		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors',
			maxZoom: 18
		}).addTo(map);
	}










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
	l("updateQueryStringParameter");
	var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
	var separator = url.indexOf('?') !== -1 ? "&" : "?";

	if (url.match(re)) {
		return url.replace(re, '$1' + key + "=" + value + '$2');
	} else {
		return url + separator + key + "=" + value;
	}
}

function resetSearch(e=false) {
	l("resetSearch");
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
	l("getMongoOperator");
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

function update_current_query(e=null) {
	l("update_current_query");
	if(e) {
		e.preventDefault();
		e.stopPropagation();
	}

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
	l("convertRulesToMongoQuery");
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
	l("deleteEntry");
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
	l("addNewEntry");
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
	l("updateEntry");
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
	l("findLatLonVariablesRecursive");
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
	log("removeDuplicatesFromJSON");
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
