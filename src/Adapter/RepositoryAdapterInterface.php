<?php

declare(strict_types=1);

namespace Jot\HfRepository\Adapter;

/**
 * Interface for repository adapters that handle CRUD operations.
 * This interface allows for swapping different database implementations.
 */
interface RepositoryAdapterInterface
{
    /**
     * Find a document by its ID.
     *
     * @param string $index The index/table name.
     * @param string $id The document ID.
     * @return array Result containing data and operation status.
     */
    public function find(string $index, string $id): array;

    /**
     * Insert a new document into the index.
     *
     * @param string $index The index/table name.
     * @param array $data The data to insert.
     * @return array Result containing data and operation status.
     */
    public function insert(string $index, array $data): array;

    /**
     * Update an existing document.
     *
     * @param string $index The index/table name.
     * @param string $id The document ID.
     * @param array $data The data to update.
     * @return array Result containing data and operation status.
     */
    public function update(string $index, string $id, array $data): array;

    /**
     * Delete a document by its ID.
     *
     * @param string $index The index/table name.
     * @param string $id The document ID.
     * @param bool $logicalDeletion Whether to perform a logical deletion.
     * @return array Result containing operation status.
     */
    public function delete(string $index, string $id, bool $logicalDeletion = true): array;

    /**
     * Execute a query and return the results.
     *
     * @param array $query The query to execute.
     * @return array Result containing data and operation status.
     */
    public function execute(array $query): array;

    /**
     * Count documents matching a query.
     *
     * @param array $query The query to count.
     * @return int The number of matching documents.
     */
    public function count(array $query): int;

    /**
     * Check if a document exists.
     *
     * @param string $index The index/table name.
     * @param string $id The document ID.
     * @return bool Whether the document exists.
     */
    public function exists(string $index, string $id): bool;
}
