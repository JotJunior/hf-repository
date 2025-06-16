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

namespace Jot\HfRepository\Service;

use Exception;
use Hyperf\Cache\Listener\DeleteListenerEvent;
use Jot\HfRepository\RepositoryInterface;
use Jot\HfRepository\Service\Contract\ServiceInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionException;
use RuntimeException;

use function Hyperf\Support\make;

abstract class AbstractService implements ServiceInterface
{
    protected const CACHE_PREFIX = 'abstract_service:entity';

    protected string $repositoryClass;

    protected string $entityClass;

    protected RepositoryInterface $repository;

    public function __construct(
        protected EventDispatcherInterface $dispatcher
    ) {
        $this->repository = make($this->repositoryClass);
    }


    public function paginate(array $query, array $filters = []): array
    {
        $query['_sort'] = $query['_sort'] ?? 'name:asc';
        return $this->repository->paginate(params: $query, filters: $filters);
    }

    /**
     * Autocompletes for results based on the provided keyword.
     * @param string $keyword the search keyword to query the repository for matching results
     * @return array an array of matching results from the repository
     * @throws ReflectionException
     */
    public function autocomplete(string $keyword): array
    {
        return $this->repository->autocomplete($keyword, ['name']);
    }

    /**
     * Searches for results based on the provided keyword.
     * @param string $keyword the search keyword to query the repository for matching results
     * @return array an array of matching results from the repository
     * @throws ReflectionException
     */
    public function search(string $keyword): array
    {
        return $this->repository->search($keyword, ['name']);
    }

    /**
     * Retrieves data for a specific entity based on the provided identifier.
     * @param string $id the unique identifier of the entity to retrieve
     * @return array an associative array containing the entity data, a result status, and an error message if applicable
     * @throws RuntimeException
     */
    public function getData(string $id): array
    {
        $entity = $this->repository->find($id);

        return [
            'data' => $entity->toArray(),
            'result' => 'success',
            'message' => null,
        ];
    }

    /**
     * Creates a new entity and stores it in the repository.
     * @param array $data the data used to create the entity
     * @return array an array containing the created entity's data, the result status, and any error information
     * @throws Exception
     */
    public function create(array $data): array
    {
        $data['tenant']['id'] = $data['_tenant_id'] ?? null;

        $entity = make($this->entityClass, ['data' => $data]);
        $result = $this->repository->create($entity);

        return [
            'data' => $result->toArray(),
            'result' => 'success',
            'message' => null,
        ];
    }

    /**
     * Updates an entity with the provided data based on the given ID.
     * @param string $id the unique identifier of the entity to be updated
     * @param array $data the data to update the entity with
     * @return array an associative array containing the update result, including the updated data, status, and error information
     * @throws Exception
     */
    public function update(string $id, array $data): array
    {
        $entity = make($this->entityClass, ['data' => ['id' => $id, ...$data]]);
        $result = $this->repository->update($entity);

        if (defined('static::CACHE_PREFIX')) {
            $this->dispatcher->dispatch(new DeleteListenerEvent(static::CACHE_PREFIX, [$id]));
        }

        return [
            'data' => $result->toArray(),
            'result' => 'success',
            'message' => null,
        ];
    }

    /**
     * Deletes a resource identified by the provided ID.
     * @param string $id the unique identifier of the resource to be deleted
     * @return array an array containing the operation result, with keys 'data', 'result', and 'error'
     * @throws Exception if the deletion process encounters an error
     */
    public function delete(string $id): array
    {
        if (defined('static::CACHE_PREFIX')) {
            $this->dispatcher->dispatch(new DeleteListenerEvent(static::CACHE_PREFIX, [$id]));
        }

        return $this->repository->delete($id);
    }

    /**
     * Checks if a record exists in the repository for the given ID.
     * @param string $id the unique identifier of the record to check
     * @return bool true if the record exists, false otherwise
     * @throws Exception
     */
    public function exists(string $id): bool
    {
        return $this->repository->exists($id);
    }
}
