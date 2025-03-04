<?php

require __DIR__ . '/vendor/autoload.php';

use Elasticsearch\Model\Example\Shakespear;

define("HOST", 'localhost:9200');
$jsonData = file_get_contents("src/json/shakespeare_7.0.json", true);
$jsonData = json_decode($jsonData, true);

$shakespear = new Shakespear();
$shakespear->updateDocument('1MxzY5UBcX2OZOEQNSTg', [
    'text_entry' => 'And in such indexes, although small pricks'
]);

$shakespear->deleteDocument('1MxzY5UBcX2OZOEQNSTg');
$shakespear->indexDocument('1MxzY5UBcX2OZOEQNSTg', [
    "type" => "line",
    "line_id" => 100001,
    "play_name" => "Troilus and Cressida",
    "speech_number" => 46,
    "line_number" => "1.3.350",
    "speaker" => "NESTOR",
    "text_entry" => "And in such indexes, although small pricks (alterado)"
]);

print_r($shakespear->searchByMatchPhrase(
    [
        "text_entry" => "And in such indexes, although small pricks"
    ]
));
