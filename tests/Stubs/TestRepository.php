<?php

declare(strict_types=1);
/**
 * This file is part of the hf_repository module, a package build for Hyperf framework that is responsible for manage controllers, entities and repositories.
 *
 * @author   Joao Zanon <jot@jot.com.br>
 * @link     https://github.com/JotJunior/hf-repository
 * @license  MIT
 */

namespace Jot\HfRepository\Tests\Stubs;

use Jot\HfElastic\Contracts\QueryBuilderInterface;
use Jot\HfRepository\Entity\EntityFactoryInterface;
use Jot\HfRepository\Query\QueryParserInterface;
use Jot\HfRepository\Repository;

/**
 * Concrete implementation of Repository for testing.
 */
class TestRepository extends Repository
{
    /**
     * Entity class name.
     */
    protected string $entity = RepositoryTestEntity::class;

    /**
     * Constructor with explicit dependencies for testing.
     */
    public function __construct(
        QueryBuilderInterface $queryBuilder,
        QueryParserInterface $queryParser,
        EntityFactoryInterface $entityFactory
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->queryParser = $queryParser;
        $this->entityFactory = $entityFactory;

        parent::__construct();
    }

    /**
     * Get the index name for testing.
     */
    protected function getIndexName(): string
    {
        return 'tests';
    }
}
