<?php
if (!defined('INCLUDED_FROM_INDEX')) {
    die('This file must be included from index.php');
}
?>
			var rules = removeDuplicates(<?php echo json_encode($rules); ?>);
			rules = [rules[0]];

			var filters = removeDuplicates(<?php print json_encode($filters); ?>);

			if(filters.length) {
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

			$('#btn-reset').on('click', function () {
				$('#builder-basic').queryBuilder('reset');
			});

			$('#btn-set').on('click', function () {
				$('#builder-basic').queryBuilder('setRules', JSON.parse(rules));
			});

			$('#btn-get').on('click', function () {
				var result = $('#builder-basic').queryBuilder('getRules');

				if (!$.isEmptyObject(result)) {
					alert(JSON.stringify(result, null, 2));
				}
			});
