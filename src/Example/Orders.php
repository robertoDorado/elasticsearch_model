<?php

namespace Elasticsearch\Model\Example;

use Elasticsearch\Model\Base\ElasticModel;

/**
 * Products Example
 * @link 
 * @author Roberto Dorado <robertodorado7@gmail.com>
 * @package Elasticsearch\Model\Example
 */
class Orders extends ElasticModel
{
    protected static string $id = 'keyword';

    protected static string $client = 'text';

    protected static string $date = 'date';

    protected static array $products = [
        'type' => 'nested',
        'properties' => [
            'id' => [
                'type' => 'keyword'
            ],
            'name' => [
                'type' => 'text'
            ],
            'quantity' => [
                'type' => 'integer'
            ],
            'price' => [
                'type' => 'float'
            ]
        ]
    ];

    /**
     * Orders constructor
     */
    public function __construct()
    {
        parent::__construct(Orders::class);
    }
}
