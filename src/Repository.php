<?php

namespace Jot\HfRepository;

use Hyperf\Stringable\Str;
use Jot\HfElastic\QueryBuilder;
use Jot\HfRepository\Exception\RepositoryCreateException;
use Jot\HfRepository\Exception\RepositoryUpdateException;
use Psr\Container\ContainerInterface;
use function Hyperf\Support\make;


abstract class Repository implements RepositoryInterface
{

    protected string $entity;
    protected string $index;
    protected QueryBuilder $queryBuilder;

    public function __construct(ContainerInterface $container)
    {
        $this->index = $this->getIndexName();
        $this->queryBuilder = $container->get(QueryBuilder::class);
    }

    /**
     * Retrieves the index name derived from the class name.
     *
     * @return string The index name in snake_case format.
     */
    private function getIndexName(): string
    {
        $className = explode('\\', get_class($this));
        $indexName = Str::plural(str_replace('Repository', '', end($className)));
        return Str::snake($indexName);
    }


    /**
     * Finds and retrieves an entity by its ID.
     *
     * @param string $id The unique identifier of the entity.
     * @return null|EntityInterface|null The hydrated entity if found, or null if not found or an error occurs.
     */
    public function find(string $id): ?EntityInterface
    {
        $result = $this->queryBuilder
            ->select()
            ->from($this->index)
            ->where('id', '=', $id)
            ->execute();

        if ($result['result'] !== 'success' || empty($result['data'][0])) {
            return null;
        }

        return make($this->entity, ['data' => $result['data'][0]]);
    }

    /**
     * Retrieves and hydrates the first entity matching the provided parameters.
     *
     * @param array $params An associative array of query parameters used to filter the entities.
     * @return null|EntityInterface The hydrated entity instance corresponding to the first match.
     */
    public function first(array $params): ?EntityInterface
    {
        $result = $this->parseQuery($params)
            ->limit(1)
            ->execute();

        if ($result['result'] !== 'success' || empty($result['data'][0])) {
            return null;
        }

        return make($this->entity, ['data' => $result['data'][0]]);
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
        $query = $this->parseQuery($params);
        $result = $query->execute();
        return array_map(fn($item) => make($this->entity, ['data' => $item]), $result['data'] ?? []);
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
        $result = $this->parseQuery($params)
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->execute();
        $result['data'] = array_map(fn($item) => (make($this->entity, ['data' => $item]))->toArray(), $result['data'] ?? []);
        return [
            ...$result,
            'current_page' => (int)$page,
            'per_page' => (int)$perPage,
            'total' => $this->parseQuery($params)->count()
        ];
    }


    /**
     * Creates and stores a new entity in the repository.
     *
     * @param EntityInterface $entity The entity to be stored.
     * @return EntityInterface The stored entity with updated data after creation.
     * @throws RepositoryCreateException If there is an error during the creation process.
     */
    public function create(EntityInterface $entity): EntityInterface
    {
        $result = $this->queryBuilder
            ->into($this->index)
            ->insert($entity->toArray());

        if ($result['result'] !== 'created') {
            throw new RepositoryCreateException($result['error']);
        }

        return make($this->entity, ['data' => $result['data']]);

    }


    /**
     * Updates an existing entity in the repository and returns the updated entity.
     *
     * @param EntityInterface $entity The entity to update, containing its identifier and updated data.
     * @return EntityInterface The updated entity after successful modification.
     * @throws RepositoryUpdateException If the update operation fails or encounters an error.
     */
    public function update(EntityInterface $entity): EntityInterface
    {
        $result = $this->queryBuilder
            ->from($this->index)
            ->update($entity->getId(), $entity->toArray());

        if (!in_array($result['result'], ['updated', 'noop'])) {
            throw new RepositoryUpdateException($result['error']);
        }

        return make($this->entity, ['data' => $result['data']]);
    }

    /**
     * Deletes a record identified by the given ID from the Elasticsearch index.
     *
     * @param string $id The unique identifier of the record to be deleted.
     * @return bool True if the record was successfully deleted, false otherwise.
     */
    public function delete(string $id): bool
    {
        return in_array($this->queryBuilder->delete($id)['result'], ['deleted', 'updated', 'noop']);
    }

    /**
     * Parses query parameters to construct a QueryBuilder object.
     *
     * @param array $params The query parameters, which may include optional keys such as '_fields' for selecting specific fields,
     *                      '_sort' for defining sorting order, and other key-value pairs for filtering conditions.
     * @return QueryBuilder The constructed QueryBuilder instance reflecting the parsed parameters.
     */
    public function parseQuery(array $params): QueryBuilder
    {
        $query = $this->queryBuilder->from($this->index);

        $query->select(explode(',', $params['_fields'] ?? '*'));

        if (!empty($params['_sort'])) {
            $sortList = array_map(fn($item) => explode(':', $item), explode(',', $params['_sort']));
            foreach ($sortList as $sort) {
                $query->orderBy($sort[0], $sort[1] ?? 'asc');
            }
        }

        foreach ($params as $key => $value) {
            if (str_starts_with($key, '_')) {
                continue;
            }
            $query->where($key, '=', $value);
        }
        return $query;
    }

    /**
     * Checks if a record with the specified identifier exists in the database.
     *
     * @param string $id The unique identifier of the record to check for existence.
     * @return bool True if the record exists, false otherwise.
     */
    public function exists(string $id): bool
    {
        return $this->queryBuilder
                ->select()
                ->from($this->index)
                ->where('id', '=', $id)
                ->count() > 0;
    }

    /**
     * Generates a hash value using the HMAC method with the SHA-256 algorithm.
     *
     * @param string $string The input string to be hashed.
     * @param string $key The secret key used for hashing.
     * @return string The resulting hash value as a string.
     */
    public function createHash(string $string, string $key): string
    {
        return hash_hmac('sha256', $string, $key);
    }


}