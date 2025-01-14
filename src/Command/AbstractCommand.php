<?php

namespace Jot\HfRepository\Command;

use Elasticsearch\Client;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Stringable\Str;
use Jot\HfElastic\ClientBuilder;
use Psr\Container\ContainerInterface;

class AbstractCommand extends HyperfCommand
{

    protected Client $esClient;
    protected string $command = '';
    protected bool $force = false;

    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct($this->command);
        $this->esClient = $this->container->get(ClientBuilder::class)->build();
    }

    /**
     * Creates a controller file based on a template and provided parameters.
     *
     * @param string $indexName The name of the index that serves as the base for generating the controller.
     * @param string $apiVersion The version of the API for which the controller is being created.
     *
     * @return void
     */
    protected function createController(string $indexName, string $apiVersion): void
    {

        $namespace = sprintf('App\\Controller\\%s', ucfirst($apiVersion));
        $serviceName = Str::snake($indexName);
        $schemaName = Str::singular($serviceName);
        $className = ucfirst(Str::camel($schemaName));

        $variables = [
            'api_version' => $apiVersion,
            'schema_name' => $schemaName,
            'service_name' => $serviceName,
            'class_name' => $className,
            'namespace' => $namespace,
        ];

        $controllerDirectory = $this->outputDir(sprintf('/app/Controller/%s', ucfirst($apiVersion)));

        $template = $this->parseTemplate('controller', $variables);

        $controllerFile = sprintf('%s/%sController.php', $controllerDirectory, $className);

        if (file_exists($controllerFile) && !$this->force) {
            $this->line(sprintf('<fg=yellow>[SKIP]</> Controller class already exists at %s', $controllerFile));
            return;
        }
        file_put_contents($controllerFile, $template);
        $this->line(sprintf('<fg=green>[OK]</> Controller class created at %s', $controllerFile));
    }

    /**
     * Creates the necessary entity classes based on the provided index name and mapping configuration.
     *
     * @param string $indexName The name of the index for which entities need to be created.
     * @return void
     */
    protected function createEntities(string $indexName): void
    {

        $serviceName = Str::snake($indexName);
        $schemaName = Str::singular($serviceName);
        $className = ucfirst(Str::camel($schemaName));
        $namespace = sprintf('App\\Entity\\%s', $className);
        $outputDir = $this->outputDir(sprintf('/app/Entity/%s', $className));

        $this->generateEntityFromMapping($this->fetchMapping($indexName), $className, $namespace, $outputDir, false);

    }

    /**
     * Creates a repository class file for the specified index name.
     *
     * @param string $indexName The name of the index to create the repository for.
     * @return void
     */
    protected function createRepository(string $indexName): void
    {
        $namespace = 'App\\Repository';
        $serviceName = Str::snake($indexName);
        $schemaName = Str::singular($serviceName);
        $className = ucfirst(Str::camel($schemaName));
        $entity = str_replace('Repository', '', sprintf('App\\Entity\\%s\\%s', $className, $className));

        $template = $this->parseTemplate('repository', ['entity' => $entity, 'namespace' => $namespace, 'class_name' => $className]);
        $outputDir = $this->outputDir('/app/Repository');
        $repositoryFile = sprintf('%s/%sRepository.php', $outputDir, $className);

        if (file_exists($repositoryFile) && !$this->force) {
            $this->line(sprintf('<fg=yellow>[SKIP]</> Repository class already exists at %s', $repositoryFile));
            return;
        }

        $this->generateFile($repositoryFile, $template);
    }

    /**
     * Creates a template by replacing placeholders within a template file with provided variables.
     *
     * @param string $name The name of the template file (without extension) to be processed.
     * @param array $variables An associative array of placeholders and their replacement values.
     *
     * @return string The processed template with placeholders replaced by their corresponding values.
     */
    private function parseTemplate(string $name, array $variables): string
    {
        $template = file_get_contents(sprintf('%s/stubs/%s.stub', __DIR__, $name));
        array_walk($variables, function ($value, $key) use (&$template) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        });

        return $template;
    }

    /**
     * Ensures the existence of the specified output directory by creating it if necessary.
     *
     * @param string $path The relative path to the desired output directory.
     * @return string The absolute path to the created or existing output directory.
     */
    private function outputDir(string $path): string
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', \dirname(__DIR__, 4));
        }

        $outputDir = sprintf('%s%s', BASE_PATH, $path);

        if (!is_dir($outputDir)) {
            @mkdir($outputDir, 0755, true);
        }

        return $outputDir;
    }

    /**
     * Retrieves the mapping configuration for the specified index in Elasticsearch.
     *
     * @param string $indexName The name of the Elasticsearch index whose mapping is to be fetched.
     *
     * @return array|null An associative array representing the index mapping if it exists, or null if the mapping cannot be retrieved.
     */
    private function fetchMapping(string $indexName): ?array
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
     * Maps an Elasticsearch data type to its corresponding PHP type.
     *
     * @param string $elasticType The Elasticsearch data type to be mapped.
     *
     * @return string The PHP type equivalent of the provided Elasticsearch data type.
     */
    private function mapElasticTypeToPhpType(string $elasticType): string
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

    /**
     * Generates a PHP entity class based on a provided mapping and other parameters.
     *
     * @param array $mapping The mapping details containing field definitions and their properties.
     * @param string $className The name of the class to be generated.
     * @param string $namespace The namespace for the generated class.
     * @param string $outputDir The directory where the generated class file will be saved.
     * @param bool $isChild Indicates if the current entity is a child entity (default is false).
     *
     * @return void
     */
    private function generateEntityFromMapping(array $mapping, string $className, string $namespace, string $outputDir, bool $isChild = false): void
    {

        $ignoredFields = ['@timestamp', '@version'];
        $readOnlyFields = ['id', 'created_at', 'updated_at', 'deleted', 'removed', '@version', '@timestamp'];
        $traits = 'use HasLogicRemoval, HasTimestamps;';

        if ($isChild) {
            $traits = '';
            $readOnlyFields = [];
        }

        $properties = $mapping['properties'] ?? [];
        $attributes = '';
        foreach ($properties as $field => $details) {
            if (in_array($field, $ignoredFields)) {
                continue;
            }
            $type = $details['type'] ?? 'object';
            $phpType = $this->mapElasticTypeToPhpType($type);
            $fieldName = Str::singular($field);

            switch ($type) {
                case 'object':
                    $nestedClassName = ucfirst(Str::camel($fieldName));
                    $this->generateEntityFromMapping($details, $nestedClassName, $namespace, $outputDir, true);
                    $phpType = "\\$namespace\\$nestedClassName";
                    $docSchema = substr(strtolower(preg_replace('/\W+/', '.', $phpType)), 1);
                    $attributes .= "    #[SA\Property(\n";
                    $attributes .= "        property: \"$fieldName\",\n";
                    $attributes .= "        ref: \"#/components/schemas/$docSchema\",\n";
                    $attributes .= "        x: [\"php_type\" => \"$phpType\"]\n";
                    $attributes .= "    )]\n";
                    break;
                case 'nested':
                    $nestedClassName = ucfirst(Str::camel($fieldName));
                    $this->generateEntityFromMapping($details, $nestedClassName, $namespace, $outputDir, true);
                    $phpType = 'array';
                    $docType = "\\$namespace\\{$nestedClassName}[]";
                    $docSchema = substr(strtolower(preg_replace('/\W+/', '.', $docType)), 1, -1);
                    $attributes .= "    #[SA\Property(\n";
                    $attributes .= "        property: \"$fieldName\",\n";
                    $attributes .= "        ref: \"#/components/schemas/$docSchema\",\n";
                    $attributes .= "        type: \"array\",\n";
                    $attributes .= "        items: new SA\Items(ref: \"\$#/components/schemas/$docSchema\"),\n";
                    $attributes .= "        x: [\"php_type\" => \"$docType\"]\n";
                    $attributes .= "    )]\n";
                    break;
                case 'date':
                    $attributes .= "    #[SA\Property(\n";
                    $attributes .= "        property: \"$fieldName\",\n";
                    $attributes .= "        type: \"string\",\n";
                    $attributes .= "        format: \"string\",\n";
                    $attributes .= in_array($fieldName, $readOnlyFields) ? "        readOnly: true,\n" : "";
                    $attributes .= "        x: [\"php_type\" => \"\\DateTime\"]\n";
                    $attributes .= "    )]\n";
                    break;
                case 'bool':
                case 'boolean':
                    $phpType = 'bool';
                    $attributes .= "    #[SA\Property(\n";
                    $attributes .= "        property: \"$fieldName\",\n";
                    $attributes .= "        type: \"boolean\",\n";
                    $attributes .= in_array($fieldName, $readOnlyFields) ? "        readOnly: true,\n" : "";
                    $attributes .= "        example: true\n";
                    $attributes .= "    )]\n";
                    break;
                case 'integer':
                case 'long':
                    $phpType = 'int';
                    $attributes .= "    #[SA\Property(\n";
                    $attributes .= "        property: \"$fieldName\",\n";
                    $attributes .= "        type: \"integer\",\n";
                    $attributes .= "        example: 5\n";
                    $attributes .= "    )]\n";
                    break;
                case 'float':
                case 'double':
                    $attributes .= "    #[SA\Property(\n";
                    $attributes .= "        property: \"$fieldName\",\n";
                    $attributes .= "        type: \"number\",\n";
                    $attributes .= "        format: \"float\",\n";
                    $attributes .= "        example: 123.45\n";
                    $attributes .= "    )]\n";
                    break;
                default:
                    $attributes .= "    #[SA\Property(\n";
                    $attributes .= "        property: \"$fieldName\",\n";
                    $attributes .= "        type: \"string\",\n";
                    $attributes .= "        example: \"\"\n";
                    $attributes .= "    )]\n";
                    break;
            }

            $property = Str::camel($field);
            $attributes .= "    protected ?$phpType \$$property = null;\n\n";

        }

        $schema = sprintf('%s.%s', str_replace('\\', '.', strtolower($namespace)), Str::snake($className));
        $template = $this->parseTemplate('entity', ['class_name' => $className, 'schema' => $schema, 'attributes' => $attributes, 'namespace' => $namespace, 'traits' => $traits]);
        $fileName = sprintf('%s/%s.php', $outputDir, $className);

        $this->generateFile($fileName, $template);

    }

    /**
     * Generates a file with the specified contents and writes it to the given output location.
     * Prompts the user for confirmation if a file with the same name already exists.
     *
     * @param string $outputFile The path to the file to be generated.
     * @param string $contents The content to be written to the file.
     * @return void
     */
    protected function generateFile(string $outputFile, string $contents): void
    {
        if (file_exists($outputFile) && !$this->force) {
            $answer = $this->ask(sprintf('The file <fg=yellow>%s</> already exists. Overwrite file? [y/n/a]', $outputFile), 'n');
            if ($answer === 'a') {
                $this->force = true;
            } elseif ($answer !== 'y') {
                $this->line(sprintf('<fg=yellow>[SKIP]</> %s', $outputFile));
                return;
            }
        }

        file_put_contents($outputFile, $contents);
        $this->line(sprintf('<fg=green>[OK]</> %s', $outputFile));

    }


    /**
     * Indents the console messages within the provided callback by a specified number of spaces.
     *
     * @param callable $callback The callback containing console messages to be indented.
     * @param int $indentLevel The number of spaces to indent the messages.
     *
     * @return void
     */
    protected function indentBlock(callable $callback, int $indentLevel): void
    {
        $originalOutput = $this->output;

        $this->output = new class($this->output, $indentLevel) extends \Symfony\Component\Console\Output\Output {
            private $output;
            private $indentation;

            public function __construct($output, $indentLevel)
            {
                parent::__construct($output->getVerbosity(), $output->isDecorated(), $output->getFormatter());
                $this->output = $output;
                $this->indentation = str_repeat(' ', $indentLevel);
            }

            protected function doWrite(string $message, bool $newline): void
            {
                $this->output->write($this->indentation . $message, $newline);
            }
        };

        try {
            $callback($this);
        } finally {
            $this->output = $originalOutput;
        }
    }


}