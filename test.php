<?php
include("functions.php");
?>

<h3><?php echo $GLOBALS["databaseName"] . "." . $GLOBALS["collectionName"]; ?> on <?php echo $GLOBALS["mongodbHost"] . ":" . $GLOBALS["mongodbPort"]; ?></h3>

<?php
// Retrieve the list of fields from the database
$query = new MongoDB\Driver\Query([], ['projection' => ['_id' => 0]]);
$cursor = $GLOBALS["mongoClient"]->executeQuery($GLOBALS["namespace"], $query);

$fields = [];
$documents = [];
foreach ($cursor as $document) {
    $documentArray = json_decode(json_encode($document), true);
    $documents[] = $documentArray;
}

// Generate filters and options for jQuery QueryBuilder
$filters = [];
$options = [];

foreach ($documents as $document) {
    traverseDocument($document, '', $filters, $options);
}

// Output the filters and options in JSON format
$output = [
    'filters' => $filters,
    'options' => $options,
];

echo json_encode($output);

function traverseDocument($data, $prefix, &$filters, &$options) {
    foreach ($data as $key => $value) {
        $path = $prefix . $key;
        $type = getDataType($value);

        $filter = [
            'id' => $path,
            'label' => $path,
            'type' => $type,
        ];

        $option = [
            'id' => $path,
            'label' => $path,
        ];

        if ($type === 'string') {
            $filter['operators'] = ['equal', 'contains'];
        } elseif ($type === 'number') {
            $filter['operators'] = ['equal', 'greater', 'less'];
        }

        $filters[] = $filter;
        $options[] = $option;

        if (is_array($value)) {
            traverseDocument($value, $path . '.', $filters, $options);
        }
    }
}

function getDataType($value) {
    if (is_numeric($value)) {
        return 'number';
    } elseif (is_string($value)) {
        return 'string';
    } else {
        return 'string'; // Default to string if data type cannot be determined
    }
}
?>

