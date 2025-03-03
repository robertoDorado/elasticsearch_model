<?php

require __DIR__ . '/vendor/autoload.php';

use Elasticsearch\Model\Example\Shakespear;

define("HOST", 'localhost:9200');
$jsonData = file_get_contents("src/json/shakespeare_7.0.json", true);
$jsonData = json_decode($jsonData, true);

$shakespear = new Shakespear();
if ($shakespear->checkIfIndexExists()) {
    $shakespear->deleteIndex();
}

print_r($shakespear->createMapping()->loadDocumentsUsingBulk($jsonData)->searchByMatchPhrase(
    [
        "text_entry" => "And in such indexes, although small pricks"
    ]
));
