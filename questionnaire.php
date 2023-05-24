<?php
define('INCLUDED_FROM_INDEX', true);
include_once("functions.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .language-selector {
            display: inline-block;
            margin-right: 5px;
        }
    </style>
<?php
	include("headers.php");
?>
	<script>
		const language = <?php print json_encode($language); ?>;
		const questions = <?php print json_encode($questions); ?>;

		function getFormData($form){
			var unindexed_array = $form.serializeArray();
			var indexed_array = {};

			$.map(unindexed_array, function(n, i){
				indexed_array[n['name']] = n['value'];
			});

			return indexed_array;
		}

		function submitForm(e) {
			if(e) {
				e.preventDefault();
				e.stopPropagation();
			}

			var serialized_form = $('#myForm').serialize();

			$.ajax({
				type : 'POST',
				url : 'questionnaire.php',
				data : serialized_form,
				success: function (data) {
					// Handle the server response
					var resultContainer = document.getElementById('resultContainer');
					try {
						var d = JSON.parse(data);
						var inserter = JSON.parse(d.inserter);
						resultContainer.innerHTML = d.html;
						try {
							var json = d.json;
							if(json) {
								if(inserter.success) {
									toastr.success("OK", inserter.success);
									console.error(json);
								} else if (inserter.error) {
									toastr.error("Inserter failed", inserter.error);
									console.error(json);
								} else {
									toastr.error("Inserter failed");
									console.error(json);
								}
							} else {
								toastr.warning("json was empty from questionnaire");
							}
						} catch (e) {
							toastr.error(e, "Error 1:");
						}
					} catch (e) {
						toastr.error(e, "Error 2:");
					}
					updateTranslations();
				},
				error: function (e) {
					toastr.error(e, "Error 3:");
					updateTranslations();
				}
			});

		}
	</script>
    <title>Questionnaire Test</title>
</head>

<body>
	<?php include("language_choser.php"); ?>
	<div id="resultContainer"></div>
    <h1><?php echo getTranslation('h1'); ?></h1>
<form method="POST" enctype="multipart/form-data" id='myForm'>
    <input type="hidden" name="auto_submit_form" value=1 />
    <?php echo generateFormFields($questions); ?>
    <br>
    <br>
    <button onclick='submitForm(event);' type="submit"><?php echo getTranslation('submit'); ?></button>
</form>
	<script>
// Get the language from the query string parameter 'lang'
const urlParams = new URLSearchParams(window.location.search);
let lang = urlParams.get('lang') || 'en'; // Default language is English

function setLang (l) {
	lang = l;
	updateTranslations();
}

// Function to update the translation of elements
function updateTranslations() {
	const elements = document.querySelectorAll('[class^="TRANSLATEME_"]');
	elements.forEach((element) => {
		const translationKey = element.classList[0].substring(12);
		const translation = language[lang][translationKey];
		if(translation) {
			element.textContent = translation;
		} else {
			alert("Could not translate " + translationKey + " to " + lang);
		}
	});
}

// Update translations on initial page load
updateTranslations();

// Update translations when language selector links are clicked
var languageSelectors = $(".language-selector").find("span")
Array.from(languageSelectors).forEach((selector) => {
	selector.addEventListener('click', function (event) {
		event.preventDefault();
	});
});

// Update translations when language is changed via URL parameter
window.addEventListener('popstate', function () {
	const newLang = urlParams.get('lang') || 'en';
	if (newLang !== lang) {
		lang = newLang;
		updateTranslations();
	}
});
</script>
</body>

</html>
