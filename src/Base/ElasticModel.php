<?php

namespace Elasticsearch\Model\Base;

use Elastic\Elasticsearch\Exception\ElasticsearchException;
use Elasticsearch\Model\Boot\Connection;
use Elasticsearch\Model\Base\ElasticModelException;
use Elasticsearch\Model\Interfaces\ElasticInterface;
use Exception;
use InvalidArgumentException;
use ReflectionClass;

/**
 * ElasticModel Base
 * @link 
 * @author Roberto Dorado <robertodorado7@gmail.com>
 * @package Elasticsearch\Model\Base
 */
abstract class ElasticModel implements ElasticInterface
{
    public string $index = "";

    private array $properties = [];

    /**
     * ElasticModel constructor
     */
    public function __construct(string $namespace)
    {
        if (!class_exists($namespace)) {
            throw new InvalidArgumentException("namespace inválido");
        }

        $transformCamelCaseToSnakeCase = function (string $value) {
            return preg_replace_callback(
                "/([\da-z])([\dA-Z])?([\dA-Z])/",
                function ($matches) {
                    return $matches[1] . (!empty($matches[2]) ? "_{$matches[2]}" : "") . "_{$matches[3]}";
                },
                $value
            );
        };
        
        $reflectionClass = new ReflectionClass($namespace);
        $namespace = basename(str_replace("\\", "/", $namespace));
        $namespace = $transformCamelCaseToSnakeCase($namespace);
        
        $namespace = strtolower($namespace);
        $this->index = $namespace;

        $this->properties = $reflectionClass->getProperties();
        $this->properties = array_reduce($this->properties, function ($acc, $property) use ($transformCamelCaseToSnakeCase) {
            $property->setAccessible(true);
            $propName = $transformCamelCaseToSnakeCase($property->getName());
            $acc[$propName]['type'] = @strtolower($property->getValue());
            return $acc;
        }, []);
    }

    public function checkIfIndexExists(string $indexName = ""): bool
    {
        $indexName = empty($indexName) ? $this->index : $indexName;
        return Connection::instance()->indices()->exists(['index' => $indexName])->asBool();
    }

    public function deleteIndex(string $indexName = ""): bool
    {
        try {
            $indexName = empty($indexName) ? $this->index : $indexName;
            Connection::instance()->indices()->delete(['index' => $indexName]);
            return true;
        } catch (ElasticsearchException $th) {
            throw new ElasticModelException(json_encode(
                [
                    "error_delete_index" => "Erro ao deletar o índice",
                    "status_code" => $th->getCode(),
                    "message" => $th->getMessage()
                ]
            ), $th->getCode());
        }
    }

    public function searchByNestedField(string $subDocument, array $subdocumentMatch): array
    {
        $params = [
            'index' => $this->index,
            'body' => [
                'query' => [
                    'nested' => [
                        'path' => $subDocument,
                        'query' => [
                            'match' => $subdocumentMatch
                        ]
                    ]
                ]
            ]
        ];

        try {
            $response = Connection::instance()->search($params);
            return $response['hits']['hits'] ?? [];
        } catch (ElasticsearchException $th) {
            throw new ElasticModelException(json_encode(
                [
                    "error_search_nested_field" => "Erro na captura por campo aninhado",
                    "status_code" => $th->getCode(),
                    "message" => $th->getMessage()
                ]
            ), $th->getCode());
        }
    }

    public function searchByScriptScore(array $queryMatch, string $source): array
    {
        $params = [
            'index' => $this->index,
            'body' => [
                'query' => [
                    'script_score' => [
                        'query' => [
                            'match' => $queryMatch
                        ],
                        'script' => [
                            'source' => $source
                        ]
                    ]
                ]
            ]
        ];

        try {
            $response = Connection::instance()->search($params);
            return $response['hits']['hits'] ?? [];
        } catch (ElasticsearchException $th) {
            throw new ElasticModelException(json_encode(
                [
                    "error_search_script_score" => "Erro na captura por script",
                    "status_code" => $th->getCode(),
                    "message" => $th->getMessage()
                ]
            ), $th->getCode());
        }
    }

    public function searchByMatchPhrase(array $fields): array
    {
        $params = [
            'index' => $this->index,
            'body' => [
                'query' => [
                    'match_phrase' => $fields
                ]
            ]
        ];

        try {
            $response = Connection::instance()->search($params);
            return $response['hits']['hits'] ?? [];
        } catch (ElasticsearchException $th) {
            throw new ElasticModelException(json_encode(
                [
                    "error_search_match_phrase" => "Erro na captura por ocorrência de frase",
                    "status_code" => $th->getCode(),
                    "message" => $th->getMessage()
                ]
            ), $th->getCode());
        }
    }

    public function checkIfExistsField(string $field): array
    {
        $params = [
            'index' => $this->index,
            'body'  => [
                'query' => [
                    'exists' => [
                        'field' => $field
                    ]
                ]
            ]
        ];

        try {
            $response = Connection::instance()->search($params);
            return $response['hits']['hits'] ?? [];
        } catch (ElasticsearchException $th) {
            throw new ElasticModelException(json_encode(
                [
                    "error_check_field_exists" => "Erro ao verificar se o campo existe",
                    "status_code" => $th->getCode(),
                    "message" => $th->getMessage()
                ]
            ), $th->getCode());
        }
    }

    public function searchByMultiMatch(string $query, array $fields, string $type = ""): array
    {
        $multiMatchData = [
            'query' => $query,
            'fields' => $fields
        ];

        if (!empty($type)) {
            if (!in_array($type, multiMatchTypeData())) {
                throw new Exception(sprintf("prorpiedade type inválido: %s", $type));
            }
            $multiMatchData["type"] = $type;
        }

        $params = [
            'index' => $this->index,
            'body' => [
                'query' => [
                    'multi_match' => $multiMatchData
                ]
            ]
        ];

        try {
            $response = Connection::instance()->search($params);
            return $response['hits']['hits'] ?? [];
        } catch (ElasticsearchException $th) {
            throw new ElasticModelException(json_encode(
                [
                    "error_search_multi_match" => "Erro na captura por multiplas ocorrências",
                    "status_code" => $th->getCode(),
                    "message" => $th->getMessage()
                ]
            ), $th->getCode());
        }
    }

    public function searchByFuzzy(array $fields): array
    {
        $params = [
            'index' => $this->index,
            'body' => [
                'query' => [
                    'fuzzy' => $fields
                ]
            ]
        ];

        try {
            $response = Connection::instance()->search($params);
            return $response['hits']['hits'] ?? [];
        } catch (ElasticsearchException $th) {
            throw new ElasticModelException(json_encode(
                [
                    "error_search_fuzzy" => "Erro na captura por fuzzy",
                    "status_code" => $th->getCode(),
                    "message" => $th->getMessage()
                ]
            ), $th->getCode());
        }
    }

    public function searchByPrefix(array $fields): array
    {
        $params = [
            'index' => $this->index,
            'body' => [
                'query' => [
                    'prefix' => $fields
                ]
            ]
        ];

        try {
            $response = Connection::instance()->search($params);
            return $response['hits']['hits'] ?? [];
        } catch (ElasticsearchException $th) {
            throw new ElasticModelException(json_encode(
                [
                    "error_search_prefix" => "Erro na captura por prefixo",
                    "status_code" => $th->getCode(),
                    "message" => $th->getMessage()
                ]
            ), $th->getCode());
        }
    }

    public function searchByRange(array $fields): array
    {
        $params = [
            'index' => $this->index,
            'body' => [
                'query' => [
                    'range' => $fields
                ]
            ]
        ];

        try {
            $response = Connection::instance()->search($params);
            return $response['hits']['hits'] ?? [];
        } catch (ElasticsearchException $th) {
            throw new ElasticModelException(json_encode(
                [
                    "error_search_range" => "Erro na captura por intervalo",
                    "status_code" => $th->getCode(),
                    "message" => $th->getMessage()
                ]
            ), $th->getCode());
        }
    }

    public function searchByWildCard(array $fields): array
    {
        $params = [
            'index' => $this->index,
            'body' => [
                'query' => [
                    'wildcard' => $fields
                ]
            ]
        ];

        try {
            $response = Connection::instance()->search($params);
            return $response['hits']['hits'] ?? [];
        } catch (ElasticsearchException $th) {
            throw new ElasticModelException(json_encode(
                [
                    "error_search_wildcard" => "Erro na captura coringa",
                    "status_code" => $th->getCode(),
                    "message" => $th->getMessage()
                ]
            ), $th->getCode());
        }
    }

    public function searchByTerms(array $fields): array
    {
        $params = [
            'index' => $this->index,
            'body'  => [
                'query' => [
                    'terms' => $fields
                ]
            ]
        ];

        try {
            $response = Connection::instance()->search($params);
            return $response['hits']['hits'] ?? [];
        } catch (ElasticsearchException $th) {
            throw new ElasticModelException(json_encode(
                [
                    "error_search_terms" => "Erro na captura pelos termos",
                    "status_code" => $th->getCode(),
                    "message" => $th->getMessage()
                ]
            ), $th->getCode());
        }
    }

    public function searchByTerm(array $fields): array
    {
        $params = [
            'index' => $this->index,
            'body'  => [
                'query' => [
                    'term' => $fields
                ]
            ]
        ];

        try {
            $response = Connection::instance()->search($params);
            return $response['hits']['hits'] ?? [];
        } catch (ElasticsearchException $th) {
            throw new ElasticModelException(json_encode(
                [
                    "error_search_term" => "Erro na captura pelo termo",
                    "status_code" => $th->getCode(),
                    "message" => $th->getMessage()
                ]
            ), $th->getCode());
        }
    }

    public function searchByCombinationsAndConditions(array $matchFields, array $filterFields): array
    {
        $checkMatchFields = array_filter($matchFields, fn($item) => array_keys($item)[0] !== 'match');
        if (!empty($checkMatchFields)) {
            throw new Exception("parâmetros do tipo match estão inválidos");
        }

        $checkFilterFields = array_filter($filterFields, fn($item) => array_keys($item)[0] !== 'term');
        if (!empty($checkFilterFields)) {
            throw new Exception("parâmetros do tipo filter estão inválidos");
        }

        $params = [
            'index' => $this->index,
            'body'  => [
                'query' => [
                    'bool' => [
                        'must' => $matchFields
                    ],
                    'filter' => $filterFields
                ]
            ]
        ];

        try {
            $response = Connection::instance()->search($params);
            return $response['hits']['hits'] ?? [];
        } catch (ElasticsearchException $th) {
            throw new ElasticModelException(json_encode(
                [
                    "error_combinations_conditions" => "Erro na captura de combinações e condições",
                    "status_code" => $th->getCode(),
                    "message" => $th->getMessage()
                ]
            ), $th->getCode());
        }
    }

    public function matchData(array $fields): array
    {
        $params = [
            'index' => $this->index,
            'body'  => [
                'query' => [
                    'match' => $fields
                ]
            ]
        ];

        try {
            $response = Connection::instance()->search($params);
            return $response['hits']['hits'] ?? [];
        } catch (ElasticsearchException $th) {
            throw new ElasticModelException(json_encode(
                [
                    "error_match_data" => "Erro na captura match",
                    "status_code" => $th->getCode(),
                    "message" => $th->getMessage()
                ]
            ), $th->getCode());
        }
    }

    public function loadDocumentsUsingBulk(array $data, string $indexName = ""): ElasticModel
    {
        $indexName = empty($indexName) ? $this->index : $indexName;
        $params = [
            'body' => []
        ];

        foreach ($data as $value) {
            if (!empty($value['id'])) {
                $params['body'][] = [
                    'index' => [
                        '_index' => $indexName,
                        '_id' => $value['id']
                    ]
                ];
            } else {
                $params['body'][] = [
                    'index' => [
                        '_index' => $indexName,
                    ]
                ];
            }

            $params['body'][] = $value;
        }

        try {
            Connection::instance()->bulk($params);
            return $this;
        } catch (ElasticsearchException $th) {
            throw new ElasticModelException(json_encode(
                [
                    "error_load_bulk" => "Erro ao carregar os documentos",
                    "status_code" => $th->getCode(),
                    "message" => $th->getMessage()
                ]
            ), $th->getCode());
        }
    }

    public function querySearch(array $query): array
    {
        $params = [
            'index' => $this->index,
            'body' => $query,
        ];

        try {
            $response = Connection::instance()->search($params);
            return $response['hits']['hits'] ?? [];
        } catch (ElasticsearchException $th) {
            throw new ElasticModelException(json_encode(
                [
                    "error_query_search" => sprintf("Falha ao executar a query: %s", json_encode($query)),
                    "status_code" => $th->getCode(),
                    "message" => $th->getMessage()
                ]
            ), $th->getCode());
        }
    }

    public function deleteDocument($id): bool
    {
        $params = [
            'index' => $this->index,
            'id'    => $id,
        ];

        try {
            Connection::instance()->delete($params);
            return true;
        } catch (ElasticsearchException $th) {
            throw new ElasticModelException(json_encode(
                [
                    "error_delete_document" => sprintf("Falha ao deletar o documento ID: %s", $id),
                    "status_code" => $th->getCode(),
                    "message" => $th->getMessage()
                ]
            ), $th->getCode());
        }
    }

    public function getDocument($id): array
    {
        $params = [
            'index' => $this->index,
            'id' => $id,
        ];

        try {
            $response = Connection::instance()->get($params)->asArray();
            return $response;
        } catch (ElasticsearchException $th) {
            throw new ElasticModelException(json_encode(
                [
                    "error_get_document" => sprintf("Falha ao encontrar o documento ID: %s", $id),
                    "status_code" => $th->getCode(),
                    "message" => $th->getMessage()
                ]
            ), $th->getCode());
        }
    }

    public function updateDocument($id, array $data): ElasticModel
    {
        $params = [
            'index' => $this->index,
            'id'    => $id,
            'body'  => [
                'doc' => $data
            ],
        ];

        try {
            Connection::instance()->update($params);
            return $this;
        } catch (ElasticsearchException $th) {
            throw new ElasticModelException(json_encode(
                [
                    "error_update_document" => sprintf("Falha ao atualizar documento ID: %s", $id),
                    "status_code" => $th->getCode(),
                    "message" => $th->getMessage()
                ]
            ), $th->getCode());
        }
    }

    public function indexDocument($id, array $data): ElasticModel
    {
        $params = [
            'index' => $this->index,
            'id' => $id,
            'body' => $data,
        ];

        try {
            Connection::instance()->index($params);
            return $this;
        } catch (ElasticsearchException $th) {
            throw new ElasticModelException(json_encode(
                [
                    "error_index_document" => sprintf("Falha ao indexar documento ID: %s", $id),
                    "status_code" => $th->getCode(),
                    "message" => $th->getMessage()
                ]
            ), $th->getCode());
        }
    }

    public function createMapping(array $properties = [], array $settings = [], string $indexName = ""): ElasticModel
    {
        if (!empty($settings)) {
            $checkSettings = ["number_of_shards", "number_of_replicas"];
            $settingsValue = array_keys($settings);

            sort($settingsValue);
            sort($checkSettings);

            if ($settingsValue !== $checkSettings) {
                throw new Exception(sprintf('Parâmetros %s são obrigatórios ao definir as configurações do mapping', implode(", ", $checkSettings)));
            }

            $settingsValue = array_values($settings);
            $settingsValue = array_filter($settingsValue, fn($item) => preg_match('/\D/', $item));

            if (!empty($settingsValue)) {
                throw new Exception('Valores de configuração precisam ser obrigatóriamente numéricos');
            }
        }

        $indexName = empty($indexName) ? $this->index : $indexName;
        $properties = empty($properties) ? $this->properties : $properties;
        $checkPropertiesType = array_filter($properties, fn($item) => empty($item['type']));

        if (!empty($checkPropertiesType)) {
            throw new Exception('Propriedade type é obrigatório na definição do mapeamento');
        }

        $validateMappingsType = array_filter($properties, fn($item) => empty(propertiesType()[$item['type']]));
        if (!empty($validateMappingsType)) {
            $invalidType = array_map(fn($item) => $item['type'], $validateMappingsType);
            $errorMessage = count($invalidType) > 1 ?
                sprintf('Valores da propriedade type são inválidos: %s', implode(", ", $invalidType)) :
                sprintf('Valor da propriedade type não é valido: %s', implode(", ", $invalidType));

            throw new Exception($errorMessage);
        }

        $params = [
            'index' => $indexName,
            'body'  => [
                'mappings' => [
                    'properties' => $properties
                ]
            ]
        ];

        if (!empty($settings)) {
            $params['body'] = array_merge(['settings' => $settings], $params['body']);
        }

        try {
            Connection::instance()->indices()->create($params);
            return $this;
        } catch (ElasticsearchException $th) {
            throw new ElasticModelException(json_encode(
                [
                    "error_create_mapping" => sprintf('Não foi possível criar o índice: %s', $indexName),
                    "status_code" => $th->getCode(),
                    "message" => $th->getMessage()
                ]
            ), $th->getCode());
        }
    }
}
