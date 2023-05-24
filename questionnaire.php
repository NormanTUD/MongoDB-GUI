<?php
	// Sample list of questions and input types
	$questions = [
	    [
		'group' => 'Personal Information',
		'questions' => [
		    [
			'question' => 'What is your name?',
			'input_type' => 'text',
			'required' => true,
			'name' => 'your_name'
		    ],
		    [
			'question' => 'How old are you?',
			'input_type' => 'number',
			'required' => true,
			'name' => 'age'
		    ],
		    [
			'question' => 'Select your gender:',
			'input_type' => 'radio',
			'name' => 'gender',
			'options' => [
			    'Male',
			    'Female',
			    'Other'
			]
		    ]
		]
	    ],
	    [
		'group' => 'Hobbies',
		'questions' => [
		    [
			'question' => 'Select your hobbies:',
			'input_type' => 'checkbox',
			'name' => 'hobbies',
			'options' => [
			    'Reading',
			    'Sports',
			    'Music',
			    'Traveling'
			]
		    ]
		]
	    ],
	    [
		'group' => 'Location',
		'questions' => [
		    [
			'question' => 'Enter your address:',
			'input_type' => 'address',
			'required' => true,
			'fields' => [
			    [
				'label' => 'Street',
				'name' => 'street'
			    ],
			    [
				'label' => 'City',
				'name' => 'city'
			    ],
			    [
				'label' => 'State',
				'name' => 'state'
			    ],
			    [
				'label' => 'Country',
				'name' => 'country'
			    ]
			]
		    ],
		    [
			'question' => 'Select your country of residence:',
			'input_type' => 'select',
			'name' => 'country',
			'options' => [
			    'USA',
			    'Canada',
			    'UK',
			    'Australia',
			    'Other'
			]
		    ]
		]
	    ],
	    // Add more question groups here...
	];

	// Function to check the completeness and plausibility of the questions array
	function checkQuestionsArray($questions) {
		foreach ($questions as $group) {
			if (!isset($group['group'])) {
				return false;
			}

			if (!isset($group['questions']) || !is_array($group['questions']) || empty($group['questions'])) {
				return false;
			}

			foreach ($group['questions'] as $question) {
				if (!isset($question['question']) || !isset($question['input_type'])) {
					return false;
				}

				if (!isset($question["name"]) && !isset($question["fields"])) {
					return false;
				}

				if ($question['input_type'] === 'radio' || $question['input_type'] === 'checkbox') {
					if (!isset($question['options']) || !is_array($question['options']) || empty($question['options'])) {
						return false;
					}
				}

				if ($question['input_type'] === 'address') {
					if (!isset($question['fields']) || !is_array($question['fields']) || empty($question['fields'])) {
						return false;
					}

					foreach ($question['fields'] as $field) {
						if (!isset($field['label']) || !isset($field['name'])) {
							return false;
						}
					}
				}
			}
		}

		return true;
	}

	// Check the completeness and plausibility of the questions array
	if (!checkQuestionsArray($questions)) {
		echo 'Invalid or incomplete questions array.';
		exit;
	}

	// Function to check if a longitude value is valid
	function isValidLongitude($longitude) {
	    // Check if the longitude is a numeric value
	    if (!is_numeric($longitude)) {
		return false;
	    }

	    // Check if the longitude is within a valid range (-180 to 180 degrees)
	    if ($longitude < -180 || $longitude > 180) {
		return false;
	    }

	    return true;
	}

	// Check if the form is submitted
	if (isset($_SERVER["REQUEST_METHOD"]) && $_SERVER['REQUEST_METHOD'] === 'POST') {
	    // Prepare an array to store the user's responses
	    $userResponses = [];
	    $isValid = true;

	    // Iterate through each question group and retrieve the user's responses
	    foreach ($questions as $group) {
		foreach ($group['questions'] as $index => $question) {
		    $inputName = 'response_group_' . $index;

		    // Handle different input types
		    switch ($question['input_type']) {
			case 'address':
			    $response = [];
			    foreach ($question['fields'] as $field) {
				$fieldName = $inputName . '_' . $field['name'];
				$response[$field['name']] = $_POST[$fieldName];
			    }
			    break;

			case 'radio':
			case 'checkbox':
			    $response = isset($_POST[$inputName]) ? $_POST[$inputName] : [];
			    break;

			default:
			    $response = isset($_POST[$inputName]) ? $_POST[$inputName] : '';

			    // Validate if the field is required and empty
			    if (isset($question['required']) && $question['required'] && empty($response)) {
				$isValid = false;
				echo 'Required question not answered: ' . $question['question'] . '<br>';
			    }
		    }

		    // Store the user's response in the array
		    $userResponses[$group['group']][$question['question']] = $response;
		}
	    }

	    if ($isValid) {
		// Convert the user's responses to JSON
		$json = json_encode($userResponses, JSON_PRETTY_PRINT);

		// Display the JSON to the user
		echo '<pre>' . $json . '</pre>';
	    } else {
		// Display the form again with error messages
		echo '<h1>Questionnaire</h1>';
		echo '<form method="POST" enctype="multipart/form-data">';
		foreach ($questions as $group) {
		    echo '<h2>' . $group['group'] . '</h2>';
		    foreach ($group['questions'] as $index => $question) {
			echo '<h3>' . $question['question'] . '</h3>';

			$inputName = 'response_group_' . $index;

			// Handle different input types
			switch ($question['input_type']) {
			    case 'address':
				foreach ($question['fields'] as $field) {
				    $fieldName = $inputName . '_' . $field['name'];
				    echo '<label>' . $field['label'] . ':</label>';
				    echo '<input type="text" name="' . $fieldName . '"><br>';
				}
				break;

			    case 'radio':
				foreach ($question['options'] as $option) {
				    echo '<label>';
				    echo '	<input type="radio" name="' . $inputName . '" value="' . $option . '"> ' . $option;
				    echo '</label><br>';
				}
				break;

			    case 'checkbox':
				foreach ($question['options'] as $option) {
				    echo '<label>';
				    echo '	<input type="checkbox" name="' . $inputName . '[]" value="' . $option . '"> ' . $option;
				    echo '</label><br>';
				}
				break;

			    default:
				echo '<input type="' . $question['input_type'] . '" name="' . $inputName . '"><br>';
			}
		    }
		}

		echo '<br>';
		echo '<button type="submit">Send</button>';
		echo '</form>';
	    }

	    exit;
	}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Questionnaire</title>
</head>
<body>
	<?php if (isset($_SERVER["REQUEST_METHOD"]) && $_SERVER['REQUEST_METHOD'] === 'GET'): ?>
		<h1>Questionnaire</h1>
		<form method="POST" enctype="multipart/form-data">
			<?php foreach ($questions as $group): ?>
				<h2><?php echo $group['group']; ?></h2>
				<?php foreach ($group['questions'] as $index => $question): ?>
				<h3><?php echo $question['question']; ?></h3>

				<?php $inputName = 'response_group_' . $index; ?>

				<?php if ($question['input_type'] === 'address'): ?>
					<?php foreach ($question['fields'] as $field): ?>
						<?php $fieldName = $inputName . '_' . $field['name']; ?>
						<label><?php echo $field['label']; ?>:
						<input type="text" name="<?php echo $fieldName; ?>">
						</label><br>
					<?php endforeach; ?>
				<?php elseif ($question['input_type'] === 'select'): ?>
					<select name='<?php print $question["name"]; ?>'>
						<?php foreach ($question['options'] as $option): ?>
						<option value="<?php echo $option; ?>"> <?php echo $option; ?></option>
						<?php endforeach; ?>
					</select>
				<?php elseif ($question['input_type'] === 'radio'): ?>
					<?php foreach ($question['options'] as $option): ?>
						<label>
						<input type="radio" name="<?php echo $inputName; ?>" value="<?php echo $option; ?>"> <?php echo $option; ?>
						</label><br>
					<?php endforeach; ?>
				<?php elseif ($question['input_type'] === 'checkbox'): ?>
					<?php foreach ($question['options'] as $option): ?>
						<label>
						<input type="checkbox" name="<?php echo $inputName; ?>[]" value="<?php echo $option; ?>"> <?php echo $option; ?>
						</label><br>
					<?php endforeach; ?>
					<?php else: ?>
						<input type="<?php echo $question['input_type']; ?>" name="<?php echo $inputName; ?>"><br>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php endforeach; ?>
		<button type="submit">Send</button>
		</form>
	<?php endif; ?>
</body>
</html>
