<?php
namespace Elasticsearch\Model\Base;

use Exception;

/**
 * ElasticModelException Base
 * @link 
 * @author Roberto Dorado <robertodorado7@gmail.com>
 * @package Elasticsearch\Model
 */
class ElasticModelException extends Exception
{
    /**
     * ElasticModelException constructor
     */
    public function __construct(string $message, $code = 500, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function __toString()
    {
        return "ElasticModelException [{$this->code}]: {$this->message}" . PHP_EOL;
    }
}
