<?php
// Sample list of questions and input types
$questions = [
    [
        'question' => 'What is your favorite color?',
        'input_type' => 'text',
        'required' => true
    ],
    [
        'question' => 'How old are you?',
        'input_type' => 'number',
        'required' => true
    ],
    [
        'question' => 'Enter your email address:',
        'input_type' => 'email',
        'required' => true
    ],
    [
        'question' => 'Select your hobbies:',
        'input_type' => 'checkbox',
        'options' => [
            'Reading',
            'Sports',
            'Music',
            'Traveling'
        ]
    ],
    [
        'question' => 'Enter your coordinates:',
        'input_type' => 'geocoordinates',
        'required' => true,
        'fields' => [
            [
                'label' => 'Latitude',
                'name' => 'latitude'
            ],
            [
                'label' => 'Longitude',
                'name' => 'longitude'
            ]
        ]
    ],
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
    // Add more questions here...
];

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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Prepare an array to store the user's responses
    $userResponses = [];
    $isValid = true;

    // Iterate through each question and retrieve the user's response
    foreach ($questions as $index => $question) {
        $inputName = 'response_' . $index;

        // Handle different input types
        switch ($question['input_type']) {
            case 'geocoordinates':
                $response = [];
                foreach ($question['fields'] as $field) {
                    $fieldName = $inputName . '_' . $field['name'];
                    $response[$field['name']] = $_POST[$fieldName];
                }

                // Validate the longitude value
                if (!isValidLongitude($response['longitude'])) {
                    $isValid = false;
                    echo 'Invalid longitude value for question: ' . $question['question'] . '<br>';
                }

                if (!isValidLongitude($response['latitude'])) {
                    $isValid = false;
                    echo 'Invalid latitude value for question: ' . $question['question'] . '<br>';
                }

                break;

            case 'address':
                $response = [];
                foreach ($question['fields'] as $field) {
                    $fieldName = $inputName . '_' . $field['name'];
                    $response[$field['name']] = $_POST[$fieldName];
                }
                break;

            case 'file':
                $response = $_FILES[$inputName]['name'];
                $uploadDir = 'uploads/';
                $uploadFile = $uploadDir . basename($_FILES[$inputName]['name']);
                move_uploaded_file($_FILES[$inputName]['tmp_name'], $uploadFile);
                break;

            default:
                $response = isset($_POST[$inputName]) ? $_POST[$inputName] : '';

                // Validate if the field is required and empty
                if ($question['required'] && empty($response)) {
                    $isValid = false;
                    echo 'Required question not answered: ' . $question['question'] . '<br>';
                }
        }

        // Store the user's response in the array
        $userResponses[$question['question']] = $response;
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
        foreach ($questions as $index => $question) {
            echo '<h3>' . $question['question'] . '</h3>';

            if ($question['input_type'] === 'geocoordinates' || $question['input_type'] === 'address') {
                foreach ($question['fields'] as $field) {
                    $fieldName = $inputName . '_' . $field['name'];
                    echo '<label>' . $field['label'] . ':</label>';
                    echo '<input type="text" name="response_' . $index . '_' . $field['name'] . '"><br>';
                }
            } elseif ($question['input_type'] === 'checkbox') {
                foreach ($question['options'] as $option) {
                    echo '<label>';
                    echo '<input type="checkbox" name="response_' . $index . '[]" value="' . $option . '"> ' . $option;
                    echo '</label><br>';
                }
            } elseif ($question['input_type'] === 'file') {
                echo '<input type="file" name="response_' . $index . '"><br>';
            } else {
                echo '<input type="' . $question['input_type'] . '" name="response_' . $index . '"><br>';
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
    <?php if ($_SERVER['REQUEST_METHOD'] === 'GET'): ?>
        <h1>Questionnaire</h1>
        <form method="POST" enctype="multipart/form-data">
            <?php foreach ($questions as $index => $question): ?>
                <h3><?php echo $question['question']; ?></h3>

                <?php if ($question['input_type'] === 'geocoordinates' || $question['input_type'] === 'address'): ?>
                    <?php foreach ($question['fields'] as $field): ?>
                        <label><?php echo $field['label']; ?>:
                            <input type="text" name="response_<?php echo $index; ?>_<?php echo $field['name']; ?>">
                        </label><br>
                    <?php endforeach; ?>
                <?php elseif ($question['input_type'] === 'checkbox'): ?>
                    <?php foreach ($question['options'] as $option): ?>
                        <label>
                            <input type="checkbox" name="response_<?php echo $index; ?>[]" value="<?php echo $option; ?>"> <?php echo $option; ?>
                        </label><br>
                    <?php endforeach; ?>
                <?php elseif ($question['input_type'] === 'file'): ?>
                    <input type="file" name="response_<?php echo $index; ?>"><br>
                <?php else: ?>
                    <input type="<?php echo $question['input_type']; ?>" name="response_<?php echo $index; ?>"><br>
                <?php endif; ?>
            <?php endforeach; ?>

            <br>
            <button type="submit">Send</button>
        </form>
    <?php endif; ?>
</body>
</html>

