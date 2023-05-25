<?php
if (!defined('INCLUDED_FROM_INDEX')) {
	die('This file must be included from index.php');
}
?>
// Load filters and rules from a file via AJAX
$(document).ready(function () {
	var old_t = l("Loading document.ready");
	Swal.fire({
		allowOutsideClick: false,
		showConfirmButton: false,
		showCancelButton: false,
		title: "The site is loading. This may take a minute.",
		html: 'Please wait'
	});
	$.ajax({
		url: 'index.php?filters_and_rules=1',
		dataType: 'json',
		success: async function(data) {
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

			var tmp = l("building jqueryquerybuilder")

			$('#builder-basic').queryBuilder({
				plugins: ["bt-tooltip-errors"],
				filters: filters,
				rules: rules
			});

			l("building jqueryquerybuilder", tmp);

			tmp = l("showing search")
			$("#search_stuff").show();
			l("showing search", tmp);


			try {
				var tmp_sp_t = l("trying to find search param from url")
				var urlParams = new URLSearchParams(window.location.search);
				if (urlParams.has('search')) {
					var searchParam = urlParams.get('search');
					l("found search param from url: " + searchParam)
					log("searchParam:", searchParam);
					if(searchParam == "undefined" || searchParam === undefined || !searchParam) {
						searchParam = "{}";
					}

					try {
						var tmp_bq = l("trying to build query");
						var query = JSON.parse(decodeURIComponent(searchParam));
						l("trying to build query", tmp_bq);

						// Set the query rules in the query builder
						var tmp_sr = l("setRules");
						console.log("QUERY:", query);
						$("#builder-basic").queryBuilder("setRules", query);
						l("setRules", tmp_sr);

						// Trigger the search
						var tmp_se = l("search entries");
						searchEntries();
						l("search entries", tmp_se);
					} catch (e) {
						le("ERROR: Could not parse search string from url");
						console.error(e);
					}
				} else {
					var tmp_sp = l("no search param found, resetting search");
					resetSearch();
					l("no search param found, resetting search", tmp_sp);
				}
				l("trying to find search param from url", tmp_sp_t)
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
