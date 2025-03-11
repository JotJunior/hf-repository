<?php

declare(strict_types=1);

namespace Jot\HfRepository\Adapter;

use Jot\HfElastic\Contracts\QueryBuilderInterface;

/**
 * Adapter class for QueryBuilder
 * This allows us to isolate changes in the QueryBuilder API and maintain compatibility
 */
class QueryBuilderAdapter
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    public function into(string $index): self
    {
        $this->queryBuilder->into($index);
        return $this;
    }

    public function from(string $index): self
    {
        $this->queryBuilder->from($index);
        return $this;
    }

    public function insert(array $data): array
    {
        return $this->queryBuilder->insert($data);
    }

    public function update(string $id, array $data): array
    {
        return $this->queryBuilder->update($id, $data);
    }

    public function delete(string $id): array
    {
        return $this->queryBuilder->delete($id);
    }

    public function select(): self
    {
        $this->queryBuilder->select();
        return $this;
    }

    public function where(string $field, string $operator, mixed $value): self
    {
        $this->queryBuilder->where($field, $operator, $value);
        return $this;
    }

    public function execute(): array
    {
        return $this->queryBuilder->execute();
    }

    public function limit(int $limit): self
    {
        $this->queryBuilder->limit($limit);
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->queryBuilder->offset($offset);
        return $this;
    }

    public function count(): int
    {
        return $this->queryBuilder->count();
    }

    /**
     * Get the underlying QueryBuilder instance
     */
    public function getQueryBuilder(): QueryBuilderInterface
    {
        return $this->queryBuilder;
    }
}
