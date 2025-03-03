<?php

namespace Elasticsearch\Model\Interfaces;

/**
 * ElasticInterface Interfaces
 * @link public 
 * @author Roberto Dorado <robertodorado7@gmail.com>
 * @package Elasticsearch\Model
 */
interface ElasticInterface
{
    public function createMapping(array $properties, array $settings, string $indexName = "");
    public function indexDocument($id, array $data);
    public function updateDocument($id, array $data);
    public function getDocument($id);
    public function deleteDocument($id);
    public function querySearch(array $query);
    public function loadDocumentsUsingBulk(array $data, string $indexName = "");
}
