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

namespace Jot\HfRepository\Tests\Exception;

use Jot\HfRepository\Exception\EntityValidationWithErrorsException;
use Jot\HfRepository\Exception\InvalidEntityException;
use Jot\HfRepository\Exception\RecordNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(InvalidEntityException::class)]
#[CoversClass(RecordNotFoundException::class)]
#[CoversClass(EntityValidationWithErrorsException::class)]
class ExceptionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    #[Test]
    #[Group('unit')]
    public function testInvalidEntityExceptionUsesTranslation(): void
    {
        // Act
        $exception = new InvalidEntityException();

        // Assert
        $this->assertEquals('Invalid entity', $exception->getMessage());
    }

    #[Test]
    #[Group('unit')]
    public function testRecordNotFoundExceptionUsesTranslation(): void
    {
        // Act
        $exception = new RecordNotFoundException();

        // Assert
        $this->assertEquals('Record not found', $exception->getMessage());
    }

    #[Test]
    #[Group('unit')]
    public function testEntityValidationWithErrorsExceptionUsesTranslation(): void
    {
        // Arrange
        $errors = ['name' => ['Name is required']];

        // Act
        $exception = new EntityValidationWithErrorsException($errors);

        // Assert
        $this->assertEquals('Validation errors', $exception->getMessage());
        $this->assertEquals($errors, $exception->getErrors());
    }
}
