<?php

declare(strict_types=1);
/**
 * This file is part of the hf_repository module, a package build for Hyperf framework that is responsible for
 * manage controllers, entities and repositories.
 *
 * @author   Joao Zanon <jot@jot.com.br>
 * @link     https://github.com/JotJunior/hf-repository
 * @license  MIT
 */

namespace Jot\HfRepository;

use Hyperf\Cache\Annotation\Cacheable;
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

abstract class Repository implements RepositoryInterface
{
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
     * @param string $id the unique identifier of the entity
     * @return null|EntityInterface the hydrated entity if found, or null if not found
     */
    public function find(string $id): ?EntityInterface
    {
        $result = $this->queryBuilder
            ->select()
            ->from($this->index)
            ->where('id', $id)
            ->execute();

        if ($result['result'] !== 'success' || empty($result['data'][0])) {
            return null;
        }

        return $this->entityFactory->create($this->entity, $result['data'][0]);
    }

    /**
     * Creates a new entity in the repository after validating the provided entity's data.
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

        if (! $createdEntity instanceof EntityInterface) {
            $message = __('hf-repository.failed_create_entity_instance');
            throw new RepositoryCreateException($message);
        }

        return $createdEntity;
    }

    /**
     * Retrieves and hydrates the first entity matching the provided parameters.
     * @param array $params an associative array of query parameters used to filter the entities
     * @return null|EntityInterface the hydrated entity instance corresponding to the first match
     * @throws ReflectionException
     */
    public function first(array $params): ?EntityInterface
    {
        $query = $this->queryParser->parse($params, $this->queryBuilder->from($this->index));
        $result = $query->limit(1)->execute();

        if ($result['result'] !== 'success' || empty($result['data'][0])) {
            return null;
        }

        return $this->entityFactory->create($this->entity, $result['data'][0]);
    }

    /**
     * Performs an autocomplete operation based on the provided keyword and searchable fields.
     * Generates additional gram fields for more accurate autocomplete matches.
     * @param string $keyword the term to be autocompleted
     * @param array $searchable The list of fields to search against. Defaults to ['name'].
     * @param array $params additional parameters for customizing the query
     * @return array an array of autocomplete results
     */
    public function autocomplete(string $keyword, array $searchable = ['name'], array $params = []): array
    {
        $fields = [];
        foreach ($searchable as $field) {
            $fields[] = $field . '.search';
            $fields[] = $field . '.search._2gram';
            $fields[] = $field . '.search._3gram';
        }

        $this->queryParser->parse($params, $this->queryBuilder->from($this->index));

        return $this->queryBuilder
            ->select(['id', ...$searchable])
            ->from($this->index)
            ->autocomplete($keyword, $fields)
            ->execute();
    }

    /**
     * Paginates a dataset based on the provided parameters.
     * @param array $params the parameters used to filter or query the dataset
     * @param int $page the current page number (default is 1)
     * @param int $perPage the number of items to display per page (default is 10)
     * @return array an array containing the paginated results, current page, items per page, and total count
     * @throws ReflectionException
     */
    public function paginate(array $params, int $page = 1, int $perPage = 10): array
    {
        $page = $params['_page'] ?? $page;
        $perPage = $params['_per_page'] ?? $perPage;

        if (! empty($params['search'])) {
            $search = $params['search'];
            unset($params['search']);
        }

        $query = $this->queryParser->parse($params, $this->queryBuilder->from($this->index));

        if (! empty($search) && ! empty($this->entity::SEARCHABLE)) {
            $query->search($search, $this->entity::SEARCHABLE);
        }

        $result = $query
            ->limit((int) $perPage)
            ->offset(($page - 1) * $perPage)
            ->execute();

        $entities = [];
        if (! empty($result['data'])) {
            $entities = array_map(
                function ($item) {
                    $entity = $this->entityFactory->create($this->entity, $item);

                    return $entity->toArray();
                },
                $result['data']
            );
        }

        $result['data'] = $entities;

        return [
            ...$result,
            'current_page' => (int) $page,
            'per_page' => (int) $perPage,
            'total' => $this->queryParser->parse($params, $this->queryBuilder->from($this->index))->count(),
        ];
    }

    /**
     * Executes a search operation on the specified index using the provided keyword and searchable criteria.
     * @param string $keyword the search term to be used in the query
     * @param array $searchable the fields or criteria to search against
     * @param array|string $fields The fields to be selected in the search result. Default is '*'.
     * @param array $params additional parameters to configure the query execution
     * @return array An array of results where each result is mapped to an entity instance. Returns an empty array if no data is found.
     */
    public function search(string $keyword, array $searchable, array|string $fields = '*', array $params = []): array
    {
        $this->queryParser->parse($params, $this->queryBuilder->from($this->index));

        $result = $this->queryBuilder
            ->select($fields)
            ->from($this->index)
            ->search($keyword, $searchable)
            ->execute();

        if (empty($result['data'])) {
            return [];
        }

        return array_map(
            function ($item) {
                return $this->entityFactory->create($this->entity, $item);
            },
            $result['data']
        );
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

        if (! in_array($result['result'], ['updated', 'noop'])) {
            $message = __('hf-repository.failed_update_entity');
            throw new RepositoryUpdateException($result['error'] ?? $message);
        }

        return $this->entityFactory->create($this->entity, $result['data']);
    }

    /**
     * Deletes a record identified by the given ID from the index.
     * @param string $id the unique identifier of the record to be deleted
     * @return null|array true if the record was successfully deleted, false otherwise
     */
    public function delete(string $id): ?array
    {
        return $this->queryBuilder->from($this->index)->delete($id);
    }

    /**
     * Checks if a record with the specified identifier exists in the database.
     * @param string $id the unique identifier of the record to check for existence
     * @return bool true if the record exists, false otherwise
     */
    public function exists(string $id): bool
    {
        return $this->queryBuilder
            ->from($this->index)
            ->exists($id);
    }

    /**
     * Fetches an entity reference from the specified index based on the provided ID and selected fields.
     * @param string $index the name of the index from which the entity reference is to be retrieved
     * @param string $id the unique identifier of the entity to fetch
     * @param array $fields the fields to include in the result. Defaults to ['id', 'name'].
     * @return array an array containing the entity reference data fetched from the index
     */
    #[Cacheable(prefix: 'entity:reference', value: '{index}:{id}', ttl: 60)]
    public function fetchEntityReference(string $index, string $id, array $fields = ['id', 'name']): array
    {
        return $this->queryBuilder
            ->select($fields)
            ->from($index)
            ->where('id', $id)
            ->execute();
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
     * Validates an entity and throws an exception if validation fails.
     * @param EntityInterface $entity the entity to validate
     * @throws EntityValidationWithErrorsException if validation fails
     */
    protected function validateEntity(EntityInterface $entity): void
    {
        $entity->validate();
        if ($entity->getErrors()) {
            throw new EntityValidationWithErrorsException($entity->getErrors());
        }
    }
}
