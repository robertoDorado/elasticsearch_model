<?php
namespace Elasticsearch\Model\Boot;

use Elastic\Elasticsearch\ClientBuilder;

/**
 * Connection Model
 * @link 
 * @author Roberto Dorado <robertodorado7@gmail.com>
 * @package Elasticsearch\Model
 */
class Connection
{
    private static $client = null;

    public static function instance() {
        if (empty(self::$client)) {
            self::$client = ClientBuilder::create()
                ->setHosts([HOST])
                ->build();
        }
        return self::$client;
    }
}
