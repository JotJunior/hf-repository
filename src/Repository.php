<?php

declare(strict_types=1);
/**
 * This file is part of hf-repository
 *
 * @link     https://github.com/JotJunior/hf-repository
 * @contact  hf-repository@jot.com.br
 * @license  MIT
 */

namespace Jot\HfRepository;

use Hyperf\Context\Context;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Stringable\Str;
use Jot\HfElastic\Contracts\QueryBuilderInterface;
use Jot\HfRepository\Entity\EntityFactoryInterface;
use Jot\HfRepository\Entity\EntityInterface;
use Jot\HfRepository\Exception\EntityValidationWithErrorsException;
use Jot\HfRepository\Exception\RepositoryCreateException;
use Jot\HfRepository\Exception\RepositoryUpdateException;
use Jot\HfRepository\Query\QueryParserInterface;
use ReflectionException;
use function Hyperf\Translation\__;

/**
 * Abstract Repository class implementing the Repository pattern.
 * This class follows the SOLID principles:
 * - Single Responsibility: Focused on data access operations
 * - Open/Closed: Extensible through inheritance and composition
 * - Liskov Substitution: Subclasses can be used interchangeably
 * - Interface Segregation: Uses specific interfaces for different responsibilities
 * - Dependency Inversion: Depends on abstractions, not concretions
 * Optimized for Swoole/Hyperf environment with coroutine safety:
 * - Uses Context isolation for concurrent requests
 * - Implements proper dependency injection
 * - Avoids static properties for coroutine safety
 * - Handles serialization for coroutine scheduling.
 */
abstract class Repository implements RepositoryInterface
{
    /**
     * Context key prefix for repository instances.
     */
    private const CONTEXT_REPOSITORY = 'repository.instance.';

    /**
     * @Inject
     */
    protected ContainerInterface $container;

    /**
     * Entity class name to be instantiated by this repository.
     */
    protected string $entity;

    /**
     * Index name for storage operations.
     */
    protected string $index;

    #[Inject]
    protected QueryBuilderInterface $queryBuilder;

    #[Inject]
    protected QueryParserInterface $queryParser;

    #[Inject]
    protected EntityFactoryInterface $entityFactory;

    public function __construct()
    {
        $this->index = $this->getIndexName();
    }

    /**
     * Retrieves the index name derived from the class name.
     * @return string the index name in snake_case format
     */
    protected function getIndexName(): string
    {
        $className = explode('\\', get_class($this));
        $indexName = Str::plural(str_replace('Repository', '', end($className)));
        return Str::snake($indexName);
    }

    /**
     * Magic method to handle serialization for coroutine scheduling.
     * Ensures that non-serializable properties are properly handled.
     */
    public function __sleep(): array
    {
        $properties = get_object_vars($this);

        // Remove container and other non-serializable properties
        unset($properties['container']);

        return array_keys($properties);
    }

    /**
     * Magic method to handle unserialization after coroutine scheduling.
     * Restores container and other dependencies.
     */
    public function __wakeup(): void
    {
        // Container will be re-injected by Hyperf's dependency injection
    }

    /**
     * Creates a deep clone of the repository, ensuring all nested objects are cloned.
     * Important for coroutine safety to prevent shared references.
     */
    public function __clone()
    {
        // Deep clone any object properties to prevent shared references
        foreach (get_object_vars($this) as $key => $value) {
            if (is_object($value)) {
                $this->{$key} = clone $value;
            }
        }
    }

    /**
     * Finds and retrieves an entity by its ID.
     * Uses Context to store query results for the current coroutine.
     *
     * @param string $id the unique identifier of the entity
     * @return null|EntityInterface the hydrated entity if found, or null if not found
     */
    public function find(string $id): ?EntityInterface
    {
        $contextKey = self::CONTEXT_REPOSITORY . 'find.' . $this->index . '.' . $id;

        // Check if we already have this entity in the current coroutine context
        $cachedEntity = Context::get($contextKey);
        if ($cachedEntity !== null) {
            return $cachedEntity;
        }

        $result = $this->queryBuilder
            ->select()
            ->from($this->index)
            ->where('id', $id)
            ->execute();

        if ($result['result'] !== 'success' || empty($result['data'][0])) {
            // Store null result in context to avoid repeated lookups
            Context::set($contextKey, null);
            return null;
        }

        $entity = $this->entityFactory->create($this->entity, $result['data'][0]);

        // Store in context for this coroutine
        Context::set($contextKey, $entity);

        return $entity;
    }

    /**
     * Creates a new entity in the repository after validating the provided entity's data.
     * Optimized for Swoole/Hyperf with coroutine safety.
     * @param EntityInterface $entity the entity instance to be created, which must pass validation
     * @return EntityInterface the newly created entity instance populated with the resulting data
     * @throws EntityValidationWithErrorsException
     * @throws RepositoryCreateException
     * @throws ReflectionException
     */
    public function create(EntityInterface $entity): EntityInterface
    {
        $this->validateEntity($entity);

        $result = $this->queryBuilder
            ->into($this->index)
            ->insert($entity->toArray());

        if ($result['result'] !== 'created') {
            $message = __('hf-repository.failed_create_entity');
            throw new RepositoryCreateException($result['error'] ?? $message);
        }

        $createdEntity = $this->entityFactory->create($this->entity, $result['data']);

        if (!$createdEntity instanceof EntityInterface) {
            $message = __('hf-repository.failed_create_entity_instance');
            throw new RepositoryCreateException($message);
        }

        // Store in context for this coroutine if it has an ID
        if (method_exists($createdEntity, 'getId') && $createdEntity->getId()) {
            $contextKey = self::CONTEXT_REPOSITORY . 'find.' . $this->index . '.' . $createdEntity->getId();
            Context::set($contextKey, $createdEntity);
        }

        return $createdEntity;
    }

    /**
     * Validates an entity and throws an exception if validation fails.
     * @param EntityInterface $entity the entity to validate
     * @throws EntityValidationWithErrorsException if validation fails
     */
    protected function validateEntity(EntityInterface $entity): void
    {
        if (!$entity->validate()) {
            throw new EntityValidationWithErrorsException($entity->getErrors());
        }
    }

    /**
     * Retrieves and hydrates the first entity matching the provided parameters.
     * Uses Context to store query results for the current coroutine.
     * @param array $params an associative array of query parameters used to filter the entities
     * @return null|EntityInterface the hydrated entity instance corresponding to the first match
     * @throws ReflectionException
     */
    public function first(array $params): ?EntityInterface
    {
        // Create a unique context key based on the parameters
        $contextKey = self::CONTEXT_REPOSITORY . 'first.' . $this->index . '.' . md5(serialize($params));

        // Check if we already have this result in the current coroutine context
        $cachedEntity = Context::get($contextKey);
        if ($cachedEntity !== null) {
            return $cachedEntity;
        }

        $query = $this->queryParser->parse($params, $this->queryBuilder->from($this->index));
        $result = $query->limit(1)->execute();

        if ($result['result'] !== 'success' || empty($result['data'][0])) {
            // Store null result in context to avoid repeated lookups
            Context::set($contextKey, null);
            return null;
        }

        $entity = $this->entityFactory->create($this->entity, $result['data'][0]);

        // Store in context for this coroutine
        Context::set($contextKey, $entity);

        // Also store by ID for potential future find() calls
        if (method_exists($entity, 'getId') && $entity->getId()) {
            $idContextKey = self::CONTEXT_REPOSITORY . 'find.' . $this->index . '.' . $entity->getId();
            Context::set($idContextKey, $entity);
        }

        return $entity;
    }

    /**
     * Executes a search query based on the provided parameters and maps the results
     * to instances of the specified entity.
     * Optimized for Swoole/Hyperf with coroutine safety.
     * @param array $params an associative array containing the parameters for the search query
     * @return array an array of entity instances resulting from the query execution
     * @throws ReflectionException
     */
    public function search(array $params): array
    {
        // Create a unique context key based on the parameters
        $contextKey = self::CONTEXT_REPOSITORY . 'search.' . $this->index . '.' . md5(serialize($params));

        // Check if we already have this result in the current coroutine context
        $cachedResults = Context::get($contextKey);
        if ($cachedResults !== null) {
            return $cachedResults;
        }

        $query = $this->queryParser->parse($params, $this->queryBuilder->from($this->index));
        $result = $query->execute();

        if (empty($result['data'])) {
            // Store empty array in context to avoid repeated lookups
            Context::set($contextKey, []);
            return [];
        }

        $entities = array_map(
            function ($item) {
                $entity = $this->entityFactory->create($this->entity, $item);

                // Also store individual entities by ID for potential future find() calls
                if (method_exists($entity, 'getId') && $entity->getId()) {
                    $idContextKey = self::CONTEXT_REPOSITORY . 'find.' . $this->index . '.' . $entity->getId();
                    Context::set($idContextKey, $entity);
                }

                return $entity;
            },
            $result['data']
        );

        // Store in context for this coroutine
        Context::set($contextKey, $entities);

        return $entities;
    }

    /**
     * Paginates a dataset based on the provided parameters.
     * Optimized for Swoole/Hyperf with coroutine safety.
     * @param array $params the parameters used to filter or query the dataset
     * @param int $page the current page number (default is 1)
     * @param int $perPage the number of items to display per page (default is 10)
     * @return array an array containing the paginated results, current page, items per page, and total count
     * @throws ReflectionException
     */
    public function paginate(array $params, int $page = 1, int $perPage = 10): array
    {
        // Create a unique context key based on the parameters and pagination info
        $paginationInfo = ['page' => $page, 'perPage' => $perPage];
        $contextKey = self::CONTEXT_REPOSITORY . 'paginate.' . $this->index . '.'
            . md5(serialize($params) . serialize($paginationInfo));

        // Check if we already have this result in the current coroutine context
        $cachedResults = Context::get($contextKey);
        if ($cachedResults !== null) {
            return $cachedResults;
        }

        $page = $params['_page'] ?? $page;
        $perPage = $params['_per_page'] ?? $perPage;

        $query = $this->queryParser->parse($params, $this->queryBuilder->from($this->index));
        $result = $query
            ->limit((int)$perPage)
            ->offset(($page - 1) * $perPage)
            ->execute();

        $entities = [];
        if (!empty($result['data'])) {
            $entities = array_map(
                function ($item) {
                    $entity = $this->entityFactory->create($this->entity, $item);

                    // Also store individual entities by ID for potential future find() calls
                    if (method_exists($entity, 'getId') && $entity->getId()) {
                        $idContextKey = self::CONTEXT_REPOSITORY . 'find.' . $this->index . '.' . $entity->getId();
                        Context::set($idContextKey, $entity);
                    }

                    return $entity->toArray();
                },
                $result['data']
            );
        }

        $result['data'] = $entities;

        $paginatedResult = [
            ...$result,
            'current_page' => (int)$page,
            'per_page' => (int)$perPage,
            'total' => $this->queryParser->parse($params, $this->queryBuilder->from($this->index))->count(),
        ];

        // Store in context for this coroutine
        Context::set($contextKey, $paginatedResult);

        return $paginatedResult;
    }

    /**
     * Updates an existing entity in the repository and returns the updated entity.
     * Optimized for Swoole/Hyperf with coroutine safety.
     * @param EntityInterface $entity the entity to update, containing its identifier and updated data
     * @return EntityInterface the updated entity after successful modification
     * @throws EntityValidationWithErrorsException if the provided entity fails validation
     * @throws RepositoryUpdateException if the update operation fails or encounters an error
     * @throws EntityValidationWithErrorsException
     * @throws ReflectionException
     */
    public function update(EntityInterface $entity): EntityInterface
    {
        $this->validateEntity($entity);

        $result = $this->queryBuilder
            ->from($this->index)
            ->update($entity->getId(), $entity->toArray());

        if (!in_array($result['result'], ['updated', 'noop'])) {
            $message = __('hf-repository.failed_update_entity');
            throw new RepositoryUpdateException($result['error'] ?? $message);
        }

        $updatedEntity = $this->entityFactory->create($this->entity, $result['data']);

        // Update entity in context for this coroutine
        if (method_exists($updatedEntity, 'getId') && $updatedEntity->getId()) {
            $contextKey = self::CONTEXT_REPOSITORY . 'find.' . $this->index . '.' . $updatedEntity->getId();
            Context::set($contextKey, $updatedEntity);

            // Also invalidate any cached search or paginate results that might contain this entity
            $this->invalidateContextCache();
        }

        return $updatedEntity;
    }

    /**
     * Invalidates cached search and paginate results in the current coroutine context.
     * This is called after operations that modify data to ensure consistency.
     */
    protected function invalidateContextCache(): void
    {
        // Get all context keys for this coroutine
        $contextKeys = Context::getContainer();

        if (!is_array($contextKeys)) {
            return;
        }

        // Find and remove search and paginate cache entries for this repository
        $searchPrefix = self::CONTEXT_REPOSITORY . 'search.' . $this->index . '.';
        $paginatePrefix = self::CONTEXT_REPOSITORY . 'paginate.' . $this->index . '.';
        $firstPrefix = self::CONTEXT_REPOSITORY . 'first.' . $this->index . '.';

        foreach ($contextKeys as $key => $value) {
            if (str_starts_with($key, $searchPrefix)
                || str_starts_with($key, $paginatePrefix)
                || str_starts_with($key, $firstPrefix)) {
                Context::set($key, null);
            }
        }
    }

    /**
     * Deletes a record identified by the given ID from the index.
     * Optimized for Swoole/Hyperf with coroutine safety.
     * @param string $id the unique identifier of the record to be deleted
     * @return bool true if the record was successfully deleted, false otherwise
     */
    public function delete(string $id): bool
    {
        $result = $this->queryBuilder->from($this->index)->delete($id);
        $success = in_array($result['result'], ['deleted', 'updated', 'noop']);

        if ($success) {
            // Remove entity from context
            $contextKey = self::CONTEXT_REPOSITORY . 'find.' . $this->index . '.' . $id;
            Context::set($contextKey, null);

            // Also invalidate any cached search or paginate results that might contain this entity
            $this->invalidateContextCache();
        }

        return $success;
    }

    /**
     * Checks if a record with the specified identifier exists in the database.
     * Optimized for Swoole/Hyperf with coroutine safety.
     * @param string $id the unique identifier of the record to check for existence
     * @return bool true if the record exists, false otherwise
     */
    public function exists(string $id): bool
    {
        return $this->queryBuilder
            ->from($this->index)
            ->exists($id);
    }
}
