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
     * @param QueryBuilderInterface $queryBuilder The base query builder to modify
     */
    public function parse(array $params, QueryBuilderInterface $queryBuilder);
}
