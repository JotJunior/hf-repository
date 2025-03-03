<?php

declare(strict_types=1);

namespace Jot\HfRepository\Query;

use Jot\HfElastic\QueryBuilder;

/**
 * Interface for query parsing functionality.
 * 
 * This interface defines the contract for classes that parse query parameters
 * into QueryBuilder instances. It follows the Interface Segregation Principle
 * by providing a focused interface for query parsing.
 */
interface QueryParserInterface
{
    /**
     * Parses query parameters to construct a QueryBuilder object.
     *
     * @param array $params The query parameters, which may include optional keys such as:
     *                      - '_fields' for selecting specific fields
     *                      - '_sort' for defining sorting order
     *                      - Other key-value pairs for filtering conditions
     * @param QueryBuilder $queryBuilder The base query builder to modify
     * @return QueryBuilder The constructed QueryBuilder instance reflecting the parsed parameters
     */
    public function parse(array $params, QueryBuilder $queryBuilder): QueryBuilder;
}
