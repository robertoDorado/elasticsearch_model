<?php
namespace Elasticsearch\Model\Example;

use Elasticsearch\Model\Base\ElasticModel;

/**
 * Shakespear Example
 * @link 
 * @author Roberto Dorado <robertodorado7@gmail.com>
 * @package Elasticsearch\Model\Example
 */
class Shakespear extends ElasticModel
{
    protected static string $speaker = "keyword";

    protected static string $playName = "keyword";

    protected static string $lineId = "integer";

    protected static string $speechNumber = "integer";

    /**
     * Shakespear constructor
     */
    public function __construct()
    {
        parent::__construct(Shakespear::class);
    }
}
