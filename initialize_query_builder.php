<?php
if (!defined('INCLUDED_FROM_INDEX')) {
	die('This file must be included from index.php');
}
?>
// Load filters and rules from a file via AJAX
$(document).ready(function () {
	var old_t = l("Loading document.ready");
	$.ajax({
		url: 'index.php?filters_and_rules=1',
		dataType: 'json',
		success: function(data) {
			var old_t = l("receiving filters and rules");
			var filters = data.filters;
			var rules = data.rules;


			if(!filters.length) {
				$("#search_stuff").hide();
				le("No DB entries found");
				return;
			}

			if(!rules.length) {
				$("#search_stuff").hide();
				le("No DB entries found");
				return;
			}

			l("building jqueryquerybuilder")

			$('#builder-basic').queryBuilder({
				plugins: ["bt-tooltip-errors"],
				filters: filters,
				rules: rules
			});

			l("showing search")
			$("#search_stuff").show();


			try {
				l("trying to find search param from url")
				var urlParams = new URLSearchParams(window.location.search);
				if (urlParams.has('search')) {
					var searchParam = urlParams.get('search');
					l("found search param from url:" + searchParam)
					try {
						l("trying to build query");
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
					l("no search param found, resetting search");
					resetSearch();
				}
			} catch (e) {
				console.error("ERROR in trying to receive filters and rules:", e);
			}

			l("receiving filters and rules", old_t);
		},
		error: function (e) {
			console.error(e);
			alert("ERROR loading site");
		}
	});

	l("Loading document.ready", old_t);
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
