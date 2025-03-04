<?php

declare(strict_types=1);

namespace Jot\HfRepository\Tests\Entity\Traits;

use Jot\HfRepository\Entity\Traits\HashableTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HashableTrait::class)]
class HashableTraitTest extends TestCase
{
    private HashableTraitTestClass $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sut = new HashableTraitTestClass();
        $this->sut->password = 'secret123';
    }

    #[Test]
    #[Group('unit')]
    public function testCreateHashUpdatesPropertyValue(): void
    {
        // Arrange
        $property = 'password';
        $salt = 'test-salt';
        $encryptionKey = 'test-encryption-key';
        $expectedHash = hash_hmac('sha256', 'secret123' . $salt, $encryptionKey);

        // Act
        $result = $this->sut->createHash($property, $salt, $encryptionKey);

        // Assert
        $this->assertSame($this->sut, $result);
        $this->assertEquals($expectedHash, $this->sut->password);
    }

    #[Test]
    #[Group('unit')]
    public function testCreateHashWithNonExistentPropertyDoesNothing(): void
    {
        // Arrange
        $property = 'nonExistentProperty';
        $salt = 'test-salt';
        $encryptionKey = 'test-encryption-key';
        $originalPassword = $this->sut->password;

        // Act
        $result = $this->sut->createHash($property, $salt, $encryptionKey);

        // Assert
        $this->assertSame($this->sut, $result);
        $this->assertEquals($originalPassword, $this->sut->password);
    }

    #[Test]
    #[Group('unit')]
    public function testCreateHashWithDifferentSaltsProducesDifferentHashes(): void
    {
        // Arrange
        $property = 'password';
        $salt1 = 'salt1';
        $salt2 = 'salt2';
        $encryptionKey = 'test-encryption-key';
        
        // Create a copy of the original object
        $sut2 = clone $this->sut;

        // Act
        $this->sut->createHash($property, $salt1, $encryptionKey);
        $hash1 = $this->sut->password;
        
        $sut2->createHash($property, $salt2, $encryptionKey);
        $hash2 = $sut2->password;

        // Assert
        $this->assertNotEquals($hash1, $hash2);
    }

    #[Test]
    #[Group('unit')]
    public function testCreateHashWithDifferentEncryptionKeysProducesDifferentHashes(): void
    {
        // Arrange
        $property = 'password';
        $salt = 'test-salt';
        $encryptionKey1 = 'encryption-key-1';
        $encryptionKey2 = 'encryption-key-2';
        
        // Create a copy of the original object
        $sut2 = clone $this->sut;

        // Act
        $this->sut->createHash($property, $salt, $encryptionKey1);
        $hash1 = $this->sut->password;
        
        $sut2->createHash($property, $salt, $encryptionKey2);
        $hash2 = $sut2->password;

        // Assert
        $this->assertNotEquals($hash1, $hash2);
    }
}

/**
 * Test class that uses HashableTrait
 */
class HashableTraitTestClass
{
    use HashableTrait;
    
    public string $password;
}
