<?php

namespace Jot\HfRepository;

use Hyperf\Stringable\Str;
use Jot\HfElastic\ElasticsearchService;
use Jot\HfRepository\Exception\RecordNotFoundException;
use function Hyperf\Support\make;

abstract class Repository implements RepositoryInterface
{

    protected ElasticsearchService $esClient;
    protected string $entity;
    protected string $index;
    private EntityInterface $entityObject;

    public function __construct(ElasticsearchService $esClient)
    {
        $this->index = $this->getIndexName();
        $this->entityObject = make($this->entity, []);
        $this->esClient = $esClient;
    }

    /**
     * Retrieves the index name derived from the class name.
     *
     * @return string The index name in snake_case format.
     */
    private function getIndexName(): string
    {
        $className = explode('\\', get_class($this));
        $indexName = Str::plural(end($className));
        return Str::snake($indexName);
    }

    /**
     * Retrieves and hydrates an entity using the provided identifier.
     *
     * @param string $id The unique identifier of the entity to retrieve.
     * @return EntityInterface The hydrated entity instance.
     */
    public function find(string $id): EntityInterface
    {
        $this->entityObject->hydrate($this->esClient->get($id, $this->index)['_source'] ?? []);
        return $this->entityObject;
    }

    /**
     * Retrieves and hydrates an entity using the provided identifier.
     * Throws an exception if the entity cannot be found.
     *
     * @param string $id The unique identifier of the entity to retrieve.
     * @return EntityInterface The hydrated entity instance.
     * @throws RecordNotFoundException If the entity with the given identifier does not exist.
     */
    public function findOrFail(string $id): EntityInterface
    {
        $this->entityObject->hydrate($this->esClient->get($id, $this->index)['_source'] ?? []);
        if (empty($this->entityObject->getId())) {
            throw new RecordNotFoundException();
        }
        return $this->entityObject;
    }

    /**
     * Retrieves and hydrates the first entity found based on the provided query.
     *
     * @param array $query The query parameters used to search for the entity.
     * @return EntityInterface The hydrated entity instance.
     */
    public function first(array $query): EntityInterface
    {
        $result = $this->esClient->search($query, $this->index);
        $this->entityObject->hydrate($result['hits']['hits'][0]['_source']);
        return $this->entityObject;
    }

    /**
     * Retrieves all records matching the given query from the Elasticsearch index and hydrates them into entities.
     *
     * @param array $query The search query to execute against the Elasticsearch index.
     * @return array An array of hydrated entities matching the search query.
     */
    public function all(array $query): array
    {
        $query['body']['size'] = 1000;
        $query['scroll'] = '5m';
        $result = $this->esClient->search($query, $this->index);
        $results = [];
        while ($result['hits']['hits'] ?? false) {
            $scrollId = $result['_scroll_id'];
            foreach ($result['hits']['hits'] as $item) {
                $entity = $this->entityObject->clone();
                $entity->hydrate($item['_source']);
                $results[] = $entity;
            }
            $result = $this->esClient->es()->scroll([
                'scroll_id' => $scrollId,
                'scroll' => '5m',
            ]);
        }
        return [
            'results' => $results,
            'total' => $result['hits']['total']['value']
        ];
    }

    /**
     * Paginates the search results from the Elasticsearch index based on the provided query, page, and per-page values.
     *
     * @param array $query The search query to execute against the Elasticsearch index.
     * @param int $page The current page number to retrieve. Defaults to 1.
     * @param int $perPage The number of results to retrieve per page. Defaults to 10.
     * @return array An array containing the paginated results, current page, results per page, and total results count.
     */
    public function paginate(array $query, int $page = 1, int $perPage = 10): array
    {
        $query['from'] = ($page - 1) * $perPage;
        $query['size'] = $perPage;
        if (empty($query['body']['query'])) {
            $query['body']['query'] = ['match_all' => new \stdClass()];
        }
        $result = $this->esClient->search($query, $this->index);

        return [
            'results' => array_map(fn($item) => $this->entityObject->hydrate($item['_source'])->toArray(), $result['hits']['hits']),
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $result['hits']['total']['value']
        ];
    }

    /**
     * Creates a new record in the Elasticsearch index using the provided entity and assigns it a unique identifier.
     *
     * @param EntityInterface $entity The entity to be created and inserted into the Elasticsearch index.
     * @return EntityInterface The created entity with its unique identifier assigned.
     */
    public function create(EntityInterface $entity): EntityInterface
    {
        $id = Str::uuid();
        $entity->setId($id);
        $this->esClient->insert($entity->toArray(), $id);
        return $this->find($id);
    }

    /**
     * Updates an existing entity in the Elasticsearch index and retrieves the updated entity.
     *
     * @param EntityInterface $entity The entity to update, including its data and identifier.
     * @return EntityInterface The updated entity retrieved from the Elasticsearch index.
     */
    public function update(EntityInterface $entity): EntityInterface
    {
        $this->esClient->update($entity->toArray(), $entity->getId());
        return $this->find($entity->getId());
    }

    /**
     * Deletes a record identified by the given ID from the Elasticsearch index.
     *
     * @param string $id The unique identifier of the record to be deleted.
     * @return bool True if the record was successfully deleted, false otherwise.
     */
    public function delete(string $id): bool
    {
        return 'deleted' === $this->esClient->delete($id, $this->index);
    }


}