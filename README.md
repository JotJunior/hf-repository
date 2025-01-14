# hf-repository

O **hf-repository** é uma library extensível para gerenciamento de dados, oferecendo uma camada de abstração baseada no
conceito de **Repositories**. Ele foi projetado para trabalhar de forma eficiente com o Elasticsearch, utilizando o
padrão de design **Repository Pattern** para isolar a lógica de acesso aos dados. Simplifica a interação com índices,
facilita o mapeamento de resultados para entidades e fornece suporte confiável para operações CRUD, busca avançada,
paginação e geração de consultas dinâmicas.

---

## Recursos Principais

- **Abstração de Repositórios**: Camada central para comunicação com o Elasticsearch.
- **CRUD Completo**: Operações básicas para criar, buscar, atualizar e remover itens.
- **Paginação Simplificada**: Paginador nativo com alta personalização de parâmetros.
- **Mapeamento de Entidades**: Integra resultados aos objetos do domínio do projeto.
- **Flexibilidade de Consultas**: Filtros, ordenações e seleção de atributos via consulta.
- **Fácil Extensão**: Estrutura modular para criação de repositórios específicos.
- **Gerador de código**: Comandos inteligentes para criar as entidades, repositórios e controladores, construindo um
  CRUD completo para as chamadas de API.

---

## Estrutura do Projeto

O projeto segue uma arquitetura limpa e bem organizada:

- **Entidades ([Entity](docs/entity.md))**: Representam os dados do negócio e oferecem funcionalidades para manipulação e hidratação de
  valores.
- **Repositórios ([Repository](docs/repository.md))**: Realizam todas as interações com a camada de dados, encapsulando as operações.
- **Construtor de Consultas (`QueryBuilder`)**: Fornece suporte à criação e execução de consultas dinâmicas no
  Elasticsearch.

---

## Tecnologias Utilizadas

- **Hyperf Framework**: Utilizado como base para o projeto, com suporte ao paradigma reativo e arquitetura moderna.
- **Elasticsearch**: Banco de dados NoSQL utilizado para indexação e busca avançada.
- **Redis**: Armazenamento em memória de alta performance utilizado para caching, controle do rate-limit e gerenciamento
  de sessões, otimizando a eficiência e performance do sistema.
- **PSR-11 (Container)**: Integração do padrão de contêiner de dependências para maior compatibilidade.
- **PHP 8.1+**: Versão mínima do PHP, aproveitando recursos modernos como atributos e tipagem forte.

---

## Instalação

Para integrar o **hf-repository** no seu projeto Hyperf adicione as dependências necessárias ao seu projeto (via
composer):

```bash
composer require jot/hf-repository
```

Após a instalação, publique os arquivos de configuração das dependências necessárias:

```shell
php bin/hyperf.php vendor:publish hyperf/redis
php bin/hyperf.php vendor:publish hyperf/rate-limit
php bin/hyperf.php vendor:publish hyperf/swagger
```

## Exemplo de Uso

O comando mais importante desta biblioteca é a criação de um CRUD completo a partir de um índice do Elasticsearch.

O comando `repo:crud` vai criar as classes de entidades, repositório e controlador, além de já preparar toda a
documentação do swagger e aplicar um rate limit padrão de 10 requisições por segundo.

```shell
php bin/hyperf.php repo:crud --index=orders

You are about to create a CRUD for index orders with api version v1.
The elasticsearch index related entities, repository and controller will be created during this process.

Are you sure you want to create a CRUD for index orders? [Y/n] [Y]:
 
Creating the CRUD for index orders...

Start creating entities...
[OK] ./app/Entity/Order/Customer.php
[OK] ./app/Entity/Order/Invoice.php
[OK] ./app/Entity/Order/Item.php
[OK] ./app/Entity/Order/OrderHistory.php
[OK] ./app/Entity/Order/Payment.php
[OK] ./app/Entity/Order/Shipment.php
[OK] ./app/Entity/Order/Order.php

Start creating repository...
[OK] ./app/Repository/OrderRepository.php

Start creating controller...
[OK] ./app/Controller/V1/OrderController.php
```

Os detalhes de cada classe gerada por este comando serão explicadas no próximo tópico.

## Criando as classes individualmente

Para criar individualmente as classes necessárias, siga os comandos na ordem abaixo:

### 1. Criando dinamicamente as classes das entidades

Utilize o comando de console ```repo:entity``` para gerar as entidades relacionadas ao índice.

```shell
php bin/hyperf.php repo:entity --index=orders

[OK] ./app/Entity/Order/Customer.php
[OK] ./app/Entity/Order/Invoice.php
[OK] ./app/Entity/Order/Item.php
[OK] ./app/Entity/Order/OrderHistory.php
[OK] ./app/Entity/Order/Payment.php
[OK] ./app/Entity/Order/Shipment.php
[OK] ./app/Entity/Order/Order.php
```

Conforme demonstrado, o comando vai analisar o mapping do índice informado e vai criar a entidade principal do índice e
caso haja objetos e objetos nested no índice, uma classe de entidade será gerada para cada um deles.

Cada classe gerada já vem aplicada com as configurações do Swagger, criando as referências necessárias para a
documentação do projeto.

```php 
<?php

declare(strict_types=1);

namespace App\Entity\Order;

use Jot\HfRepository\Entity;
use Jot\HfRepository\Trait\HasTimestamps;
use Jot\HfRepository\Trait\HasLogicRemoval;
use Hyperf\Swagger\Annotation as SA;

#[SA\Schema(schema: "app.entity.order.order")]
class Order extends Entity
{

    use HasLogicRemoval, HasTimestamps;

        #[SA\Property(
        property: "created_at",
        type: "string",
        format: "string",
        readOnly: true,
        x: ["php_type" => "\DateTime"]
    )]
    protected ?\DateTime $createdAt = null;

    #[SA\Property(
        property: "customer",
        ref: "#/components/schemas/app.entity.order.customer",
        x: ["php_type" => "\App\Entity\Order\Customer"]
    )]
    protected ?\App\Entity\Order\Customer $customer = null;

    #[SA\Property(
        property: "id",
        type: "string",
        example: "749ef2bd-1372-4ef2-998c-0cbec9bc1496"
    )]
    protected ?string $id = null;

    #[SA\Property(
        property: "installment_count",
        type: "integer",
        example: 5
    )]
    protected ?int $installmentCount = null;

    // ...

}

```

### 2. Criando o repositório

Para criar o repositório relacionado ao índice do elasticsearch, utilize o comando abaixo:

```shell
php bin/hyperf.php repo:repository --index=orders

[OK] ./app/Repository/OrderRepository.php
```

O comando criará dentro do diretório ```app/Repository``` uma classe User com o seguinte conteúdo:

```php
<?php

namespace App\Repository;

use Jot\HfRepository\Repository;
use App\Entity\Order\Order as Entity;

class OrderRepository extends Repository
{
    protected string $entity = Entity::class;

}
```

### 3. Criando um controlador que fará uso do repositório

O comando `repo:controller` vai criar um controlador OrderController já preparado para receber os métodos GET, POST,
PUT, DELETE e HEAD com as consultas e persistências realizadas pelo repositório gerado anteriormente.

```shell
php bin/hyperf.php repo:controller --index=orders

[OK] ./app/Controller/V1/OrderController.php
```

Repare que o diretório final do controlador é baseada em uma versão. É possível definir a versão da api acrescentando a
opção `--api-version=`  ao comando.

```shell
php bin/hyperf.php repo:controller --index=orders --api-version=v2
```

Caso o arquivo do controlador exista, o comando perguntará se deseja substituí-lo.

```shell
php bin/hyperf.php repo:controller --index=orders

The file ./app/Controller/V1/OrderController.php already exists. Overwrite file? [y/n/a] [n]:
```

Também é possível já forçar a substituição do arquivo diretamente no comando usando a opção --force

```shell
php bin/hyperf.php repo:controller --index=orders --force
```

O arquivo do controlador gerado também vem com as configurações do Swagger aplicadas. Além disso, também são aplicadas
configurações de rate limit para limitar as requisições à API.

```php 
<?php

declare(strict_types=1);

namespace App\Controller\V1;

use App\Controller\AbstractController;
use App\Entity\Order\Order;
use App\Repository\OrderRepository;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Hyperf\RateLimit\Annotation\RateLimit;
use Hyperf\Swagger\Annotation as SA;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

#[SA\HyperfServer('http')]
#[SA\Tag(
    name: 'Order',
    description: 'Endpoints related to orders management'
)]
#[SA\Schema(schema: "app.error.response", required: ["result", "error"],
    properties: [
        new SA\Property(property: "result", type: "string", example: "error"),
        new SA\Property(property: "error", type: "string", example: "Error message"),
        new SA\Property(property: "data", type: "string|array", example: null),
    ],
    type: "object"
)]
#[Controller(prefix: '/v1')]
class OrderController extends AbstractController
{

    #[Inject]
    protected OrderRepository $repository;

    #[SA\Get(
        path: "/orders",
        description: "Retrieve a list of orders with optional pagination.",
        summary: "Get Orders List",
        tags: ["Order"],
        parameters: [
            new SA\Parameter(
                name: "_page",
                description: "Page number for pagination",
                in: "query",
                required: false,
                schema: new SA\Schema(type: "integer", example: 1)
            ),
            new SA\Parameter(
                name: "_per_page",
                description: "Number of results per page",
                in: "query",
                required: false,
                schema: new SA\Schema(type: "integer", example: 10)
            ),
            new SA\Parameter(
                name: "_sort",
                description: "Sort results by a specific fields",
                in: "query",
                required: false,
                schema: new SA\Schema(type: "string", example: "created_at:desc,updated_at:desc")
            ),
            new SA\Parameter(
                name: "_fields",
                description: "Fields to include in the response",
                in: "query",
                required: false,
                schema: new SA\Schema(type: "string", example: "id,created_at,updated_at")
            )
        ],
        responses: [
            new SA\Response(
                response: 200,
                description: "Order details retrieved successfully",
                content: new SA\JsonContent(
                    properties: [
                        new SA\Property(
                            property: "data",
                            type: "array",
                            items: new SA\Items(ref: "#/components/schemas/app.entity.order.order")
                        ),
                        new SA\Property(
                            property: "result",
                            type: "string",
                            example: "success"
                        ),
                        new SA\Property(
                            property: "error",
                            type: "string",
                            example: null,
                            nullable: true
                        )
                    ],
                    type: "object"
                )
            ),
            new SA\Response(
                response: 400,
                description: "Bad Request",
                content: new SA\JsonContent(ref: "#/components/schemas/app.error.response")
            ),
            new SA\Response(
                response: 500,
                description: "Application Error",
                content: new SA\JsonContent(ref: "#/components/schemas/app.error.response")
            )
        ]
    )]
    #[RateLimit(create: 10, consume: 1)]
    #[GetMapping('orders[/]')]
    public function getOrdersList(): PsrResponseInterface
    {
        $response = $this->repository->paginate($this->request->all());
        if ($response['result'] === 'error') {
            return $this->response->withStatus(400)->json($response);
        }
        return $this->response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->json($response);

    }

    #[SA\Get(
        path: "/orders/{id}",
        description: "Retrieve the details of a specific orders identified by ID.",
        summary: "Get Order Data",
        tags: ["Order"],
        parameters: [
            new SA\Parameter(
                name: "id",
                description: "Unique identifier of the orders",
                in: "path",
                required: true,
                schema: new SA\Schema(type: "string", example: "12345")
            )
        ],
        responses: [
            new SA\Response(
                response: 200,
                description: "Order details retrieved successfully",
                content: new SA\JsonContent(
                    properties: [
                        new SA\Property(
                            property: "data",
                            ref: "#/components/schemas/app.entity.order.order"
                        ),
                        new SA\Property(
                            property: "result",
                            type: "string",
                            example: "success"
                        ),
                        new SA\Property(
                            property: "error",
                            type: "string",
                            example: "Invalid request parameters",
                            nullable: true
                        )
                    ],
                    type: "object"
                )
            ),
            new SA\Response(
                response: 400,
                description: "Server Error",
                content: new SA\JsonContent(ref: "#/components/schemas/app.error.response")
            ),
            new SA\Response(
                response: 404,
                description: "Order Not Found",
                content: new SA\JsonContent(ref: "#/components/schemas/app.error.response")
            ),
            new SA\Response(
                response: 500,
                description: "Application Error",
                content: new SA\JsonContent(ref: "#/components/schemas/app.error.response")
            )
        ]
    )]
    #[RateLimit(create: 10, consume: 1)]
    #[GetMapping('orders/{id}')]
    public function getOrderData(string $id): PsrResponseInterface
    {
        $response = $this->repository->find($id);

        if (empty($response)) {
            return $this->response->withStatus(404)->json([
                'data' => null,
                'result' => 'not-found',
                'error' => 'Document not found'
            ]);
        }

        return $this->response->json([
            'data' => $response->toArray(),
            'result' => 'success',
            'error' => null,
        ]);
    }

    #[SA\Post(
        path: "/orders",
        description: "Add a new orders to the system.",
        summary: "Create a New Order",
        requestBody: new SA\RequestBody(
            required: true,
            content: new SA\JsonContent(ref: "#/components/schemas/app.entity.order.order")
        ),
        tags: ["Order"],
        responses: [
            new SA\Response(
                response: 201,
                description: "Order Created",
                content: new SA\JsonContent(ref: "#/components/schemas/app.entity.order.order")
            ),
            new SA\Response(
                response: 400,
                description: "Bad Request",
                content: new SA\JsonContent(ref: "#/components/schemas/app.error.response")
            ),
            new SA\Response(
                response: 500,
                description: "Application Error",
                content: new SA\JsonContent(ref: "#/components/schemas/app.error.response")
            )
        ]
    )]
    #[RateLimit(create: 10, consume: 2)]
    #[PostMapping('orders[/]')]
    public function createOrder(): PsrResponseInterface
    {
        $entity = new Order($this->request->all());

        try {
            $response = $this->repository->create($entity);
        } catch (\Throwable $e) {
            return $this->response->withStatus(400)->json([
                'data' => null,
                'result' => 'error',
                'error' => $e->getMessage()
            ]);
        }

        return $this->response->withStatus(201)->json([
            'data' => $response->toArray(),
            'result' => 'success',
            'error' => null,
        ]);
    }

    #[SA\Put(
        path: "/orders/{id}",
        description: "Update the details of an existing orders.",
        summary: "Update an Order",
        requestBody: new SA\RequestBody(
            required: true,
            content: new SA\JsonContent(ref: "#/components/schemas/app.entity.order.order")
        ),
        tags: ["Order"],
        parameters: [
            new SA\Parameter(
                name: "id",
                description: "Unique identifier of the orders",
                in: "path",
                required: true,
                schema: new SA\Schema(type: "string", example: "12345")
            )
        ],
        responses: [
            new SA\Response(
                response: 200,
                description: "Order Updated",
                content: new SA\JsonContent(ref: "#/components/schemas/app.entity.order.order")
            ),
            new SA\Response(
                response: 400,
                description: "Bad Request",
                content: new SA\JsonContent(ref: "#/components/schemas/app.error.response")
            ),
            new SA\Response(
                response: 404,
                description: "Order Not Found",
                content: new SA\JsonContent(ref: "#/components/schemas/app.error.response")
            ),
            new SA\Response(
                response: 500,
                description: "Application Error",
                content: new SA\JsonContent(ref: "#/components/schemas/app.error.response")
            )
        ]
    )]
    #[RateLimit(create: 10, consume: 5)]
    #[PutMapping('orders/{id}')]
    public function updateOrder(string $id): PsrResponseInterface
    {
        $entity = new Order(['id' => $id, ...$this->request->all()]);

        try {
            $response = $this->repository->update($entity);
        } catch (\Throwable $e) {
            return $this->response->withStatus(400)->json([
                'data' => null,
                'result' => 'error',
                'error' => $e->getMessage()
            ]);
        }

        return $this->response->json([
            'data' => $response->toArray(),
            'result' => 'success',
            'error' => null,
        ]);
    }

    #[SA\Delete(
        path: "/orders/{id}",
        description: "Delete an existing orders by its unique identifier.",
        summary: "Delete an Order",
        tags: ["Order"],
        parameters: [
            new SA\Parameter(
                name: "id",
                description: "Unique identifier of the orders",
                in: "path",
                required: true,
                schema: new SA\Schema(type: "string", example: "12345")
            )
        ],
        responses: [
            new SA\Response(
                response: 200,
                description: "Order Deleted",
                content: new SA\JsonContent(
                    properties: [
                        new SA\Property(
                            property: "data",
                            type: "string",
                            nullable: true
                        ),
                        new SA\Property(
                            property: "result",
                            type: "string",
                            example: "success"
                        ),
                        new SA\Property(
                            property: "error",
                            type: "string",
                            example: "Order not found",
                            nullable: true
                        )
                    ],
                    type: "object"
                )
            ),
            new SA\Response(
                response: 400,
                description: "Bad Request",
                content: new SA\JsonContent(ref: "#/components/schemas/app.error.response")
            ),
            new SA\Response(
                response: 404,
                description: "Order Not Found",
                content: new SA\JsonContent(ref: "#/components/schemas/app.error.response")
            ),
            new SA\Response(
                response: 500,
                description: "Application Error",
                content: new SA\JsonContent(ref: "#/components/schemas/app.error.response")
            )
        ]
    )]
    #[RateLimit(create: 1, consume: 1)]
    #[DeleteMapping('orders/{id}')]
    public function deleteOrder(string $id): PsrResponseInterface
    {
        return $this->response->json([
            'data' => null,
            'result' => $this->repository->delete($id) ? 'success' : 'error',
            'error' => null,
        ]);
    }
}
```


