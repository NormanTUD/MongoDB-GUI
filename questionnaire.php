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
	'hobbies_question' => 'What are your hobbies?'
    ],
    // Add more language translations here
];

// Function to retrieve language translations
function getTranslation($key) {
	global $language;
	$lang = 'en'; // Default language is English
	if (isset($_GET['lang']) && isset($language[$_GET['lang']])) {
		$lang = $_GET['lang'];
	}

	if(isset($language[$lang])) {
		if(isset($language[$lang][$key])) {
			return $language[$lang][$key]; // Return the translation or the key itself if not found
		} else {
			die("Unknown language key: ".htmlentities($key));
		}
	} else {
		die("Unknown language shortcut: ".htmlentities($lang));
	}
}

// Sample list of questions and input types
$questions = [
	[
		'group' => 'Personal Information',
		'questions' => [
			[
				'question' => getTranslation('name_question'),
				'input_type' => 'text',
				'required' => true,
				'name' => 'your_name'
			],
			[
				'question' => getTranslation('age_question'),
				'input_type' => 'number',
				'required' => true,
				'name' => 'age'
            ],
            [
                'question' => getTranslation('gender_question'),
                'input_type' => 'radio',
                'name' => 'gender',
                'options' => [
                    getTranslation('male_option'),
                    getTranslation('female_option'),
                    getTranslation('other_option')
                ]
            ]
        ]
    ],
    [
        'group' => 'Hobbies',
        'questions' => [
            [
                'question' => getTranslation('hobbies_question'),
                'input_type' => 'checkbox',
                'name' => 'hobbies',
                'options' => [
                    getTranslation('reading_option'),
                    getTranslation('sports_option'),
                    getTranslation('music_option'),
                    getTranslation('traveling_option')
                ]
            ]
        ]
    ],
    [
        'group' => 'Location',
        'questions' => [
            [
                'question' => getTranslation('address_question'),
                'input_type' => 'address',
                'required' => true,
		'name' => 'location',
                'fields' => [
                    [
                        'label' => getTranslation('street_label'),
                        'name' => 'street'
                    ],
                    [
                        'label' => getTranslation('city_label'),
                        'name' => 'city'
                    ],
                    [
                        'label' => getTranslation('state_label'),
                        'name' => 'state'
                    ],
                    [
                        'label' => getTranslation('country_label'),
                        'name' => 'country'
                    ]
                ]
            ],
            [
                'question' => getTranslation('country_residence_question'),
                'input_type' => 'select',
                'name' => 'country',
                'options' => [
                    'USA',
                    'Canada',
                    'UK',
                    'Australia',
                    getTranslation('other_option')
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

            if ($question['required'] && empty($value)) {
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
    <title><?php echo getTranslation('title'); ?></title>
</head>

<body>
    <h1><?php echo getTranslation('h1'); ?></h1>
    <form method="POST" enctype="multipart/form-data">
        <?php foreach ($questions as $group): ?>
            <h2><?php echo $group['group']; ?></h2>
            <?php foreach ($group['questions'] as $index => $question): ?>
                <?php if ($question['input_type'] === 'text' || $question['input_type'] === 'number'): ?>
                    <h3><?php echo $question['question']; ?></h3>
                    <input type="<?php echo $question['input_type']; ?>" name="<?php echo $question['name']; ?>"<?php if ($question['required']) echo ' required'; ?>>
                <?php elseif ($question['input_type'] === 'radio'): ?>
                    <h3><?php echo $question['question']; ?></h3>
                    <?php foreach ($question['options'] as $option): ?>
                        <input type="radio" name="<?php echo $question['name']; ?>" value="<?php echo $option; ?>"><?php echo $option; ?>
                    <?php endforeach; ?>
                <?php elseif ($question['input_type'] === 'checkbox'): ?>
                    <h3><?php echo $question['question']; ?></h3>
                    <?php foreach ($question['options'] as $option): ?>
                        <input type="checkbox" name="<?php echo $question['name']; ?>[]" value="<?php echo $option; ?>"><?php echo $option; ?>
                    <?php endforeach; ?>
                <?php elseif ($question['input_type'] === 'address'): ?>
                    <h3><?php echo $question['question']; ?></h3>
                    <?php foreach ($question['fields'] as $field): ?>
                        <label for="<?php echo $question['name'] . '_' . $field['name']; ?>"><?php echo $field['label']; ?></label>
                        <input type="text" name="<?php echo $question['name'] . '_' . $field['name']; ?>"<?php if ($question['required']) echo ' required'; ?>>
                    <?php endforeach; ?>
                <?php elseif ($question['input_type'] === 'select'): ?>
                    <h3><?php echo $question['question']; ?></h3>
                    <select name="<?php echo $question['name']; ?>">
                        <option value=""><?php echo getTranslation('select_option'); ?></option>
                        <?php foreach ($question['options'] as $option): ?>
                            <option value="<?php echo $option; ?>"><?php echo $option; ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <button type="submit"><?php echo getTranslation('submit'); ?></button>
    </form>
</body>

</html>
