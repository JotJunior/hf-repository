<?php

declare(strict_types=1);

namespace Jot\HfRepository\Adapter;

/**
 * Adapter class for QueryBuilder
 * This allows us to isolate changes in the QueryBuilder API and maintain compatibility
 * while enabling swapping different database implementations.
 */
class QueryBuilderAdapter
{
    /**
     * @var array The query being built
     */
    private array $query = [];
    
    /**
     * @var string|null The current index
     */
    private ?string $index = null;
    
    /**
     * @var array Conditions for the query
     */
    private array $conditions = [];
    
    /**
     * @var int|null Limit for the query
     */
    private ?int $limit = null;
    
    /**
     * @var int|null Offset for the query
     */
    private ?int $offset = null;
    
    /**
     * @var array Sort criteria for the query
     */
    private array $sort = [];
    
    /**
     * @var array Fields to select
     */
    private array $fields = [];
    
    /**
     * @var RepositoryAdapterInterface The repository adapter for executing queries
     */
    private RepositoryAdapterInterface $repositoryAdapter;
    
    /**
     * @param RepositoryAdapterInterface $repositoryAdapter The repository adapter
     */
    public function __construct(RepositoryAdapterInterface $repositoryAdapter)
    {
        $this->repositoryAdapter = $repositoryAdapter;
        $this->resetQuery();
    }
    
    /**
     * Reset the query to its initial state
     */
    private function resetQuery(): void
    {
        $this->query = [];
        $this->index = null;
        $this->conditions = [];
        $this->limit = null;
        $this->offset = null;
        $this->sort = [];
        $this->fields = [];
    }
    
    /**
     * Set the index for insertion operations
     */
    public function into(string $index): self
    {
        $this->from($index);
        return $this;
    }
    
    /**
     * Set the index for query operations
     */
    public function from(string $index): self
    {
        $this->index = $index;
        return $this;
    }
    
    /**
     * Insert data into the index
     */
    public function insert(array $data): array
    {
        if (!$this->index) {
            throw new \InvalidArgumentException('Index must be set before inserting data');
        }
        
        $result = $this->repositoryAdapter->insert($this->index, $data);
        $this->resetQuery();
        return $result;
    }
    
    /**
     * Update a document by ID
     */
    public function update(string $id, array $data): array
    {
        if (!$this->index) {
            throw new \InvalidArgumentException('Index must be set before updating data');
        }
        
        $result = $this->repositoryAdapter->update($this->index, $id, $data);
        $this->resetQuery();
        return $result;
    }
    
    /**
     * Delete a document by ID
     */
    public function delete(string $id): array
    {
        if (!$this->index) {
            throw new \InvalidArgumentException('Index must be set before deleting data');
        }
        
        $result = $this->repositoryAdapter->delete($this->index, $id);
        $this->resetQuery();
        return $result;
    }
    
    /**
     * Set the query to select mode
     */
    public function select(array $fields = []): self
    {
        $this->fields = $fields;
        return $this;
    }
    
    /**
     * Add a where condition to the query
     */
    public function where(string $field, string $operator, mixed $value): self
    {
        $this->conditions[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
            'context' => 'must',
        ];
        
        return $this;
    }
    
    /**
     * Execute the query and return the results
     */
    public function execute(): array
    {
        if (!$this->index) {
            throw new \InvalidArgumentException('Index must be set before executing query');
        }
        
        $query = $this->buildQuery();
        $result = $this->repositoryAdapter->execute($query);
        $this->resetQuery();
        return $result;
    }
    
    /**
     * Set the limit for the query
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }
    
    /**
     * Set the offset for the query
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }
    
    /**
     * Count the number of documents matching the query
     */
    public function count(): int
    {
        if (!$this->index) {
            throw new \InvalidArgumentException('Index must be set before counting');
        }
        
        $query = $this->buildQuery();
        $count = $this->repositoryAdapter->count($query);
        $this->resetQuery();
        return $count;
    }
    
    /**
     * Build the query from the current state
     */
    private function buildQuery(): array
    {
        $query = [
            'index' => $this->index,
            'conditions' => $this->conditions,
        ];
        
        if (!empty($this->fields)) {
            $query['fields'] = $this->fields;
        }
        
        if ($this->limit !== null) {
            $query['limit'] = $this->limit;
        }
        
        if ($this->offset !== null) {
            $query['offset'] = $this->offset;
        }
        
        if (!empty($this->sort)) {
            $query['sort'] = $this->sort;
        }
        
        return $query;
    }
}
