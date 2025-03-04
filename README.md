# Elasticsearch Model - PHP

Este repositório contém uma implementação de um modelo para interagir com o Elasticsearch utilizando a biblioteca oficial `elasticsearch/elasticsearch` no PHP.

## 📌 Requisitos

- PHP 7.4 ou superior
- Composer
- Elasticsearch rodando (localmente ou em um servidor)
- Biblioteca `elasticsearch/elasticsearch`

## 📦 Instalação

Instale a biblioteca necessária via Composer:

```bash
composer require elasticsearch/elasticsearch
```

## 🔧 Configuração

Antes de utilizar a model, configure a conexão com o Elasticsearch através de uma constante:

```php
define("HOST", 'localhost:9200');
```

## 🏗 Estrutura da Model

A model implementa métodos padrão para interagir com um índice do Elasticsearch. Exemplo da model genérica:

```php
abstract class ElasticModel implements ElasticInterface
{
    private string $index = "";

    private array $properties = [];

    /**
     * ElasticModel constructor
     */
    public function __construct(string $namespace)
    {
        if (!class_exists($namespace)) {
            throw new InvalidArgumentException("namespace inválido");
        }

        $this->index = strtolower(basename(str_replace("\\", "/", $namespace)));
        $reflectionClass = new ReflectionClass($namespace);

        $this->properties = $reflectionClass->getProperties();
        $this->properties = array_reduce($this->properties, function ($acc, $property) {
            $property->setAccessible(true);
            $propName = preg_replace("/([a-z])([A-Z])/", "$1_$2", $property->getName());
            $acc[$propName]['type'] = $property->getValue();
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

## 🚀 Conclusão

Este modelo fornece uma estrutura básica para interagir com o Elasticsearch no PHP. Você pode expandi-lo conforme necessário para suportar pesquisas avançadas, paginação, mapeamentos personalizados e muito mais.

---

📌 **Dica:** Certifique-se de que o Elasticsearch está rodando antes de realizar chamadas à API.
