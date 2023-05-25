<?php
include("functions.php");

$collection = $GLOBALS["mongoClient"]->$GLOBALS["databaseName"]->$GLOBALS["collectionName"];

// Function to retrieve all available fields in the collection
function getAllFields($collection)
{
    $pipeline = [
        ['$project' => ['fields' => ['$objectToArray' => '$$ROOT']]],
        ['$unwind' => '$fields'],
        ['$group' => ['_id' => null, 'fields' => ['$addToSet' => '$fields.k']]]
    ];

    $result = $collection->aggregate($pipeline)->toArray();

    if (isset($result[0]['fields'])) {
        return $result[0]['fields'];
    }

    return [];
}

// Function to retrieve the data type of a field
function getFieldType($collection, $field)
{
    $pipeline = [
        ['$project' => ['valueType' => ['$type' => '$' . $field]]],
        ['$limit' => 1]
    ];

    $result = $collection->aggregate($pipeline)->toArray();

    if (isset($result[0]['valueType'])) {
        switch ($result[0]['valueType']) {
            case 'double':
                return 'float';
            case 'int':
                return 'integer';
            // Add cases for other value types as needed
        }
    }

    return 'string'; // Default data type if not determined
}

// Function to generate JSON structure for jQuery QueryBuilder
function generateQueryBuilderRules($collection)
{
    $fields = getAllFields($collection);
    $jsonStructure = [
        "condition" => "AND",
        "rules" => []
    ];

    foreach ($fields as $field) {
        $fieldType = getFieldType($collection, $field);

        $type = "text";
        $operators = [];

        switch ($fieldType) {
            case 'integer':
            case 'int':
            case 'float':
                $operators = ['equal', 'not_equal', 'less', 'less_or_equal', 'greater', 'greater_or_equal'];
                $type = "number";
                break;
            case 'string':
                $operators = ['equal', 'not_equal', 'contains', 'starts_with', 'ends_with'];
                break;
            // Add additional cases for other data types if needed
        }

        $jsonStructure['rules'][] = [
            "id" => $field,
            "field" => $field,
            "type" => $fieldType,
            "input" => $type,
            "operators" => $operators,
            "value" => ""
        ];
    }

    return json_encode($jsonStructure, JSON_PRETTY_PRINT);
}

// Generate the JSON structure for jQuery QueryBuilder
$jsonStructure = generateQueryBuilderRules($collection);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Search Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jQuery-QueryBuilder/2.4.1/css/query-builder.default.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jQuery-QueryBuilder/2.4.1/js/query-builder.standalone.min.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            var $builder = $('#builder');

            // Initialize the QueryBuilder
            $builder.queryBuilder({
                plugins: ['bt-tooltip-errors'],
                filters: <?php echo $jsonStructure; ?>
            });

            // Function to execute the search query and fetch results
            function executeSearch() {
                var query = $builder.queryBuilder('getRules');
                var queryString = JSON.stringify(query);

                $.ajax({
                    url: 'search.php', // Replace with your server-side script to handle the search query
                    type: 'POST',
                    data: {
                        query: queryString
                    },
                    dataType: 'json',
                    success: function(response) {
                        // Handle the search results here
                        console.log(response);
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                    }
                });
            }

            // Attach the search button click event
            $('#searchButton').click(function() {
                executeSearch();
            });
        });
    </script>
</head>

<body>
    <div id="builder"></div>
    <button id="searchButton">Search</button>
</body>
</html>
