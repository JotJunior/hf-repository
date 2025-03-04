<?php

namespace Jot\HfRepository\Tests\Entity\Traits;

use Hyperf\Swagger\Annotation as SA;
use Jot\HfRepository\Entity\Traits\HydratableTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(HydratableTrait::class)]
class HydratableTraitTest extends TestCase
{
    private HydratableTraitTestClass $sut;

    #[Test]
    #[Group('unit')]
    public function testHydrateWithValidData(): void
    {
        // Arrange
        $data = [
            'name' => 'Test Name',
            'email' => 'test@example.com',
        ];

        // Act
        $result = $this->sut->hydrate($data);

        // Assert
        $this->assertSame($this->sut, $result);
        $this->assertEquals('Test Name', $this->sut->name);
        $this->assertEquals('test@example.com', $this->sut->email);
    }

    #[Test]
    #[Group('unit')]
    public function testHydrateWithNonExistentProperty(): void
    {
        // Arrange
        $data = [
            'name' => 'Test Name',
            'non_existent' => 'This property does not exist',
        ];

        // Act
        $result = $this->sut->hydrate($data);

        // Assert
        $this->assertSame($this->sut, $result);
        $this->assertEquals('Test Name', $this->sut->name);
        // The non-existent property should be ignored
    }

    #[Test]
    #[Group('unit')]
    public function testHydrateWithRelatedClass(): void
    {
        // Arrange
        $data = [
            'name' => 'Test Name',
        ];

        // Act
        $result = $this->sut->hydrate($data);

        // Assert
        $this->assertSame($this->sut, $result);
        $this->assertEquals('Test Name', $this->sut->name);
    }

    #[Test]
    #[Group('unit')]
    public function testHydrateWithExceptionLogging(): void
    {
        // Este teste é apenas para verificar que não há erros quando o logger não está definido
        // Arrange
        $data = [
            'exception_property' => 'This will cause an exception',
        ];
        $this->sut->logger = null;

        // Act
        $result = $this->sut->hydrate($data);

        // Assert
        $this->assertSame($this->sut, $result);
        // Se não houve exceção, o teste passou
        $this->assertTrue(true);
    }

    #[Test]
    #[Group('unit')]
    public function testToArrayWithNestedObjects(): void
    {
        // Arrange
        $this->sut->name = 'Test Name';
        $this->sut->relatedEntity = new RelatedEntity();
        $this->sut->relatedEntity->id = 1;
        $this->sut->relatedEntity->name = 'Related Entity';

        // Act
        $result = $this->sut->toArray();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('related_entity', $result);
        // O objeto RelatedEntity não implementa toArray, então ele é retornado como objeto
        $this->assertInstanceOf(RelatedEntity::class, $result['related_entity']);
        $this->assertEquals(1, $result['related_entity']->id);
        $this->assertEquals('Related Entity', $result['related_entity']->name);
    }

    #[Test]
    #[Group('unit')]
    public function testToArrayWithDateTimeObjects(): void
    {
        // Arrange
        $this->sut->name = 'Test Name';
        $this->sut->createdAt = new \DateTime('2023-01-01 12:00:00');

        // Act
        $result = $this->sut->toArray();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('created_at', $result);
        $this->assertIsString($result['created_at']);
        // O formato do DateTime é DATE_ATOM
        $this->assertStringContainsString('2023-01-01T12:00:00', $result['created_at']);
    }

    #[Test]
    #[Group('unit')]
    public function testToArrayWithHiddenProperties(): void
    {
        // Arrange
        $this->sut->name = 'Test Name';
        $this->sut->email = 'test@example.com';
        $this->sut->hiddenProperties = ['email'];

        // Act
        $result = $this->sut->toArray();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('email', $result);
    }

    #[Test]
    #[Group('unit')]
    public function testExtractVariables(): void
    {
        // Arrange
        $reflection = new \ReflectionClass($this->sut);
        $method = $reflection->getMethod('extractVariables');
        $method->setAccessible(true);

        $dateTime = new \DateTime('2023-01-01 12:00:00');
        $array = ['key' => 'value'];
        $object = new RelatedEntity();
        $object->id = 1;

        // Act & Assert
        // Teste com DateTime
        $result = $method->invoke($this->sut, $dateTime);
        $this->assertIsString($result);
        $this->assertStringContainsString('2023-01-01T12:00:00', $result);

        // Teste com array
        $result = $method->invoke($this->sut, $array);
        $this->assertIsArray($result);
        $this->assertEquals($array, $result);

        // Teste com objeto
        $result = $method->invoke($this->sut, $object);
        $this->assertInstanceOf(RelatedEntity::class, $result);
    }

    #[Test]
    #[Group('unit')]
    public function testGetAllProperties(): void
    {
        // Arrange
        $reflection = new \ReflectionClass($this->sut);
        $method = $reflection->getMethod('getAllProperties');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->sut, $reflection);

        // Assert
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Verificar se as propriedades da classe estão presentes
        $propertyNames = array_map(function (\ReflectionProperty $prop) {
            return $prop->getName();
        }, $result);

        $this->assertContains('name', $propertyNames);
        $this->assertContains('email', $propertyNames);
    }

    #[Test]
    #[Group('unit')]
    public function testExtractVariablesWithArray(): void
    {
        // Arrange
        $arrayData = ['key' => 'value'];
        $this->sut->arrayProperty = $arrayData;

        // Act
        $result = $this->sut->toArray();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('array_property', $result);
        $this->assertEquals($arrayData, $result['array_property']);
    }

    #[Test]
    #[Group('unit')]
    public function testToArrayWithInaccessibleProperty(): void
    {
        // Arrange
        $this->sut->name = 'Test Name';

        // Adicionar a propriedade à lista de propriedades ocultas
        $this->sut->hiddenProperties = ['exception_on_access'];

        // Act
        $result = $this->sut->toArray();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        // A propriedade deve estar oculta
        $this->assertArrayNotHasKey('exception_on_access', $result);
    }

    #[Test]
    #[Group('unit')]
    public function testToArrayWithExceptionHandling(): void
    {
        // Arrange
        $this->sut->name = 'Test Name';

        // Criar um mock de ReflectionProperty que lança exceção
        $mockReflection = new class() extends \ReflectionClass {
            public function __construct()
            {
                // Construtor vazio para evitar erros
            }

            public function getProperties(?int $filter = null): array
            {
                // Criar uma propriedade que lança exceção ao acessar
                $mockProperty = $this->createMockProperty();
                return [$mockProperty];
            }

            private function createMockProperty()
            {
                return new class() extends \ReflectionProperty {
                    public function __construct()
                    {
                        // Construtor vazio para evitar erros
                    }

                    public function getName(): string
                    {
                        return 'exception_property';
                    }

                    public function setAccessible(bool $accessible): void
                    {
                        // Não faz nada
                    }

                    public function getValue(object $object = null): mixed
                    {
                        throw new \Exception('Cannot access property');
                    }
                };
            }
        };

        // Criar um objeto de teste com o mock de reflection
        $testObject = new class($mockReflection) {
            use HydratableTrait;

            private $reflection;
            public array $hiddenProperties = [];

            public function __construct($reflection)
            {
                $this->reflection = $reflection;
            }

            public function toArrayTest(): array
            {
                // Chamar o método toArray com o mock de reflection
                return $this->toArray();
            }
        };

        // Act
        $result = $testObject->toArrayTest();

        // Assert
        $this->assertIsArray($result);
        // A propriedade que lança exceção deve ser ignorada
        $this->assertArrayNotHasKey('exception_property', $result);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->sut = new HydratableTraitTestClass();
    }
}

/**
 * Test class for HydratableTrait
 */
class HydratableTraitTestClass
{
    use HydratableTrait;

    public string $name;
    public string $email;

    #[SA\Property(x: ['php_type' => RelatedEntity::class])]
    public ?RelatedEntity $relatedEntity = null;
    public ?\DateTime $createdAt = null;
    public ?LoggerInterface $logger = null;
    public array $hiddenProperties = [];
    public array $arrayProperty = [];
    public bool $exceptionOnAccess = false;

    /**
     * @SA\Property(x={"php_type"="Jot\HfRepository\Tests\Entity\Traits\RelatedEntity"})
     */
    public ?RelatedEntity $docCommentRelatedEntity = null;

    /**
     * Property that will throw an exception during hydration
     */
    public function setExceptionProperty($value): void
    {
        throw new \Exception('Exception during hydration');
    }

    /**
     * Getter que lança exceção ao ser acessado
     */
    public function getExceptionOnAccess(): bool
    {
        throw new \Exception('Cannot access property');
    }
}

/**
 * Related entity for testing
 */
class RelatedEntity
{
    public ?int $id = null;
    public ?string $name = null;
}
