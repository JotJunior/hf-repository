<?php

declare(strict_types=1);

namespace Jot\HfRepository\Query;

use Jot\HfElastic\QueryBuilder;
use Jot\HfRepository\Adapter\QueryBuilderAdapter;

/**
 * Default implementation of QueryParserInterface.
 * 
 * This class is responsible for parsing query parameters into QueryBuilder instances.
 * It follows the Single Responsibility Principle by focusing solely on query parsing.
 */
class QueryParser implements QueryParserInterface
{
    /**
     * Parses query parameters to construct a QueryBuilder object.
     *
     * @param array $params The query parameters, which may include optional keys such as:
     *                      - '_fields' for selecting specific fields
     *                      - '_sort' for defining sorting order
     *                      - Other key-value pairs for filtering conditions
     * @param QueryBuilder|QueryBuilderAdapter $queryBuilder The base query builder to modify
     * @return QueryBuilder|QueryBuilderAdapter The constructed QueryBuilder instance reflecting the parsed parameters
     */
    public function parse(array $params, $queryBuilder)
    {
        // Select fields
        $queryBuilder->select(explode(',', $params['_fields'] ?? '*'));

        // Apply sorting
        if (!empty($params['_sort'])) {
            $sortList = array_map(fn($item) => explode(':', $item), explode(',', $params['_sort']));
            foreach ($sortList as $sort) {
                $queryBuilder->orderBy($sort[0], $sort[1] ?? 'asc');
            }
        }

        // Apply filters
        foreach ($params as $key => $value) {
            if (str_starts_with($key, '_')) {
                continue;
            }
            $queryBuilder->where($key, $value);
        }
        
        return $queryBuilder;
    }
}
