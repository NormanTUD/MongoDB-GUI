"use strict";
var focus_log = {};
var performance_log = {};

var markerCluster = null;
var map = null;
var heatLayer = null;

function log (...args) { console.log(args); }

function le (msg) {
	return l (msg, null, "error");
}

function l (msg, old_ts=null, printer="log") {
	var ct = t();

	if(old_ts) {
		var delta_t = (ct - old_ts) / 1000;
		var original_msg = msg;
		msg = msg + ` (took ${delta_t} s)`
		$("#l").html(msg);

		if(!Object.keys(performance_log).includes(original_msg)) {
			performance_log[original_msg] = [];
		}
		performance_log[original_msg].push(delta_t);

		performance_log_table();
	}

	$("#l").html(msg);
	if(printer == "log") {
		log(msg);
	} else if (printer == "error") {
		console.error(msg);
	} else {
		console.error("Unknown printer");
		log(msg);
	}

	return ct;
}

function getQueryParam(param) {
	var old_ts = l("getQueryParam");
	const urlParams = new URLSearchParams(window.location.search);
	l("getQueryParam", old_ts);
	return urlParams.get(param);
}

function removeDuplicates(r) {
	var old_ts = l("removeDuplicates");
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

	l("removeDuplicates", old_ts);
	return uniqueOptions;
}


function load_all_entries () {
	var old_ts = l("load_all_entries");
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
				visualizations(entries)
			} else if (data.error) {
				toastr.error(data.error);
			}
		},
		error: function () {
			toastr.error('Error resetting search.');
		}
	});

	l("load_all_entries", old_ts);
}

async function visualizations (entries) {
	// Update the map with the new matching entries
	var entries_with_geo_coords = findLatLonVariablesRecursive(entries);
	updateMap(entries_with_geo_coords);

	generalizedVisualization(entries);
	countKeys(entries);
	var groups = await groupJSONStructures(entries);
	if(groups) {
		var old_ts = l("groups: " + groups);
	}
}

function searchEntries() {
	var old_ts = l("searchEntries");
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


					// Generate the visualization
					visualizations(matchingEntries);
				} else {
					toastr.info('No matching entries found.');
				}
			},
			error: function() {
				toastr.error('Error searching entries.');
			}
		});
	} else {
		toastr.info('Could not get search rules.');
	}

	l("searchEntries", old_ts);
}

function avg(values) {
	var old_ts = l("avg");
	if (values.length === 0) {
		return 0;
	}

	var sum = values.reduce(function (accumulator, currentValue) {
		return accumulator + currentValue;
	}, 0);

	l("avg", old_ts);
	return sum / values.length;
}

function countKeys(entries) {
	var old_ts = l("countKeys");
	// Calculate the total number of entries
	var totalEntries = entries.length;

	// Count the occurrence of each property
	var propertyCounts = {};
	entries.forEach(function(entry) {
		Object.keys(entry).forEach(function(property) {
			if(property != "_id") {
				if (!propertyCounts.hasOwnProperty(property)) {
					propertyCounts[property] = 0;
				}
				propertyCounts[property]++;
			}
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

	var layout = {
		title: 'Occurency of keys',
		xaxis: {
			title: 'Fields'
		},
		yaxis: {
			title: 'Results'
		}
	};

	Plotly.newPlot('countKeysChart', data, layout);

	l("countKeys", old_ts);
	return data;
}

// Group JSON structures by nested structure
async function groupJSONStructures(entries) {
	var old_ts = l("groupJSONStructures");
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

	l("groupJSONStructures", old_ts);
	return groupCount;
}

function sumArrayElements(arr) {
	var sum = 0;
	for (var i = 0; i < arr.length; i++) {
		sum += arr[i];
	}
	return sum;
}

function filterUndefinedAndNull(arr) {
	return arr.filter(element => element !== undefined && element !== null);
}

async function generalizedVisualization(entries) {
	var old_ts = l("generalizedVisualization");
	var analyze_fields = {
		'lat (avg)': {
			'aggregation': 'average',
			'column': 'lat'
			/*
			'analysis': function(values) {
				return values;
			}
			*/
		},
		'lat (distinct)': {
			'aggregation': 'distinct',
			'column': 'lat'
		},
		'lat (min)': {
			'aggregation': 'min',
			'column': 'lat'
		},
		'lat (sum)': {
			'aggregation': 'sum',
			'column': 'lat'
		},
		'lat (max)': {
			'aggregation': 'max',
			'column': 'lat'
		},
		'lat (count)': {
			'aggregation': 'count',
			'column': 'lat'
		}
		// Add more fields and analysis functions as needed
	};

	var data = [];
	Object.entries(analyze_fields).forEach(([field, config]) => {
		var column = config.column;
		var values = entries.map(entry => entry[column]);
		values = filterUndefinedAndNull(values);
		var result = null;

		// Perform aggregation or analysis based on the configuration
		switch (config.aggregation) {
			case 'count':
				result = values.length;
				break;
			case 'max':
				result = Math.max(...values);
				break;
			case 'min':
				result = Math.min(...values);
				break;
			case 'sum':
				result = sumArrayElements(values);
				break;
			case 'average':
				result = avg(values);
				break;
			case 'distinct':
				result = [...new Set(values)].length;
				break;
			case 'custom':
				result = config.analysis(values);
				break;
			case 'none':
			default:
				result = null;
				break;
		}

		if(result !== null) {
			data.push({
				field: field,
				result: result
			});
		} else {
			le("No result could be obtained");
			log("=====")
			log("values:", values);
			log("config:", config);
			log("=====")
		}
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
		title: 'Customizable stats',
		xaxis: {
			title: 'Fields'
		},
		yaxis: {
			title: 'Results'
		}
	};

	Plotly.newPlot('generalizedVisualizationChart', [trace], layout);
	l("generalizedVisualization", old_ts);
}

function appendEntry (entry_id) {
	var id = "entry_" + entry_id;
	if(!$("#" + id).length) {
		var full_entry = '<div id="' + id + '">' +
				'<div id="jsoneditor_' + entry_id + '"></div>' +
				'<button onclick="deleteEntry(\'' + entry_id + '\')">Delete</button>' +
			'</div>'

		$('#entry_list').append(full_entry);
	}

	return $("#" + id)[0];
}


// Initialize JSON Editor for each entry
function initJsonEditor(entry) {
	var old_ts = l("initJsonEditor");
	var entry_id = entry["_id"]["oid"];
	if(!entry_id) {
		entry_id = entry["_id"]["$oid"];
	}

	var container = appendEntry(entry_id);

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
	l("initJsonEditor", old_ts);
}

function updateMap(entries) {
	var old_ts = l("updateMap");
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
	l("updateMap", old_ts);
}

function updateQueryStringParameter(url, key, value) {
	var old_ts = l("updateQueryStringParameter");
	var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
	var separator = url.indexOf('?') !== -1 ? "&" : "?";

	if (url.match(re)) {
		return url.replace(re, '$1' + key + "=" + value + '$2');
	} else {
		return url + separator + key + "=" + value;
	}
	l("updateQueryStringParameter", old_ts);
}

function removeQueryStringParameter(url, key) {
	var old_ts = l("removeQueryStringParameter");
	var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
	var separator = url.indexOf('?') !== -1 ? "&" : "?";

	if (url.match(re)) {
		return url.replace(re, function (match, p1, p2) {
			if (p1 === "?" || p1 === "&") {
				return p2 === "&" ? p1 : "";
			} else {
				return p1;
			}
		});
	} else {
		return url;
	}

	l("removeQueryStringParameter", old_ts);
}

function resetSearch(e=false) {
	var old_ts = l("resetSearch");
	if(e) {
		e.preventDefault();
		e.stopPropagation();
	}

	var newUrl = removeQueryStringParameter(window.location.href, 'search');
	history.pushState({ path: newUrl }, '', newUrl);

	// Reset the query builder
	$('#builder-basic').queryBuilder('reset');

	// Clear the current query display
	$("#current_query").empty();

	// Load all entries
	load_all_entries();

	l("resetSearch", old_ts);
}

function getMongoOperator(operator) {
	var old_ts = l("getMongoOperator");
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
	l("getMongoOperator", old_ts);
}

function update_current_query(e=null) {
	var old_ts = l("update_current_query");
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
	l("update_current_query", old_ts);
}

function convertRulesToMongoQuery(rules) {
	var old_ts = l("convertRulesToMongoQuery");
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

	l("convertRulesToMongoQuery", old_ts);
	return query;
}

function deleteEntry(entryId, event=null) {
	var old_ts = l("deleteEntry");
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
			try {
				var data = JSON.parse(response);
				if (data.success) {
					try {
						toastr.success(data.success);
						// Remove the deleted entry from the page
						var entry_id = 'entry_id' + data.entryId
						var editor_id = 'json_editor_' + data.entryId
						log(data.entryId['$oid'])
						log('Removing "#entry_' + data.entryId['$oid'] + "'");
						$('#entry_' + data.entryId['$oid']).remove();
						// Remove the deleted entry's JSON Editor instance
						if('editor_' + data.entryId['$oid'] in window) {
							window['editor_' + data.entryId['$oid']].destroy();
							delete window['editor_' + data.entryId['$oid']];
						}
					} catch (e) {
						console.error(e);
					}
				} else if (data.error) {
					toastr.error(data.error);
				} else {
					console.error("??? case ???", data);
				}
			} catch (e) {
				toastr.error(e);
			}
		},
		error: function () {
			toastr.error('Error deleting entry.');
		}
	});
	
	l("deleteEntry", old_ts);
}

function addNewEntry(event) {
	var old_ts = l("addNewEntry");
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

				appendEntry(data.entryId);

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

	l("addNewEntry", old_ts);
}

function updateEntry(entryId, jsonData) {
	var old_ts = l("updateEntry");
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

	l("updateEntry", old_ts);
}

function findLatLonVariablesRecursive(entry, originalEntry = null) {
	var old_ts;
	if (originalEntry === null) {
		originalEntry = JSON.parse(JSON.stringify(entry));
		old_ts = l("findLatLonVariablesRecursive");
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
	var no_duplicates = removeDuplicatesFromJSON(latLonVariables, !!old_ts);
	//log("no_duplicates", no_duplicates);

	if(old_ts) {
		l("findLatLonVariablesRecursive", old_ts);
	}
	return no_duplicates;
}

function removeDuplicatesFromJSON(arr, enable_log=0) {
	var old_ts;
	if(enable_log) {
		old_ts = l("removeDuplicatesFromJSON");
	}
	const uniqueEntries = [];
	const seenIds = new Set();

	for (const entry of arr) {
		const id = JSON.stringify(entry);

		if (!seenIds.has(id)) {
			uniqueEntries.push(entry);
			seenIds.add(id);
		}
	}

	if(enable_log) {
		l("removeDuplicatesFromJSON", old_ts);
	}

	return uniqueEntries;
}

function t(oldTimestamp) {
	var currentTimestamp = Date.now();

	if (oldTimestamp) {
		var difference = currentTimestamp - oldTimestamp;
		return difference;
	} else {
		return currentTimestamp;
	}
}

function performance_log_table () {
	// Convert the data into an array of objects
	var dataArray = Object.entries(performance_log).map(([key, value]) => ({ key, value }));

	// Sort the array based on the largest values first
	dataArray.sort((a, b) => b.value - a.value);

	// Generate the table HTML
	var tableHTML = "<table><thead><tr><th>Function</th><th>Execution Time</th></tr></thead><tbody>";

	dataArray.forEach(function (item) {
		var functionName = item.key;
		var executionTimes = item.value;

		tableHTML += "<tr><td>" + functionName + "</td><td>" + executionTimes.join(", ") + "</td></tr>";
	});

	tableHTML += "</tbody></table>";

	// Append the table to a container element
	var container = document.getElementById("performance_log");
	container.innerHTML = tableHTML;
}
