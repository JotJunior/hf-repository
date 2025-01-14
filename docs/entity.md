# Classe `Entity`

A classe `Entity` implementa uma estrutura abstrata que serve como base para entidades no projeto. Ela oferece métodos para visualização e manipulação de propriedades da entidade, bem como funções para conversão e hidratação de seus dados.

---

## Namespace

```php
namespace Jot\HfRepository;
```

---

## Descrição

Esta classe fornece as seguintes funcionalidades principais:

- **Manipulação de Propriedades**: Permite visualizar e modificar propriedades da entidade.
- **Conversão para Array**: Gera uma representação associativa da entidade.
- **Hidratação Automática**: Popula os dados da entidade a partir de um array.
- **Clonagem de Instâncias**: Permite criar cópias do objeto.

---

## Métodos

### `__construct(array $data = [])`

Construtor da classe. Aceita um array associativo para inicializar as propriedades da entidade.

**Parâmetros:**
- `data`: Um array associativo com os dados para inicializar a entidade.

**Exemplo de Uso:**

```php
$data = [
    'id' => '5678',
    'name' => 'Jane Doe',
];

$entity = new UserEntity($data);
echo $entity->getId(); // Saída: 5678
```

---

### `getId(): ?string`

Retorna o ID da entidade, ou `null` caso o ID não esteja definido.

**Exemplo de Uso:**

```php
$entity = new UserEntity(['id' => 'abcd1234']);
echo $entity->getId(); // Saída: abcd1234
```

---

### `hydrate(array $data): self`

Popula as propriedades da entidade com dados de um array associativo. Converte as chaves de `snake_case` para `camelCase` automaticamente. Além disso, detecta e instancia objetos relacionados a partir de anotações (como `@var` ou OpenAPI).

**Parâmetros:**
- `data` - Array associativo com as chaves representando nomes de propriedades em `snake_case` e valores correspondentes.

**Exemplo de Uso:**

```php
$data = [
    'first_name' => 'Jane',
    'email_address' => 'jane.doe@example.com',
];

$entity = new UserEntity();
$entity->hydrate($data);

echo $entity->toArray()['first_name']; // Saída: Jane
```

---

### `toArray(): array`

Converte a entidade em um array associativo. Caso uma propriedade seja um objeto (e possua o método `toArray`), ele será chamado de forma recursiva. Para propriedades do tipo `DateTime`, sua instância será formatada como string no formato `DATE_ATOM`.

**Retorno:**
- Array associativo com as propriedades e seus valores correspondentes.

**Exemplo de Uso:**

```php
$data = [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'created_at' => new DateTime('2023-10-01T00:00:00Z'),
];

$entity = new UserEntity($data);
print_r($entity->toArray());

// Saída:
[
    'first_name' => 'John',
    'last_name' => 'Doe',
    'created_at' => '2023-10-01T00:00:00+00:00',
];
```

---

### `clone(): self`

Cria uma cópia idêntica (clonando) do objeto atual.

**Exemplo de Uso:**

```php
$clonedEntity = $entity->clone();
print_r($clonedEntity->toArray());
```

---

## Métodos Privados Auxiliares

### `extractVariables(mixed $value): mixed`

Este método processa valores internamente:
- Se o valor for um objeto e implementar o método `toArray()`, ele será chamado.
- Se for uma instância de `DateTime`, retorna uma string formatada no padrão `DATE_ATOM`.
- Caso contrário, retorna o próprio valor.

---

### `propertyExistsInEntity(string $property): bool`

Valida se uma propriedade existe dentro do escopo da entidade, considerando tanto herança quanto `traits`.

**Parâmetros:**
- `property` - Nome da propriedade a ser verificada.

**Retorno:**
- `true` se a propriedade existir, ou `false` caso contrário.

**Exemplo de Uso:**

```php
$exists = $this->propertyExistsInEntity('id');
echo $exists ? 'Propriedade existe.' : 'Propriedade não encontrada.';
// Saída: Propriedade existe.
```

---

### `getAllProperties(\ReflectionClass $reflection): array`

Obtém todas as propriedades pertencentes à classe, incluindo heranças de **traits**.

**Retorno:**
- Um array de objetos do tipo `ReflectionProperty`.

---

## Exemplo Prático de Uso

Abaixo está um exemplo funcional de como criar e manipular uma entidade que estende a classe abstrata `Entity`:

```php
<?php

namespace App\Entities;

use Jot\HfRepository\Entity;

class UserEntity extends Entity
{
    protected ?string $name = null;
    protected ?string $email = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }
}

// Criando e manipulando uma entidade
$data = [
    'name' => 'Alice',
    'email' => 'alice@example.com',
];

$user = new UserEntity($data);
echo $user->getName(); // Saída: Alice
echo $user->getEmail(); // Saída: alice@example.com

print_r($user->toArray());
// Saída:
// [
//     'name' => 'Alice',
//     'email' => 'alice@example.com',
// ]
```

---

## Benefícios da Classe

1. **Reutilização de Código**: A classe `Entity` serve como base para diversas entidades, permitindo reutilizar funcionalidades comuns.
2. **Hidratação Automática**: O método `hydrate` facilita o preenchimento das propriedades de maneira dinâmica.
3. **Conversão para Array**: A funcionalidade de serializar o objeto para um array facilita integrações com APIs ou sistemas externos.
4. **Flexibilidade para Extensão**: Pode ser facilmente estendida para atender às necessidades de projetos complexos.

