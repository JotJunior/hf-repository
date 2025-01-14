<?php

declare(strict_types=1);

namespace Jot\HfRepository\Command;

use Elasticsearch\Client;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Stringable\Str;
use Jot\HfElastic\ClientBuilder;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class GenerateEntityCommand extends HyperfCommand
{
    protected Client $esClient;

    protected string $namespace = 'App\\Entity';
    protected string $outputDir = BASE_PATH . '/app/Entity';
    protected bool $withSwagger = false;
    protected bool $withGraphql = false;
    protected bool $force = false;
    protected array $ignoredFields = ['@timestamp', '@version'];
    protected array $readOnlyFields = ['created_at', 'updated_at', 'deleted', 'removed', '@version', '@timestamp'];


    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('repo:entities');
        $this->setDescription('Creating entity classes based on the elasticsearch mapping configuration.');
        $this->addArgument('mapping', InputOption::VALUE_REQUIRED, 'Elasticsearch mapping name', '');
        $this->addOption('namespace', 'N', InputOption::VALUE_OPTIONAL, 'Entity namespace', 'App\\Entity');
        $this->addOption('output-dir', 'O', InputOption::VALUE_OPTIONAL, 'Entity namespace', BASE_PATH . '/app/Entity');
        $this->addOption('force', 'F', InputOption::VALUE_NONE, 'Rewrite mapping file');
        $this->esClient = $this->container->get(ClientBuilder::class)->build();
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

        $swagger = $this->ask('Enable Swagger annotations? [y/n]', 'y');
        $this->withSwagger = $swagger === 'y';

        $graphql = $this->ask('Enable GraphQL annotations? [y/n]', 'y');
        $this->withGraphql = $graphql === 'y';

        $this->force = boolval($this->input->getOption('force'));

        $mapping = $this->input->getArgument('mapping');
        $mainClassName = ucfirst(Str::camel(Str::singular($mapping)));
        $this->setOutputDir($mainClassName);
        $this->namespace = sprintf('%s\\%s', $this->input->getOption('namespace'), $mainClassName);

        $this->newLine();
        $this->line(sprintf('Starting to generate entity %s...', $mainClassName));

        try {
            $this->generateEntityFromMapping($this->fetchMapping($mapping), $mainClassName);
        } catch (\Throwable $e) {
            $this->line('ERROR: ' . $e->getMessage(), 'error');
            return;
        }
        $this->line(sprintf('<fg=green>[OK]</> Entity <fg=yellow>%s</> generated successfully!', $mainClassName));

    }

    private function setOutputDir(string $entityName): self
    {
        $this->outputDir = sprintf('%s/%s', $this->input->getOption('output-dir'), $entityName);

        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }

        return $this;
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
            $response = $this->esClient->indices()->getMapping(['index' => $indexName]);
            return $response[$indexName]['mappings'] ?? null;
        } catch (\Exception $e) {
            $this->line('<fg=red>[ERROR]</> ' . $e->getMessage());
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
            'keyword', 'text' => 'string',
            'date' => '\DateTime',
            'long', 'integer', 'short', 'byte' => 'int',
            'double', 'float' => 'float',
            'boolean' => 'bool',
            'nested' => 'array',
            'object' => 'object',
            default => 'mixed',
        };
    }

    private function generateEntityFromMapping(array $mapping, string $className, bool $isChild = false): void
    {
        $properties = $mapping['properties'] ?? [];
        $classContent = "<?php\n\n";
        $classContent .= "declare(strict_types=1);\n\n";
        $classContent .= "namespace {$this->namespace};\n\n";
        $classContent .= "use Jot\HfRepository\Entity;\n";
        $classContent .= $isChild ? "" : "use Jot\HfRepository\Trait\HasTimestamps;\n";
        $classContent .= $isChild ? "" : "use Jot\HfRepository\Trait\HasLogicRemoval;\n";
        $classContent .= $this->withSwagger ? "use Hyperf\Swagger\Annotation as SA;\n" : "";
        $classContent .= $this->withGraphql ? "use TheCodingMachine\GraphQLite\Annotations\Field;\n" : "";
        $classContent .= $this->withGraphql ? "use TheCodingMachine\GraphQLite\Annotations\Type;\n" : "";

        $classContent .= "\n";
        $swaggerSchema = strtolower(sprintf('%s.%s', preg_replace('/\W+/', '.', $this->namespace), $className));

        $classContent .= $this->withGraphql ? "#[Type]\n" : "";
        $classContent .= $this->withSwagger ? "#[SA\Schema(schema: \"$swaggerSchema\")]\n" : "";
        $classContent .= "class $className extends Entity\n{\n\n";
        if (!$isChild) {
            $classContent .= "    use HasLogicRemoval, HasTimestamps;\n\n";
        }

        $getters = "\n";
        foreach ($properties as $field => $details) {
            if (in_array($field, $this->ignoredFields)) {
                continue;
            }
            $type = $details['type'] ?? 'object';
            $phpType = $this->mapElasticTypeToPhpType($type);
            $fieldName = Str::singular($field);

            switch ($type) {
                case 'object':
                    $nestedClassName = ucfirst(Str::camel($fieldName));
                    $this->generateEntityFromMapping($details, $nestedClassName, true);
                    $phpType = "\\$this->namespace\\$nestedClassName";
                    $docSchema = substr(strtolower(preg_replace('/\W+/', '.', $phpType)), 1);
                    if ($this->withSwagger) {
                        $classContent .= "    #[SA\Property(\n";
                        $classContent .= "        property: \"$fieldName\",\n";
                        $classContent .= "        ref: \"#/components/schemas/$docSchema\",\n";
                        $classContent .= "        x: [\"php_type\" => \"$phpType\"]\n";
                        $classContent .= "    )]\n";
                    }
                    break;
                case 'nested':
                    $nestedClassName = ucfirst(Str::camel($fieldName));
                    $this->generateEntityFromMapping($details, $nestedClassName, true);
                    $phpType = 'array';
                    $docType = "\\$this->namespace\\{$nestedClassName}[]";
                    $docSchema = substr(strtolower(preg_replace('/\W+/', '.', $docType)), 1, -1);
                    if ($this->withSwagger) {
                        $classContent .= "    #[SA\Property(\n";
                        $classContent .= "        property: \"$fieldName\",\n";
                        $classContent .= "        ref: \"#/components/schemas/$docSchema\",\n";
                        $classContent .= "        type: \"array\",\n";
                        $classContent .= "        items: new SA\Items(ref: \"\$#/components/schemas/$docSchema\"),\n";
                        $classContent .= "        x: [\"php_type\" => \"$docType\"]\n";
                        $classContent .= "    )]\n";
                    }
                    break;
                case 'date':
                    if ($this->withSwagger) {
                        $classContent .= "    #[SA\Property(\n";
                        $classContent .= "        property: \"$fieldName\",\n";
                        $classContent .= "        type: \"string\",\n";
                        $classContent .= "        format: \"string\",\n";
                        $classContent .= in_array($fieldName, $this->readOnlyFields) ? "        readOnly: true,\n" : "";
                        $classContent .= "        x: [\"php_type\" => \"\\DateTime\"]\n";
                        $classContent .= "    )]\n";
                    }
                    break;
                case 'bool':
                case 'boolean':
                    $phpType = 'bool';
                    if ($this->withSwagger) {
                        $classContent .= "    #[SA\Property(\n";
                        $classContent .= "        property: \"$fieldName\",\n";
                        $classContent .= "        type: \"boolean\",\n";
                        $classContent .= "        example: true\n";
                        $classContent .= in_array($fieldName, $this->readOnlyFields) ? "        ,readOnly: true,\n" : "";
                        $classContent .= "    )]\n";
                    }
                    break;
                case 'integer':
                case 'long':
                    $phpType = 'int';
                    if ($this->withSwagger) {
                        $classContent .= "    #[SA\Property(\n";
                        $classContent .= "        property: \"$fieldName\",\n";
                        $classContent .= "        type: \"integer\",\n";
                        $classContent .= "        example: 5\n";
                        $classContent .= "    )]\n";
                    }
                    break;
                case 'float':
                case 'double':
                    if ($this->withSwagger) {
                        $classContent .= "    #[SA\Property(\n";
                        $classContent .= "        property: \"$fieldName\",\n";
                        $classContent .= "        type: \"number\",\n";
                        $classContent .= "        format: \"float\",\n";
                        $classContent .= "        example: 123.45\n";
                        $classContent .= "    )]\n";
                    }
                    break;
                default:
                    if ($this->withSwagger) {
                        $classContent .= "    #[SA\Property(\n";
                        $classContent .= "        property: \"$fieldName\",\n";
                        $classContent .= "        type: \"string\",\n";
                        $classContent .= "        example: \"\"\n";
                        $classContent .= "    )]\n";
                    }
                    break;
            }

            $property = Str::camel($field);

            $classContent .= "    protected ?$phpType \$$property = null;\n\n";

            $methodName = sprintf('get%s()', ucfirst($property));
            if ($this->withGraphql) {
                $getters .= "    #[Field]\n";
                $getters .= "    public function $methodName: ?$phpType { return \$this->$property; }\n\n";
            }

        }

        $classContent .= $getters;

        $classContent .= "\n\n";
        $classContent .= "}\n";

        $filePath = "$this->outputDir/$className.php";

        if (file_exists($filePath) && !$this->force) {
            $answer = $this->ask(sprintf('Entity <fg=yellow>%s</> already exists. Overwrite file? [y/n/a]', $className), 'n');
            if ($answer === 'a') {
                $this->force = true;
            } elseif ($answer !== 'y') {
                $this->line(sprintf('<fg=yellow>[SKIP]</> %s', $className));
                return;
            }
        }
        file_put_contents($filePath, $classContent);
        $this->line(sprintf('<fg=green>[OK]</> %s', $className));
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
