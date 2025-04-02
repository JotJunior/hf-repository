<?php

declare(strict_types=1);

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
     * Entity class name
     */
    protected string $entity = RepositoryTestEntity::class;
    
    /**
     * Constructor with explicit dependencies for testing
     * 
     * @param QueryBuilderInterface $queryBuilder
     * @param QueryParserInterface $queryParser
     * @param EntityFactoryInterface $entityFactory
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
     * Get the index name for testing
     * 
     * @return string
     */
    protected function getIndexName(): string
    {
        return 'tests';
    }
}
