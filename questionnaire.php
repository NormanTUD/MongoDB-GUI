<?php
define('INCLUDED_FROM_INDEX', true);
include_once("functions.php");
// Language file containing translations
$language = [
	'en' => [
		'name_question' => 'What is your name?',
		'age_question' => 'How old are you?',
		'gender_question' => 'Whats your gender?',
		'male_option' => 'male',
		'female_option' => 'female',
		'other_option' => 'other',
		'reading_option' => 'reading',
		'sports_option' => 'sports',
		'music_option' => 'music',
		'traveling_option' => 'traveling',
		'address_question' => 'address?',
		'country_residence_question' => 'country of residence?',
		'title' => 'Questionnaire',
		'submit' => 'Submit',
		'required_question' => 'Required question not answered: ',
		'invalid_response' => 'Invalid response for question: ',
		'h1' => 'Questionnaire',
		'not_in_browser' => 'Not in a browser-like environment. Are you calling this from the CLI?',
		'select_option' => 'Select an option',
		'address_label' => 'Address',
		'street_label' => 'Street',
		'city_label' => 'City',
		'state_label' => 'State',
		'country_label' => 'Country',
		'hobbies_question' => 'What are your hobbies?',
		'personal_information' => 'Personal information',
		'location_question' => 'Location',
		'USA' => 'USA',
		'UK' => 'UK',
		'Australia' => 'Australia',
		"form_submission" => "Sent data",
		"Canada" => "Canada",
		"errors" => "Errors"
	],
	'de' => [
		"form_submission" => "Gesendete Daten",
		'name_question' => 'Wie ist dein Name?',
		'age_question' => 'Wie alt bist du?',
		'gender_question' => 'Was ist dein Geschlecht?',
		'male_option' => 'männlich',
		'female_option' => 'weiblich',
		'other_option' => 'anderes',
		'reading_option' => 'Lesen',
		'sports_option' => 'Sport',
		'music_option' => 'Musik',
		'traveling_option' => 'Reisen',
		'address_question' => 'Adresse?',
		'country_residence_question' => 'Land des Wohnsitzes?',
		'title' => 'Fragebogen',
		'submit' => 'Absenden',
		'required_question' => 'Erforderliche Frage nicht beantwortet: ',
		'invalid_response' => 'Ungültige Antwort für Frage: ',
		'h1' => 'Fragebogen',
		'not_in_browser' => 'Nicht in einer browserähnlichen Umgebung. Rufen Sie dies aus der Befehlszeile auf?',
		'select_option' => 'Option auswählen',
		'address_label' => 'Adresse',
		'street_label' => 'Straße',
		'city_label' => 'Stadt',
		'state_label' => 'Bundesland',
		'country_label' => 'Land',
		'hobbies_question' => 'Was sind deine Hobbys?',
		'personal_information' => 'Persönliche Informationen',
		'location_question' => 'Ort',
		'USA' => 'USA',
		'UK' => 'UK',
		"Canada" => "Canada",
		'Australia' => 'Australien',
		"errors" => "Fehler"
	],
	'ja' => [
		'name_question' => 'お名前は何ですか？',
		"form_submission" => "FORM_SUBMISSION",
		'age_question' => '年齢はいくつですか？',
		'gender_question' => '性別は何ですか？',
		'male_option' => '男性',
		'female_option' => '女性',
		'other_option' => 'その他',
		'reading_option' => '読書',
		'sports_option' => 'スポーツ',
		'music_option' => '音楽',
		'traveling_option' => '旅行',
		'address_question' => '住所は？',
		'country_residence_question' => '居住国はどこですか？',
		'title' => 'アンケート',
		'submit' => '送信',
		'required_question' => '必須の質問が回答されていません: ',
		'invalid_response' => '無効な回答です: ',
		'h1' => 'アンケート',
		'not_in_browser' => 'ブラウザのような環境ではありません。CLI から呼び出していますか？',
		'select_option' => 'オプションを選択',
		'address_label' => '住所',
		'street_label' => '番地',
		'city_label' => '市区町村',
		'state_label' => '都道府県',
		'country_label' => '国',
		'hobbies_question' => '趣味は何ですか？',
		'personal_information' => '個人情報',
		'location_question' => '位置',
		'USA' => 'USA',
		'UK' => 'UK',
		'Canada' => 'Canada',
		'Australia' => 'Australia',
		"errors" => "Fehler"
	],
	// Add more language translations here
];

// Function to retrieve language translations
function getTranslation($key, $span = false, $option = false) {
    global $language;
    $lang = 'en'; // Default language is English

    if (isset($_GET['lang']) && isset($language[$_GET['lang']])) {
        $lang = htmlentities($_GET['lang']);
    }

    if (isset($language[$lang])) {
        if (isset($language[$lang][$key])) {
            if ($span) {
		    if($option) {
			    return '<option class="TRANSLATEME_'.$key.'">'.$key.'</option>';
		    } else {
			    return '<span value="'.$key.'" class="TRANSLATEME_'.$key.'">'.$key.'</span>';
		    }

            } else {
                return $language[$lang][$key];
            }
        } else {
            throw new Exception("Unknown language key: \$language[$lang][" . htmlentities($key) . "]");
        }
    } else {
        throw new Exception("Unknown language shortcut: " . htmlentities($lang));
    }
}

// Sample list of questions and input types
$questions = [
	[
		'group' => 'personal_information',
		'questions' => [
			[
				'question' => 'name_question',
				'input_type' => 'text',
				'required' => true,
				'name' => 'your_name'
			],
			[
				'question' => 'age_question',
				'input_type' => 'number',
				'required' => true,
				'name' => 'age'
			]
			/*,
			[
				'question' => 'gender_question',
				'input_type' => 'radio',
				'name' => 'gender',
				'options' => [
					'male_option',
					'female_option',
					'other_option'
				]
			]
			 */
		]
	]/*,
	[
		'group' => 'hobbies_question',
		'questions' => [
			[
				'question' => 'hobbies_question',
				'input_type' => 'checkbox',
				'name' => 'hobbies',
				'options' => [
					'reading_option',
					'sports_option',
					'music_option',
					'traveling_option'
				]
			]
		]
	],
	[
		'group' => 'location_question',
		'questions' => [
			[
				'question' => 'address_question',
				'input_type' => 'text',
				'name' => 'location',
				'fields' => [
					[
						'label' => 'street_label',
						'required' => true,
						'name' => 'street'
					],
					[
						'label' => 'city_label',
						'required' => true,
						'name' => 'city'
					],
					[
						'label' => 'state_label',
						'required' => true,
						'name' => 'state'
					],
					[
						'label' => 'country_label',
						'required' => true,
						'name' => 'country'
					]
				]
			],
			[
				'question' => 'country_residence_question',
				'input_type' => 'select',
				'name' => 'country',
				'options' => [
					'USA',
					'Canada',
					'UK',
					'Australia',
					'other_option'
				]
			]
		]
	]
	 */
];


function generateFormFields($questions) {
    $html = '';
    
    foreach ($questions as $group) {
        $html .= '<h2>' . getTranslation($group['group'], true) . '</h2>';
        
        foreach ($group['questions'] as $index => $question) {
            $html .= generateFormField($question);
        }
    }
    
    return $html;
}

function generateFormField($question) {
	$html = '';

	if ($question['input_type'] === 'text' || $question['input_type'] === 'number') {
		$html .= '<h3>' . getTranslation($question['question'], true) . '</h3>';
		if(isset($question["fields"]) && is_array($question["fields"])) {
			foreach ($question['fields'] as $field) {
				$html .= '<label for="' . $question['name'] . '_' . $field['name'] . '">' . getTranslation($field['label'], true) . '</label>';
				$html .= '<input type="text" name="' . $question['name'] . '_' . $field['name'] . '"';
				if (isset($field["required"]) && $field['required']) {
					$html .= ' required';
				}
				$html .= '>';
			}
		} else {
			$html .= '<input type="' . $question['input_type'] . '" name="' . $question['name'] . '"';
			if (isset($question["required"]) && $question['required']) {
				$html .= ' required';
			}
			$html .= '>';
		}
	} elseif ($question['input_type'] === 'radio') {
		$html .= '<h3>' . getTranslation($question['question'], true) . '</h3>';
		foreach ($question['options'] as $option) {
			$html .= '<input type="radio" id="' . $question['name'] . '" name="' . $question['name'] . '" value="' . $option . '">' . getTranslation($option, true);
		}
	} elseif ($question['input_type'] === 'checkbox') {
		$html .= '<h3>' . getTranslation($question['question'], true) . '</h3>';
		foreach ($question['options'] as $option) {
			$html .= '<input type="checkbox" name="' . $question['name'] . '[]" value="' . $option . '">' . getTranslation($option, true);
		}
	} elseif ($question['input_type'] === 'select') {
		$html .= '<h3>' . getTranslation($question['question'], true) . '</h3>';
		$html .= '<select name="' . $question['name'] . '">';
		$html .= getTranslation('select_option', true, true);
		foreach ($question['options'] as $option) {
			$html .= getTranslation($option, true, true);
		}
		$html .= '</select>';
	}

	return $html;
}

// Function to validate and process the form submission
function processFormSubmission($questions) {
	$html = '';

	$response = [];
	$errors = [];

	foreach ($questions as $group) {
		foreach ($group['questions'] as $question) {
			$name = $question['name'];
			if(isset($question["fields"])) {
				$base_name = $question["name"];
				foreach ($question["fields"] as $tq_name) {
					$key = $base_name.'_'.$tq_name["name"];
					$tq_value = $_POST[$key] ?? '';

					if (isset($question['required']) && $question['required'] && empty($tq_value)) {
						$errors[] = getTranslation('required_question', true) . getTranslation($question['question'], true);
					}

					if ($question['input_type'] === 'number' && !is_numeric($tq_value)) {
						$errors[] = getTranslation('invalid_response', true) . getTranslation($question['question'], true);
					}

					$response[$key] = $tq_value;
				}
			} else {
				$value = $_POST[$name] ?? '';

				if (isset($question['required']) && $question['required'] && empty($value)) {
					$errors[] = getTranslation('required_question', true) . getTranslation($question['question'], true);
				}

				if ($question['input_type'] === 'number' && !is_numeric($value)) {
					$errors[] = getTranslation('invalid_response', true) . getTranslation($question['question'], true);
				}

				$response[$name] = $value;
			}
		}
	}

	if (!empty($errors)) {
		// Display error messages
		$html .= '<h2>'.getTranslation("errors", true).'</h2>';
		$html .= '<ul>';
		foreach ($errors as $error) {
			$html .= '<li>' . $error . '</li>';
		}
		$html .= '</ul>';
	} else {
		// Display form submission data
		$html .= '<h2>' . getTranslation('form_submission', true) . '</h2>';
		$html .= '<ul>';
		foreach ($response as $name => $value) {
			if(is_array($value)) {
				$html .= '<li><strong>' . $name . '</strong>: ' . join(', ', $value) . '</li>';
			} else {
				$html .= '<li><strong>' . $name . '</strong>: ' . $value . '</li>';
			}
		}
		$html .= '</ul>';
	}

	return ["html" => $html, "json" => json_encode($response), "errors" => $errors];
}

// Check if the form is submitted
if (isset($_SERVER["REQUEST_METHOD"]) && $_SERVER['REQUEST_METHOD'] === 'POST') {
	print json_encode(processFormSubmission($questions));
	exit();
}
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

			// Collect form data
			/*
			var formData = new FormData(document.getElementById('myForm'));
			log(formData);
			 */

			// Send form data to server

			fetch('questionnaire.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					new_entry_data: getFormData($("#myForm"))
				})
			})
			.then(response => response.text())
			.then(data => {
				// Handle the server response
				var resultContainer = document.getElementById('resultContainer');
				try {
					var d = JSON.parse(data);
					resultContainer.innerHTML = d.html;

					try {
						var json = JSON.parse(d.json);
					} catch (e) {
						console.error(e);
					}
				} catch (e) {
					console.error(e);
				}
				updateTranslations();
			})
			.catch(error => {
				console.error('Error:', error);
				updateTranslations();
			});
		}
	</script>
    <title><?php echo getTranslation('title'); ?></title>
</head>

<body>
<div id="resultContainer"></div>
    <div>
        <?php
        $languageIcons = [
            'en' => '🇺🇸', // English
            'de' => '🇩🇪', // German
            'ja' => '🇯🇵', // Japanese
            // Add more language icons here
        ];

        foreach ($language as $lang => $translations) {
            if (isset($languageIcons[$lang])) {
                echo '<span class="language-selector">';
		echo '<span onclick="setLang(\''.htmlentities($lang).'\')">' . $languageIcons[$lang] . '</span>';
                echo '</span>';
            }
        }
        ?>
    </div>
    <h1><?php echo getTranslation('h1', 1); ?></h1>
<form method="POST" enctype="multipart/form-data" id='myForm'>
    <?php echo generateFormFields($questions); ?>
    <br>
    <br>
    <button onclick='submitForm(event);' type="submit"><?php echo getTranslation('submit', true); ?></button>
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
