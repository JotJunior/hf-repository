<?php

declare(strict_types=1);

namespace Jot\HfRepository\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Stringable\Str;
use Jot\HfElastic\ElasticsearchService;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class GenerateEntityCommand extends HyperfCommand
{
    protected ElasticsearchService $esClient;

    protected array $ignoreProperties = ['created_at', 'updated_at', 'removed'];

    protected string $namespace = 'App\\Entity';
    protected string $outputDir = BASE_PATH . '/app/Entity';

    public function __construct(protected ContainerInterface $container, ElasticsearchService $esClient)
    {
        parent::__construct('jot:create-entity');
        $this->setDescription('Creating entity classes based on the elasticsearch mapping configuration.');
        $this->addOption('mapping', 'M', InputOption::VALUE_REQUIRED, 'Elasticsearch mapping name', '');
        $this->addOption('namespace', 'N', InputOption::VALUE_OPTIONAL, 'Entity namespace', 'App\\Entity');
        $this->addOption('output-dir', 'O', InputOption::VALUE_OPTIONAL, 'Entity namespace', BASE_PATH . '/app/Entity');
        $this->addOption('force', 'F', InputOption::VALUE_OPTIONAL, 'Rewrite mapping file', false);

        $this->esClient = $esClient;
    }

    /**
     * Executes the command to generate entity classes based on the mapping configuration.
     *
     * This method ensures the BASE_PATH constant is defined, retrieves input options,
     * and orchestrates the generation of entity classes into the specified directory.
     *
     * @return void
     */
    public function handle()
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', \dirname(__DIR__, 4));
        }

        $mapping = $this->input->getOption('mapping');
        $force = boolval($this->input->getOption('force'));
        $mainClassName = ucfirst(Str::camel(Str::singular($mapping)));
        $this->setOutputDir($mainClassName);
        $this->namespace = sprintf('%s\\%s', $this->input->getOption('namespace'), $mainClassName);

        $this->line(sprintf('Starting to generate entity %s', $mainClassName), 'info');

        try {
            $this->generateEntityFromMapping($this->fetchMapping($mapping), $mainClassName, false, $force);
            $this->line(sprintf('[OK] %s', $mainClassName));
        } catch (\Throwable $e) {
            $this->line('ERROR: ' . $e->getMessage(), 'error');
            return;
        }
        $this->line('Entity generated successfully!', 'info');

    }

    private function setOutputDir(string $entityName): string
    {
        $this->outputDir = sprintf('%s/%s', $this->input->getOption('output-dir'), $entityName);

        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }

        return $this->outputDir;
    }

    /**
     * Fetches the mapping information for a specific index from Elasticsearch.
     *
     * @param string $indexName The name of the index for which the mapping should be retrieved.
     * @return array|null The mapping information for the index, or null if the mapping could not be retrieved or does not exist.
     */
    private function fetchMapping($indexName): ?array
    {
        try {
            $response = $this->esClient->es()->indices()->getMapping(['index' => $indexName]);
            return $response[$indexName]['mappings'] ?? null;
        } catch (\Exception $e) {
            $this->line('ERROR: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Maps an Elasticsearch data type to a corresponding PHP type.
     *
     * @param string $elasticType The Elasticsearch data type to be mapped.
     * @return string The corresponding PHP type as a string.
     */
    private function mapElasticTypeToPhpType($elasticType): string
    {
        return match ($elasticType) {
            'text' => 'string',
            'keyword' => 'string',
            'date' => '\DateTime',
            'long' => 'int',
            'integer' => 'int',
            'short' => 'int',
            'byte' => 'int',
            'double' => 'float',
            'float' => 'float',
            'boolean' => 'bool',
            'nested' => 'array',
            'object' => 'object',
            default => 'mixed',
        };
    }

    private function generateEntityFromMapping(array $mapping, string $className, bool $isChild = false, bool $force = false): void
    {
        $properties = $mapping['properties'] ?? [];
        $classContent = "<?php\n\nnamespace {$this->namespace};\n\n";
        $classContent .= "use Jot\HfRepository\Entity;\n";
        if (!$isChild) {
            $classContent .= "use Jot\HfRepository\Trait\HasTimestamps;\n";
            $classContent .= "use Jot\HfRepository\Trait\HasLogicRemoval;\n";
        }
        $classContent .= "use OpenApi\Attributes as OA;\n";
        $classContent .= "\n";
        $className = ucfirst(Str::singular($className));
        $swaggerSchema = strtolower(sprintf('%s.%s', preg_replace('/\W+/', '.', $this->namespace), $className));

        $classContent .= "#[OA\Schema(schema: \"$swaggerSchema\")]\n";
        $classContent .= "class $className extends Entity\n{\n\n";
        if (!$isChild) {
            $classContent .= "    use HasLogicRemoval, HasTimestamps;\n\n";
        }

        foreach ($properties as $field => $details) {
            $type = $details['type'] ?? 'object';
            $phpType = $this->mapElasticTypeToPhpType($type);
            $fieldName = Str::snake(Str::singular($field));

            switch ($type) {
                case 'object':
                    $nestedClassName = ucfirst($fieldName);
                    $this->generateEntityFromMapping($details, $nestedClassName, true, $force);
                    $this->line(sprintf('[OK] %s', $nestedClassName));
                    $phpType = "\\$this->namespace\\$nestedClassName";
                    $docSchema = substr(strtolower(preg_replace('/\W+/', '.', $phpType)), 1);
                    $classContent .= "    #[OA\Property(\n";
                    $classContent .= "        property: \"$fieldName\",\n";
                    $classContent .= "        ref: \"#/components/schemas/$docSchema\",\n";
                    $classContent .= "        x: [\"php_type\" => \"$phpType\"]\n";
                    $classContent .= "    )]\n";
                    break;
                case 'nested':
                    $nestedClassName = ucfirst($fieldName);
                    $this->generateEntityFromMapping($details, $nestedClassName, true, $force);
                    $phpType = 'array';
                    $docType = "\\$this->namespace\\{$nestedClassName}[]";
                    $docSchema = substr(strtolower(preg_replace('/\W+/', '.', $docType)), 1, -1);
                    $classContent .= "    #[OA\Property(\n";
                    $classContent .= "        property: \"$fieldName\",\n";
                    $classContent .= "        ref: \"#/components/schemas/$docSchema\",\n";
                    $classContent .= "        type: \"array\",\n";
                    $classContent .= "        items: new OA\Items(ref: \"\$#/components/schemas/$docSchema\"),\n";
                    $classContent .= "        x: [\"php_type\" => \"$docType\"]\n";
                    $classContent .= "    )]\n";
                    break;
                case 'date':
                    $classContent .= "    #[OA\Property(\n";
                    $classContent .= "        property: \"$fieldName\",\n";
                    $classContent .= "        type: \"string\",\n";
                    $classContent .= "        format: \"string\",\n";
                    $classContent .= "        x: [\"php_type\" => \"\\DateTime\"]\n";
                    $classContent .= "    )]\n";
                    break;
                case 'bool':
                    $phpType = 'bool';
                    $classContent .= "    #[OA\Property(\n";
                    $classContent .= "        property: \"$fieldName\",\n";
                    $classContent .= "        type: \"boolean\",\n";
                    $classContent .= "        example: true\n";
                    $classContent .= "    )]\n";
                    break;
                case 'integer':
                case 'long':
                    $phpType = 'int';
                    $classContent .= "    #[OA\Property(\n";
                    $classContent .= "        property: \"$fieldName\",\n";
                    $classContent .= "        type: \"integer\",\n";
                    $classContent .= "        example: 5\n";
                    $classContent .= "    )]\n";
                    break;
                case 'float':
                case 'double':
                    $classContent .= "    #[OA\Property(\n";
                    $classContent .= "        property: \"$fieldName\",\n";
                    $classContent .= "        type: \"number\",\n";
                    $classContent .= "        format: \"float\",\n";
                    $classContent .= "        example: 123.45\n";
                    $classContent .= "    )]\n";
                    break;
                default:
                    $classContent .= "    #[OA\Property(\n";
                    $classContent .= "        property: \"$fieldName\",\n";
                    $classContent .= "        type: \"string\",\n";
                    $classContent .= "        example: \"\"\n";
                    $classContent .= "    )]\n";
                    break;
            }

            $property = Str::camel($field);

            $classContent .= "    protected ?$phpType \$$property = null;\n\n";
        }

        $classContent .= "\n";
        $classContent .= "\n";
        $classContent .= "}\n";

        $filePath = "$this->outputDir/$className.php";
        file_put_contents($filePath, $classContent);
    }

    /**
     * Converts a snake_case string to camelCase or PascalCase if specified.
     *
     * @param string $snakeCase The input string in snake_case format.
     * @param bool $ucFirst Optional. If true, converts the string to PascalCase (uppercase first letter). Defaults to false.
     * @return string The converted string in camelCase or PascalCase format.
     */
    private function snakeToCamelCase(string $snakeCase, bool $ucFirst = false): string
    {
        $camelCase = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $snakeCase))));
        if ($ucFirst) {
            $camelCase = ucfirst($camelCase);
        }
        return $camelCase;
    }

}
