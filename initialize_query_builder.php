<?php
if (!defined('INCLUDED_FROM_INDEX')) {
	die('This file must be included from index.php');
}
?>
// Load filters and rules from a file via AJAX
$(document).ready(function () {
	$.ajax({
		url: 'index.php?filters_and_rules=1',
		dataType: 'json',
		success: function(data) {
log(data);
			var filters = removeDuplicates(data.filters);
			var rules = removeDuplicates(data.rules);


			if (filters.length) {
				$('#builder-basic').queryBuilder({
					plugins: ["bt-tooltip-errors"],
					filters: filters,
					rules: rules
				});
				$("#search_stuff").show();
			} else {
				$("#search_stuff").hide();
				console.log("No DB entries found");
			}


			var urlParams = new URLSearchParams(window.location.search);
			if (urlParams.has('search')) {
				var searchParam = urlParams.get('search');
				try {
					var query = JSON.parse(decodeURIComponent(searchParam));

					// Set the query rules in the query builder
					$("#builder-basic").queryBuilder("setRules", query);

					// Trigger the search
					searchEntries();
				} catch (e) {
					alert("ERROR: Could not parse search string from url");
					console.error("ERROR: Could not parse search string from url");
					console.error(e);
				}
			} else {
				resetSearch();
			}

		}
	});
});

$('#btn-reset').on('click', function() {
	$('#builder-basic').queryBuilder('reset');
});

$('#btn-set').on('click', function() {
	var rules = JSON.parse($('#rules-json').val());
	$('#builder-basic').queryBuilder('setRules', rules);
});

$('#btn-get').on('click', function() {
	var result = $('#builder-basic').queryBuilder('getRules');

	if (!$.isEmptyObject(result)) {
		alert(JSON.stringify(result, null, 2));
	}
});
