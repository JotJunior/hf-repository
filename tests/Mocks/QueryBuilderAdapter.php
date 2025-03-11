<?php

declare(strict_types=1);

namespace Jot\HfRepository\Tests\Mocks;

use Jot\HfElastic\QueryBuilder;

/**
 * Adapter class for QueryBuilder to use in tests
 * This allows us to mock the methods that might have changed in the QueryBuilder API
 */
class QueryBuilderAdapter
{
    public function __construct(private QueryBuilder $queryBuilder)
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
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }
}
