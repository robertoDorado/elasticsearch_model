# Elasticsearch Model - PHP

Este repositório contém uma implementação de um modelo para interagir com o Elasticsearch utilizando a biblioteca oficial `elasticsearch/elasticsearch` no PHP.

## 📌 Requisitos

- PHP 7.4 ou superior
- Composer
- Elasticsearch rodando (localmente ou em um servidor)
- Biblioteca `elasticsearch/elasticsearch`

## 🔧 Configuração

Antes de utilizar a model, configure a conexão com o Elasticsearch através de uma constante:

```php
define("HOST", 'localhost:9200');
```

## 🏗 Estrutura da Model

A model implementa métodos padrão para interagir com um índice do Elasticsearch. Exemplo da model genérica:

```php

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

    public array $properties = [];

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
        $this->properties = array_values(array_filter($this->properties, function ($item) {
            $item->setAccessible(true);
            return $item->isStatic();
        }));

        $this->properties = array_reduce($this->properties, function ($acc, $property) use ($transformCamelCaseToSnakeCase) {
            $property->setAccessible(true);
            $typeName = $property->getType()->getName();

            $matchType = [
                "string" => function ($property) use ($transformCamelCaseToSnakeCase, $acc) {
                    $propName = strtolower($transformCamelCaseToSnakeCase($property->getName()));
                    $acc[$propName]['type'] = strtolower($property->getValue());
                    return $acc;
                },

                "array" => function ($property) use ($transformCamelCaseToSnakeCase, $acc) {
                    $referenceData = array_keys($property->getValue());
                    $propName = strtolower($transformCamelCaseToSnakeCase($property->getName()));
                    sort($referenceData);
                    
                    if (['properties', 'type'] !== $referenceData) {
                        throw new InvalidArgumentException('Erro ao definir as propriedades obrigatórias no mapping array');
                    }

                    $dataKeys = array_map(function ($keyName) use ($transformCamelCaseToSnakeCase) {
                        return strtolower($transformCamelCaseToSnakeCase($keyName));
                    }, array_keys($property->getValue()['properties']));
                    
                    $dataValues = array_values($property->getValue()['properties']);
                    $properties = array_combine($dataKeys, $dataValues);

                    $acc[$propName]["type"] = $property->getValue()['type'];
                    $acc[$propName]["properties"] = $properties;
                    return $acc;
                }
            ];

            return $matchType[$typeName]($property) ?? [];
        }, []);
    }

    public function checkIfIndexExists(string $indexName = ""): bool
    {
        $indexName = empty($indexName) ? $this->index : $indexName;
        return Connection::instance()->indices()->exists(['index' => $indexName])->asBool();
    }
}
```

## 📚 Exemplos de Uso

Crie uma classe modelo que vai extender a classe modelo genérica
```php
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
```

### Criar um modelo com um campo nested
```php
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
```

### Criar um Documento
```php
$shakespear = new Shakespear();
$shakespear->createMapping();
```

### Carregamento de dados em massa utilizando Bulk
```php
$jsonData = file_get_contents("src/json/shakespeare_7.0.json", true);
$jsonData = json_decode($jsonData, true);
$shakespear->loadDocumentsUsingBulk($jsonData)
```

### Capturar um documento
```php
$response = $shakespear->getDocument('1');
print_r($response);
```

### Atualizar um Documento
```php
$shakespear->updateDocument('1', ['preco' => 120]);
```

### Deletar um Documento
```php
$shakespear->deleteDocument('1');
```

## Inserir documento em um ídice que possui um campo do tipo nested
```php
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
```

## 🚀 Conclusão

Este modelo fornece uma estrutura básica para interagir com o Elasticsearch no PHP. Você pode expandi-lo conforme necessário para suportar pesquisas avançadas, paginação, mapeamentos personalizados e muito mais.

---

📌 **Dica:** Certifique-se de que o Elasticsearch está rodando antes de realizar chamadas à API.
