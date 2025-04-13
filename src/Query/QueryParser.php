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

namespace Jot\HfRepository\Query;

use Jot\HfElastic\Contracts\QueryBuilderInterface;

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
     * @param QueryBuilderInterface $queryBuilder The base query builder to modify
     * @return QueryBuilderInterface The constructed QueryBuilder instance reflecting the parsed parameters
     */
    public function parse(array $params, QueryBuilderInterface $queryBuilder): QueryBuilderInterface
    {
        // Select fields
        $queryBuilder->select(explode(',', $params['_fields'] ?? '*'));

        if (! empty($params['_per_page'])) {
            $queryBuilder->limit((int) $params['_per_page']);
        }

        // Apply sorting
        if (! empty($params['_sort'])) {
            $sortList = array_map(fn ($item) => explode(':', $item), explode(',', $params['_sort']));
            foreach ($sortList as $sort) {
                $queryBuilder->orderBy($sort[0], $sort[1] ?? 'asc');
            }
        }

        // Apply filters
        foreach ($params as $key => $value) {
            if (str_starts_with($key, '_')) {
                continue;
            }
            $queryBuilder->where($key, '=', $value);
        }

        return $queryBuilder;
    }
}
