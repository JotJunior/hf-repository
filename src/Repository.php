<?php

namespace Jot\HfRepository;

use Hyperf\Di\Annotation\Inject;
use Jot\HfElastic\ElasticsearchService;
use Jot\HfRepository\Exception\RecordNotFoundException;

abstract class Repository
{

    #[Inject]
    protected ElasticsearchService $esClient;

    protected string $index;
    protected EntityInterface $entity;

    public function __construct(EntityInterface $entity)
    {
        $this->index = $this->getIndexName();
        $this->entity = $entity;
    }

    /**
     * Retrieves the index name derived from the class name.
     *
     * @return string The index name in snake_case format.
     */
    private function getIndexName(): string
    {
        $className = explode('\\', get_class($this));
        return $this->camelToSnakeCase(end($className));
    }

    /**
     * Retrieves and hydrates an entity using the provided identifier.
     *
     * @param string $id The unique identifier of the entity to retrieve.
     * @return EntityInterface The hydrated entity instance.
     */
    public function find(string $id): EntityInterface
    {
        $this->entity->hydrate($this->esClient->get($id, $this->index)['_source'] ?? []);
        return $this->entity;
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
        $this->entity->hydrate($this->esClient->get($id, $this->index)['_source'] ?? []);
        if (empty($this->entity->getId())) {
            throw new RecordNotFoundException();
        }
        return $this->entity;
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
        $this->entity->hydrate($result['hits']['hits'][0]['_source']);
        return $this->entity;
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
                $entity = $this->entity->clone();
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

    public function paginate(array $query, int $page = 1, int $perPage = 10): array
    {
        $query['body']['from'] = ($page - 1) * $perPage;
        $query['body']['size'] = $perPage;
        $result = $this->esClient->search($query, $this->index);
        return [
            'results' => array_map(fn($item) => $this->entity->clone()->hydrate($item['_source']), $result['hits']['hits']),
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $result['hits']['total']['value']
        ];
    }

    /**
     * Converts a camelCase string to snake_case format.
     *
     * @param string $string The camelCase string to be converted.
     * @return string The converted string in snake_case format.
     */
    private function camelToSnakeCase(string $string): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }


}