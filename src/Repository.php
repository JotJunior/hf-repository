<?php

namespace Jot\HfRepository;

use Hyperf\Stringable\Str;
use Jot\HfRepository\Adapter\QueryBuilderAdapter;
use Jot\HfRepository\Entity\EntityFactoryInterface;
use Jot\HfRepository\Entity\EntityInterface;
use Jot\HfRepository\Exception\EntityValidationWithErrorsException;
use Jot\HfRepository\Exception\RepositoryCreateException;
use Jot\HfRepository\Exception\RepositoryUpdateException;
use Jot\HfRepository\Query\QueryParserInterface;

/**
 * Abstract Repository class implementing the Repository pattern.
 *
 * This class follows the SOLID principles:
 * - Single Responsibility: Focused on data access operations
 * - Open/Closed: Extensible through inheritance and composition
 * - Liskov Substitution: Subclasses can be used interchangeably
 * - Interface Segregation: Uses specific interfaces for different responsibilities
 * - Dependency Inversion: Depends on abstractions, not concretions
 */
abstract class Repository implements RepositoryInterface
{
    protected string $entity;
    protected string $index;
    protected QueryBuilderAdapter $queryBuilderAdapter;

    public function __construct(
        QueryBuilderAdapter              $queryBuilderAdapter,
        protected QueryParserInterface   $queryParser,
        protected EntityFactoryInterface $entityFactory
    )
    {
        $this->index = $this->getIndexName();
        $this->queryBuilderAdapter = $queryBuilderAdapter;
    }

    /**
     * Retrieves the index name derived from the class name.
     *
     * @return string The index name in snake_case format.
     */
    protected function getIndexName(): string
    {
        $className = explode('\\', get_class($this));
        $indexName = Str::plural(str_replace('Repository', '', end($className)));
        return Str::snake($indexName);
    }

    /**
     * Finds and retrieves an entity by its ID.
     *
     * @param string $id The unique identifier of the entity.
     * @return null|EntityInterface The hydrated entity if found, or null if not found.
     */
    public function find(string $id): ?EntityInterface
    {
        $result = $this->queryBuilderAdapter
            ->select()
            ->from($this->index)
            ->where('id', '=', $id)
            ->execute();

        if ($result['result'] !== 'success' || empty($result['data'][0])) {
            return null;
        }

        return $this->entityFactory->create($this->entity, ['data' => $result['data'][0]]);
    }

    /**
     * Creates a new entity in the repository after validating the provided entity's data.
     *
     * @param EntityInterface $entity The entity instance to be created, which must pass validation.
     * @return EntityInterface The newly created entity instance populated with the resulting data.
     * @throws EntityValidationWithErrorsException If the provided entity fails validation.
     * @throws RepositoryCreateException If an error occurs during the creation process in the repository.
     */
    public function create(EntityInterface $entity): EntityInterface
    {
        $this->validateEntity($entity);

        $result = $this->queryBuilderAdapter
            ->into($this->index)
            ->insert($entity->toArray());

        if ($result['result'] !== 'created') {
            throw new RepositoryCreateException($result['error'] ?? 'Failed to create entity');
        }

        $createdEntity = $this->entityFactory->create($this->entity, ['data' => $result['data']]);

        if (!$createdEntity instanceof EntityInterface) {
            throw new RepositoryCreateException('Failed to create entity instance');
        }

        return $createdEntity;
    }

    /**
     * Validates an entity and throws an exception if validation fails.
     *
     * @param EntityInterface $entity The entity to validate.
     * @throws EntityValidationWithErrorsException If validation fails.
     */
    protected function validateEntity(EntityInterface $entity): void
    {
        if (!$entity->validate()) {
            throw new EntityValidationWithErrorsException($entity->getErrors());
        }
    }

    /**
     * Retrieves and hydrates the first entity matching the provided parameters.
     *
     * @param array $params An associative array of query parameters used to filter the entities.
     * @return null|EntityInterface The hydrated entity instance corresponding to the first match.
     */
    public function first(array $params): ?EntityInterface
    {
        $query = $this->queryParser->parse($params, $this->queryBuilderAdapter->from($this->index));
        $result = $query->limit(1)->execute();

        if ($result['result'] !== 'success' || empty($result['data'][0])) {
            return null;
        }

        return $this->entityFactory->create($this->entity, ['data' => $result['data'][0]]);
    }

    /**
     * Executes a search query based on the provided parameters and maps the results
     * to instances of the specified entity.
     *
     * @param array $params An associative array containing the parameters for the search query.
     * @return array An array of entity instances resulting from the query execution.
     */
    public function search(array $params): array
    {
        $query = $this->queryParser->parse($params, $this->queryBuilderAdapter->from($this->index));
        $result = $query->execute();

        if (empty($result['data'])) {
            return [];
        }

        return array_map(
            fn($item) => $this->entityFactory->create($this->entity, ['data' => $item]),
            $result['data']
        );
    }

    /**
     * Paginates a dataset based on the provided parameters.
     *
     * @param array $params The parameters used to filter or query the dataset.
     * @param int $page The current page number (default is 1).
     * @param int $perPage The number of items to display per page (default is 10).
     * @return array An array containing the paginated results, current page, items per page, and total count.
     */
    public function paginate(array $params, int $page = 1, int $perPage = 10): array
    {
        $page = $params['_page'] ?? $page;
        $perPage = $params['_per_page'] ?? $perPage;

        $query = $this->queryParser->parse($params, $this->queryBuilderAdapter->from($this->index));
        $result = $query
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->execute();

        $entities = [];
        if (!empty($result['data'])) {
            $entities = array_map(
                fn($item) => $this->entityFactory->create($this->entity, ['data' => $item])->toArray(),
                $result['data']
            );
        }

        $result['data'] = $entities;

        return [
            ...$result,
            'current_page' => (int)$page,
            'per_page' => (int)$perPage,
            'total' => $this->queryParser->parse($params, $this->queryBuilderAdapter->from($this->index))->count()
        ];
    }

    /**
     * Updates an existing entity in the repository and returns the updated entity.
     *
     * @param EntityInterface $entity The entity to update, containing its identifier and updated data.
     * @return EntityInterface The updated entity after successful modification.
     * @throws EntityValidationWithErrorsException If the provided entity fails validation.
     * @throws RepositoryUpdateException If the update operation fails or encounters an error.
     */
    public function update(EntityInterface $entity): EntityInterface
    {
        $this->validateEntity($entity);

        $result = $this->queryBuilderAdapter
            ->from($this->index)
            ->update($entity->getId(), $entity->toArray());

        if (!in_array($result['result'], ['updated', 'noop'])) {
            throw new RepositoryUpdateException($result['error'] ?? 'Failed to update entity');
        }

        return $this->entityFactory->create($this->entity, ['data' => $result['data']]);
    }

    /**
     * Deletes a record identified by the given ID from the index.
     *
     * @param string $id The unique identifier of the record to be deleted.
     * @return bool True if the record was successfully deleted, false otherwise.
     */
    public function delete(string $id): bool
    {
        $result = $this->queryBuilderAdapter->from($this->index)->delete($id);
        return in_array($result['result'], ['deleted', 'updated', 'noop']);
    }

    /**
     * Checks if a record with the specified identifier exists in the database.
     *
     * @param string $id The unique identifier of the record to check for existence.
     * @return bool True if the record exists, false otherwise.
     */
    public function exists(string $id): bool
    {
        return $this->queryBuilderAdapter
                ->select()
                ->from($this->index)
                ->where('id', '=', $id)
                ->count() > 0;
    }
}