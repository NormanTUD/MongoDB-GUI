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
		"Canada" => "Canada"
	],
	'de' => [
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
		'Australia' => 'Australien'
	],
	'ja' => [
		'name_question' => 'お名前は何ですか？',
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
		'Australia' => 'Australien'
	],
	// Add more language translations here
];

// Function to retrieve language translations
function getTranslation($key, $span = false) {
    global $language;
    $lang = 'en'; // Default language is English

    if (isset($_GET['lang']) && isset($language[$_GET['lang']])) {
        $lang = htmlentities($_GET['lang']);
    }

    if (isset($language[$lang])) {
        if (isset($language[$lang][$key])) {
            if ($span) {
                return '<span class="'.$key.'"></span>';
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
			],
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
		]
	],
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
				'input_type' => 'address',
				'required' => true,
				'name' => 'location',
				'fields' => [
					[
						'label' => 'street_label',
						'name' => 'street'
					],
					[
						'label' => 'city_label',
						'name' => 'city'
					],
					[
						'label' => 'state_label',
						'name' => 'state'
					],
					[
						'label' => 'country_label',
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
];

// Function to validate and process the form submission
function processFormSubmission($questions)
{
    $response = [];
    $errors = [];

    foreach ($questions as $group) {
        foreach ($group['questions'] as $question) {
            $name = $question['name'];
            $value = $_POST[$name] ?? '';

            if (isset($question['required']) && $question['required'] && empty($value)) {
                $errors[] = getTranslation('required_question') . $question['question'];
            }

            if ($question['input_type'] === 'number' && !is_numeric($value)) {
                $errors[] = getTranslation('invalid_response') . $question['question'];
            }

            $response[$name] = $value;
        }
    }

    if (!empty($errors)) {
        // Display error messages
        echo '<h2>Error</h2>';
        echo '<ul>';
        foreach ($errors as $error) {
            echo '<li>' . $error . '</li>';
        }
        echo '</ul>';
    } else {
        // Display form submission data
        echo '<h2>' . getTranslation('form_submission') . '</h2>';
        echo '<ul>';
        foreach ($response as $name => $value) {
            echo '<li><strong>' . $name . '</strong>: ' . $value . '</li>';
        }
        echo '</ul>';
    }
}

// Check if the form is submitted
if (isset($_SERVER["REQUEST_METHOD"]) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    processFormSubmission($questions);
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
	<script>
const language = {
  en: {
    name_question: 'What is your name?',
    age_question: 'How old are you?',
    gender_question: 'Whats your gender?',
    male_option: 'male',
    female_option: 'female',
    other_option: 'other',
    reading_option: 'reading',
    sports_option: 'sports',
    music_option: 'music',
    traveling_option: 'traveling',
    address_question: 'address?',
    country_residence_question: 'country of residence?',
    title: 'Questionnaire',
    submit: 'Submit',
    required_question: 'Required question not answered: ',
    invalid_response: 'Invalid response for question: ',
    h1: 'Questionnaire',
    not_in_browser: 'Not in a browser-like environment. Are you calling this from the CLI?',
    select_option: 'Select an option',
    address_label: 'Address',
    street_label: 'Street',
    city_label: 'City',
    state_label: 'State',
    country_label: 'Country',
    hobbies_question: 'What are your hobbies?',
    personal_information: 'Personal information',
    location_question: 'Location'
  },
  de: {
    name_question: 'Wie ist dein Name?',
    age_question: 'Wie alt bist du?',
    gender_question: 'Was ist dein Geschlecht?',
    male_option: 'männlich',
    female_option: 'weiblich',
    other_option: 'anderes',
    reading_option: 'Lesen',
    sports_option: 'Sport',
    music_option: 'Musik',
    traveling_option: 'Reisen',
    address_question: 'Adresse?',
    country_residence_question: 'Land des Wohnsitzes?',
    title: 'Fragebogen',
    submit: 'Absenden',
    required_question: 'Erforderliche Frage nicht beantwortet: ',
    invalid_response: 'Ungültige Antwort für Frage: ',
    h1: 'Fragebogen',
    not_in_browser: 'Nicht in einer browserähnlichen Umgebung. Rufen Sie dies aus der Befehlszeile auf?',
    select_option: 'Option auswählen',
    address_label: 'Adresse',
    street_label: 'Straße',
    city_label: 'Stadt',
    state_label: 'Bundesland',
    country_label: 'Land',
    hobbies_question: 'Was sind deine Hobbys?',
    personal_information: 'Persönliche Informationen',
    location_question: 'Ort'
  },
  ja: {
    name_question: 'お名前は何ですか？',
    age_question: '年齢はいくつですか？',
    gender_question: '性別は何ですか？',
    male_option: '男性',
    female_option: '女性',
    other_option: 'その他',
    reading_option: '読書',
    sports_option: 'スポーツ',
    music_option: '音楽',
    traveling_option: '旅行',
    address_question: '住所は？',
    country_residence_question: '居住国はどこですか？',
    title: 'アンケート',
    submit: '送信',
    required_question: '必須の質問が回答されていません: ',
    invalid_response: '無効な回答です: ',
    h1: 'アンケート',
    not_in_browser: 'ブラウザのような環境ではありません。CLI から呼び出していますか？',
    select_option: 'オプションを選択',
    address_label: '住所',
    street_label: '番地',
    city_label: '市区町村',
    state_label: '都道府県',
    country_label: '国',
    hobbies_question: '趣味は何ですか？',
    personal_information: '個人情報',
    location_question: '位置'
  }
  // Add more language translations here
};

var lang = 'de';

// Get the language from the query string parameter 'lang'
const urlParams = new URLSearchParams(window.location.search);
const lang = urlParams.get('lang') || 'en'; // Default language is English

// Function to update the translation of elements
function updateTranslations() {
  const elements = document.getElementsByClassName('TRANSLATEME');
  for (const element of elements) {
    const translationKey = element.classList[0];
    const translation = language[lang][translationKey];
    element.textContent = translation;
  }
}

// Update translations on initial page load
updateTranslations();

// Update translations when language selector links are clicked
const languageSelectors = document.getElementsByClassName('language-selector');
for (const selector of languageSelectors) {
  selector.addEventListener('click', function (event) {
    event.preventDefault();
    const selectedLang = this.getAttribute('href').substr(6);
    urlParams.set('lang', selectedLang);
    window.location.search = urlParams.toString();
  });
}

// Update translations when language is changed via URL parameter
window.addEventListener('popstate', function () {
  const newLang = urlParams.get('lang') || 'en';
  if (newLang !== lang) {
    lang = newLang;
    updateTranslations();
  }
});

</script>
    <title><?php echo getTranslation('title'); ?></title>
</head>

<body>
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
                echo '<a href="?lang=' . $lang . '">' . $languageIcons[$lang] . '</a>';
                echo '</span>';
            }
        }
        ?>
    </div>
    <h1><?php echo getTranslation('h1', 1); ?></h1>
<form method="POST" enctype="multipart/form-data">
    <?php foreach ($questions as $group): ?>
        <h2><?php echo getTranslation($group['group'], true); ?></h2>
        <?php foreach ($group['questions'] as $index => $question): ?>
            <?php if ($question['input_type'] === 'text' || $question['input_type'] === 'number'): ?>
                <h3><?php echo getTranslation($question['question'], true); ?></h3>
                <input type="<?php echo $question['input_type']; ?>" name="<?php echo $question['name']; ?>"<?php if ($question['required']) echo ' required'; ?>>
            <?php elseif ($question['input_type'] === 'radio'): ?>
                <h3><?php echo getTranslation($question['question'], true); ?></h3>
                <?php foreach ($question['options'] as $option): ?>
                    <input type="radio" id="<?php echo $question['name']; ?>" name="<?php echo $question['name']; ?>" value="<?php echo $option; ?>"><?php echo getTranslation($option, true); ?>
                <?php endforeach; ?>
            <?php elseif ($question['input_type'] === 'checkbox'): ?>
                <h3><?php echo getTranslation($question['question'], true); ?></h3>
                <?php foreach ($question['options'] as $option): ?>
                    <input type="checkbox" name="<?php echo $question['name']; ?>[]" value="<?php echo $option; ?>"><?php echo getTranslation($option, true); ?>
                <?php endforeach; ?>
            <?php elseif ($question['input_type'] === 'address'): ?>
                <h3><?php echo getTranslation($question['question'], true); ?></h3>
                <?php foreach ($question['fields'] as $field): ?>
                    <label for="<?php echo $question['name'] . '_' . $field['name']; ?>"><?php echo getTranslation($field['label'], true); ?></label>
                    <input type="text" name="<?php echo $question['name'] . '_' . $field['name']; ?>"<?php if ($question['required']) echo ' required'; ?>>
                <?php endforeach; ?>
            <?php elseif ($question['input_type'] === 'select'): ?>
                <h3><?php echo getTranslation($question['question'], true); ?></h3>
                <select name="<?php echo $question['name']; ?>">
                    <option value=""><?php echo getTranslation('select_option', true); ?></option>
                    <?php foreach ($question['options'] as $option): ?>
                        <option value="<?php echo $option; ?>"><?php echo getTranslation($option, true); ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endforeach; ?>
    <button type="submit"><?php echo getTranslation('submit', true); ?></button>
</form>
</body>

</html>
