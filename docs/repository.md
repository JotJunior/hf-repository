# Classe `Repository`

A classe `Repository` fornece uma abstração para gerenciar entidades e interagir com índices no Elasticsearch, permitindo operações como criação, leitura, atualização, exclusão, paginação e buscas avançadas. Ela serve como base para implementações específicas de repositórios no projeto.

---

## Namespace

```php
namespace Jot\HfRepository;
```

---

## Descrição

### Funcionalidades Principais:

- **Gerenciamento de Entidades**: Retorna, cria, atualiza ou remove entidades armazenadas.
- **Construção de Consultas**: Manipula atributos de busca e filtros usando o `QueryBuilder`.
- **Paginação**: Pagina os resultados com base em parâmetros fornecidos.
- **Suporte ao Elasticsearch**: Realiza operações diretamente em índices do Elasticsearch.

---

## Propriedades

### `protected string $entity`
Classe de entidade associada ao repositório.

### `protected string $index`
O nome do índice Elasticsearch derivado dinamicamente do nome da classe.

### `protected QueryBuilder $queryBuilder`
Instância do `QueryBuilder` fornecida pelo contêiner.

---

## Métodos

### `__construct(ContainerInterface $container)`

Construtor que inicializa o repositório, associando o `QueryBuilder` e definindo o nome do índice com base no nome da classe.

**Parâmetros:**
- `container`: Instância do contêiner de injeção de dependência.

---

### `find(string $id): ?EntityInterface`

Recupera uma entidade pelo seu identificador único.

**Parâmetros:**
- `id`: O identificador da entidade.

**Retorno:**
- Uma instância da entidade correspondente ou `null` se não encontrada.

**Exemplo de Uso:**
```php
$entity = $repository->find('12345');
```

---

### `first(array $params): ?EntityInterface`

Recupera a primeira entidade que corresponde aos filtros fornecidos.

**Parâmetros:**
- `params`: Filtros em formato de array associativo.

**Retorno:**
- Uma instância da entidade correspondente ou `null` se nenhum registro for encontrado.

**Exemplo de Uso:**
```php
$params = ['status' => 'active'];
$entity = $repository->first($params);
```

---

### `search(array $params): array`

Executa uma consulta baseada nos parâmetros e retorna uma lista de instâncias da entidade correspondente.

**Parâmetros:**
- `params`: Array contendo os filtros da consulta.

**Retorno:**
- Um array de instâncias da entidade.

**Exemplo de Uso:**
```php
$params = ['status' => 'published'];
$entities = $repository->search($params);
```

---

### `paginate(array $params, int $page = 1, int $perPage = 10): array`

Realiza a paginação dos resultados com base nos parâmetros fornecidos.

**Parâmetros:**
- `params`: Filtros para a consulta.
- `page`: Número da página atual (padrão: `1`).
- `perPage`: Número de itens por página (padrão: `10`).

**Retorno:**
- Um array contendo os dados paginados, incluindo:
  - `current_page`
  - `per_page`
  - `total`

**Exemplo de Uso:**
```php
$params = ['status' => 'active'];
$paginatedResult = $repository->paginate($params, 2, 20);
```

---

### `create(EntityInterface $entity): EntityInterface`

Cria e armazena uma nova instância da entidade no índice.

**Parâmetros:**
- `entity`: A entidade a ser criada.

**Retorno:**
- A entidade criada.

**Exceções:**
- `RepositoryCreateException`: Quando ocorre algum erro durante o processo de criação.

**Exemplo de Uso:**
```php
$newEntity = new UserEntity(['name' => 'Jane Doe']);
$createdEntity = $repository->create($newEntity);
```

---

### `update(EntityInterface $entity): EntityInterface`

Atualiza uma entidade existente no índice.

**Parâmetros:**
- `entity`: A entidade a ser atualizada (deve conter um identificador).

**Retorno:**
- A entidade atualizada.

**Exceções:**
- `RepositoryUpdateException`: Quando ocorre algum erro durante o processo de atualização.

**Exemplo de Uso:**
```php
$existingEntity->name = 'Updated Name';
$updatedEntity = $repository->update($existingEntity);
```

---

### `delete(string $id): bool`

Remove uma entidade pelo seu ID.

**Parâmetros:**
- `id`: Identificador da entidade a ser removida.

**Retorno:**
- `true` se a entidade for removida com sucesso, caso contrário, `false`.

**Exemplo de Uso:**
```php
$wasDeleted = $repository->delete('12345');
```

---

### `exists(string $id): bool`

Verifica se um registro com o ID fornecido existe no índice.

**Parâmetros:**
- `id`: O identificador do registro.

**Retorno:**
- `true` se o registro existir, caso contrário, `false`.

**Exemplo de Uso:**
```php
$doesExist = $repository->exists('12345');
```

---

### `parseQuery(array $params): QueryBuilder`

Constrói uma consulta com base nos parâmetros fornecidos.

**Parâmetros:**
- `params`: Parâmetros como `_fields`, `_sort` e filtros diversos.

**Retorno:**
- Instância do `QueryBuilder`.

**Exemplo de Uso:**
```php
$query = $repository->parseQuery(['status' => 'active', '_sort' => 'name:asc']);
```

---

## Exemplo Prático de Uso

Abaixo está um exemplo de implementação de um repositório específico baseado na classe `Repository`:

```php
namespace App\Repositories;

use Hyperf\Context\ApplicationContext;
use Jot\HfRepository\Repository;


class UserRepository extends Repository
{
    protected string $entity = \App\Entities\UserEntity::class;
}

// Exemplo de uso
$repository = new UserRepository(ApplicationContext::getContainer());

// Criando uma entidade
$newUser = new UserEntity(['name' => 'John Doe', 'email' => 'john@example.com']);
$createdUser = $repository->create($newUser);

// Buscando uma entidade
$fetchedUser = $repository->find($createdUser->getId());

// Atualizando uma entidade
$fetchedUser->hydrate(['name' => 'UpdatedName']);
$updatedUser = $repository->update($fetchedUser);

// Listando usuários com paginação
$users = $repository->paginate(['status' => 'active'], 1, 10);
```

---

## Benefícios da Classe

1. **Abstração de Operações**: Fornece uma camada de abstração para o acesso aos dados.
2. **Alinhado ao Elasticsearch**: Trabalha perfeitamente com índices e consultas no Elasticsearch.
3. **Facilidade de Extensão**: Projetado para ser estendido por repositórios específicos.
4. **Integração com Entidades**: Mapeia automaticamente os resultados das consultas para instâncias da classe de entidade correspondente.
