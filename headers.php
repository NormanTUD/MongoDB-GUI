<script src="jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="style.css"/>
<link rel="stylesheet" href="bootstrap.min.css"/>
<script src="popper.min.js"></script>
<script src="bootstrap.min.js"></script>
<link rel="stylesheet" href="jsoneditor.min.css"/>
<script src="jsoneditor.min.js"></script>
<link rel="stylesheet" href="toastr.min.css"/>
<script src="query-builder.standalone.min.js"></script>
<link href="query-builder.default.min.css" rel="stylesheet">
<script src="toastr.min.js"></script>
<link rel="stylesheet" href="leaflet.css">
<script src="leaflet.js"></script>
<link rel="stylesheet" href="MarkerCluster.css" />
<link rel="stylesheet" href="MarkerCluster.Default.css" />
<link rel="stylesheet" href="nouislider.min.css" />
<script src="nouislider.min.js"></script>
<script src="leaflet.markercluster.js"></script>
<script src="leaflet-heat.js"></script>
<script src="plotly-latest.min.js"></script>
<script src="main.js"></script>
<script src="translations.js"></script>
<script>
	const language = <?php print json_encode($language); ?>;

	$(document).ready(function () {
		updateTranslations();
	});
</script>
