<?php
include("functions.php");
?>

<h3><?php echo $GLOBALS["databaseName"] . "." . $GLOBALS["collectionName"]; ?> on <?php echo $GLOBALS["mongodbHost"] . ":" . $GLOBALS["mongodbPort"]; ?></h3>

<?php
// Function to recursively extract fields from nested documents
function extractFields($document, $parentField = '', &$fields = []) {
    foreach ($document as $key => $value) {
        $field = $parentField ? $parentField . '.' . $key : $key;
        $fields[] = $field;

        if (is_array($value) && !empty($value)) {
            extractFields($value, $field, $fields);
        }
    }
}

// Function to determine the data type of a field
function determineFieldType($document, $field) {
    $fieldParts = explode('.', $field);
    $value = $document;

    foreach ($fieldParts as $part) {
        if (isset($value[$part])) {
            $value = $value[$part];
        } else {
            return 'string';
        }
    }

    if (is_array($value)) {
        return 'string'; // Assume arrays are stored as strings
    } elseif (is_numeric($value)) {
        return 'double'; // Numeric values can be treated as doubles
    } elseif (is_bool($value)) {
        return 'boolean';
    } else {
        return 'string';
    }
}

// Retrieve the list of fields from the database
$query = new MongoDB\Driver\Query([], ['projection' => ['_id' => 0]]);
$cursor = $GLOBALS["mongoClient"]->executeQuery($GLOBALS["namespace"], $query);

$fields = [];
foreach ($cursor as $document) {
    extractFields((array)$document, '', $fields);
}

// Generate options and filters
$options = [];
$filters = [];

foreach ($fields as $field) {
    $options[] = [
        'id' => $field,
        'label' => $field,
        'type' => determineFieldType((array)$document, $field)
    ];

    $fieldParts = explode('.', $field);
    $lastField = end($fieldParts);

    $operators = ['equal', 'not_equal'];
    $fieldType = determineFieldType((array)$document, $field);
    if ($fieldType === 'double' || $fieldType === 'boolean') {
        $operators[] = 'less';
        $operators[] = 'less_or_equal';
        $operators[] = 'greater';
        $operators[] = 'greater_or_equal';
    } elseif ($fieldType === 'string') {
        $operators[] = 'contains'; // Add 'contains' for string fields
    }

    $filters[] = [
        'id' => $field,
        'label' => $field,
        'type' => $fieldType,
        'operators' => $operators,
        'input' => $fieldType === 'string' ? 'text' : 'number',
        'field' => $lastField
    ];
}

// Output the generated options and filters
print "<pre>";
print_r($options);
print_r($filters);
?>

