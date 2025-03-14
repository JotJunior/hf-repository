<?php

declare(strict_types=1);

namespace Jot\HfRepository\Adapter;

use Hyperf\Stringable\Str;
use Jot\HfElastic\Contracts\ElasticRepositoryInterface;
use Jot\HfElastic\Contracts\QueryBuilderInterface;

/**
 * Adapter for Elasticsearch repository operations.
 * Implements the RepositoryAdapterInterface using the ElasticRepository.
 */
class ElasticRepositoryAdapter implements RepositoryAdapterInterface
{
    /**
     * @param ElasticRepositoryInterface $repository The Elasticsearch repository.
     * @param QueryBuilderInterface $queryBuilder The query builder for Elasticsearch.
     */
    public function __construct(
        private readonly ElasticRepositoryInterface $repository,
        private readonly QueryBuilderInterface      $queryBuilder
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function insert(string $index, array $data): array
    {
        $this->repository->setIndex($index);

        // Add required fields if not present
        if (!isset($data['id'])) {
            $data['id'] = Str::uuid()->toString();
        }

        return $this->repository->insert($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $index, string $id, array $data): array
    {
        $this->repository->setIndex($index);

        // Ensure ID is not changed
        unset($data['id']);

        return $this->repository->update($id, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $index, string $id, bool $logicalDeletion = true): array
    {
        $this->repository->setIndex($index);
        return $this->repository->delete($id, $logicalDeletion);
    }

    /**
     * {@inheritdoc}
     */
    public function count(array $query): int
    {
        // Reset the query builder to ensure a clean state
        $this->queryBuilder->from($query['index']);

        // Apply all conditions from the query
        if (!empty($query['conditions'])) {
            foreach ($query['conditions'] as $condition) {
                $field = $condition['field'];
                $operator = $condition['operator'];
                $value = $condition['value'];
                $context = $condition['context'] ?? 'must';

                $this->queryBuilder->where($field, $operator, $value, $context);
            }
        }

        // Count the documents
        return $this->queryBuilder->count();
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $index, string $id): bool
    {
        $result = $this->find($index, $id);
        return $result['result'] === 'success' && !empty($result['data']);
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $index, string $id): array
    {
        $this->repository->setIndex($index);

        $result = $this->queryBuilder
            ->select()
            ->from($index)
            ->where('id', '=', $id)
            ->where('deleted', '=', false)
            ->execute();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $query): array
    {
        // Reset the query builder to ensure a clean state
        $this->queryBuilder->from($query['index']);

        // Apply all conditions from the query
        if (!empty($query['conditions'])) {
            foreach ($query['conditions'] as $condition) {
                $field = $condition['field'];
                $operator = $condition['operator'];
                $value = $condition['value'];
                $context = $condition['context'] ?? 'must';

                $this->queryBuilder->where($field, $operator, $value, $context);
            }
        }

        // Apply limit if specified
        if (isset($query['limit'])) {
            $this->queryBuilder->limit($query['limit']);
        }

        // Apply offset if specified
        if (isset($query['offset'])) {
            $this->queryBuilder->offset($query['offset']);
        }

        // Apply sort if specified
        if (!empty($query['sort'])) {
            foreach ($query['sort'] as $sort) {
                $this->queryBuilder->orderBy($sort['field'], $sort['order'] ?? 'asc');
            }
        }

        // Execute the query
        return $this->queryBuilder->execute();
    }
}
