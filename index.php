<?php

require __DIR__ . '/vendor/autoload.php';

use Elasticsearch\Model\Example\Orders;

define("HOST", 'localhost:9200');
$orders = new Orders();
$orders->indexDocument(uniqid(), [
    "id" => uniqid(),
    "client" => "João",
    "date" => (new DateTime())->format('c'),
    "products" => [
        [
            "id" => uniqid(),
            "name" => "Celular",
            "quantity" => 2,
            "price" => 1256.77
        ],
        [
            "id" => uniqid(),
            "name" => "Máquina de lavar",
            "quantity" => 1,
            "price" => 785.66
        ],
    ]
]);
