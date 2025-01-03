<?php

declare(strict_types=1);

namespace Jot\HfRepository\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Di\Annotation\Inject;
use Jot\HfElastic\ElasticsearchService;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class GenerateEntityCommand extends HyperfCommand
{
    #[Inject]
    protected ElasticsearchService $esClient;

    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('jot:create-entity');
        $this->setDescription('Elasticsearch mappings migrations command.');
        $this->addOption('mapping', 'M', InputOption::VALUE_REQUIRED, 'Elasticsearch mapping name', '');
        $this->addOption('namespace', 'N', InputOption::VALUE_REQUIRED, 'Entity namespace', '');
        $this->addOption('force', 'F', InputOption::VALUE_OPTIONAL, 'Rewrite mapping file ', false);
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
        $namespace = $this->input->getOption('namespace');
        $force = boolval($this->input->getOption('force'));

        $entityDirectory = BASE_PATH . '/app/Entity';
        $this->line('Starting to generate entity...');
        $this->generateEntityFromMapping($this->fetchMapping($mapping), $mapping, $namespace, $entityDirectory, $force);
        $this->line('Entity generated successfully!');

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

    /**
     * Generates a PHP entity class file based on the provided mapping.
     *
     * @param array $mapping The mapping definition containing properties and their attributes.
     * @param string $className The name of the class to generate.
     * @param string $namespace The namespace for the generated class.
     * @param string $outputDir The directory where the generated class file will be stored.
     * @param bool $force Optional. If true, overwrites existing files with the same name. Defaults to false.
     * @return void
     */
    private function generateEntityFromMapping(array $mapping, string $className, string $namespace, string $outputDir, bool $force = false): void
    {
        $properties = $mapping['properties'] ?? [];
        $classContent = "<?php\n\nnamespace {$namespace};\n\n";
        $classContent .= "class $className\n{\n";

        foreach ($properties as $field => $details) {
            $type = $details['type'] ?? 'mixed';
            $phpType = $this->mapElasticTypeToPhpType($type);

            if (($type === 'object' || $type === 'nested') && isset($details['properties'])) {
                $nestedClassName = $this->snakeToCamelCase($field, true);
                $this->generateEntityFromMapping($details, $nestedClassName, $namespace, $outputDir, $force);
                $phpType = "\\$namespace\\$nestedClassName";
            }

            $property = $this->snakeToCamelCase($field);

            $classContent .= "    /**\n";
            $classContent .= "     * @var $phpType\n";
            $classContent .= "     */\n";
            $classContent .= "    private \$$property;\n\n";
        }

        $classContent .= "    // Add getter and setter methods as needed\n";
        $classContent .= "}\n";

        $filePath = "$outputDir/$className.php";
        if (file_exists($filePath) && !$force) {
            file_put_contents($filePath, $classContent);
        }
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
