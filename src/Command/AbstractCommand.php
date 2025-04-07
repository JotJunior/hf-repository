<?php

declare(strict_types=1);
/**
 * This file is part of the hf_repository module, a package build for Hyperf framework that is responsible for manage controllers, entities and repositories.
 *
 * @author   Joao Zanon <jot@jot.com.br>
 * @link     https://github.com/JotJunior/hf-repository
 * @license  MIT
 */

namespace Jot\HfRepository\Command;

use Elasticsearch\Client;
use Exception;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Stringable\Str;
use Jot\HfElastic\ClientBuilder;
use Jot\HfRepository\Exception\IndexNotFoundException;
use Psr\Container\ContainerInterface;
use function Hyperf\Translation\__;

class AbstractCommand extends HyperfCommand
{
    use HfFriendlyLinesTrait;

    protected Client $esClient;

    protected string $command = '';

    protected string $indexPrefix = '';

    protected bool $force = false;

    protected array $arrayFields = [];

    protected string $moduleName;

    protected string $apiVersion;
    protected string $middlewareStrategy;

    public function __construct(
        protected ContainerInterface       $container,
        protected readonly ConfigInterface $config
    ) {
        parent::__construct($this->command);
        $this->esClient = $this->container->get(ClientBuilder::class)->build();
        $esConfig = $this->config->get('hf_elastic');
        $this->indexPrefix = $esConfig['prefix'] ?? '';
        $shieldConfig = $this->config->get('hf_shield');
        $this->moduleName = $shieldConfig['module_name'] ?? 'api';
        $this->apiVersion = $shieldConfig['api_version'] ?? 'v1';
        $this->middlewareStrategy = $shieldConfig['middleware_strategy'] ?? 'bearer';
    }

    public function setArrayFields(array $fields): self
    {
        $this->arrayFields = $fields;
        return $this;
    }

    /**
     * Creates a controller file based on a template and provided parameters.
     *
     * @param string $indexName the name of the index that serves as the base for generating the controller
     */
    protected function createController(string $indexName): void
    {
        $serviceName = Str::snake($indexName);
        $schemaName = Str::singular($serviceName);
        $className = ucfirst(Str::camel($schemaName));
        $apiVersion = $this->ask('API  version: ', $this->apiVersion);
        $namespace = sprintf('App\Controller\%s', ucfirst($apiVersion));
        $moduleName = $this->ask('Scope module name: ', $this->moduleName);
        $resourceName = $this->ask('Scope resource name: ', $schemaName);
        $middlewareStrategy = match ($this->ask('Enable hf_shield protection? [bearer|session|public]', 'bearer')) {
            'bearer' => 'BearerStrategy::class',
            'session' => 'SessionStrategy::class',
            'signed_jwt' => 'SignedJwtStrategy::class',
            default => 'NoneStrategy::class',
        };

        $variables = [
            'api_version' => $apiVersion,
            'schema_name' => $schemaName,
            'service_name' => $serviceName,
            'class_name' => $className,
            'module_name' => $moduleName,
            'resource_name' => $resourceName,
            'namespace' => $namespace,
            'middleware_strategy' => $middlewareStrategy,
        ];

        $controllerDirectory = $this->outputDir(sprintf('/app/Controller/%s', ucfirst($apiVersion)));

        $template = $this->parseTemplate('controller', $variables);

        $controllerFile = sprintf('%s/%sController.php', $controllerDirectory, $className);

        $this->generateFile($controllerFile, $template);
    }

    /**
     * Ensures the existence of the specified output directory by creating it if necessary.
     *
     * @param string $path the relative path to the desired output directory
     * @return string the absolute path to the created or existing output directory
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
     * Creates a template by replacing placeholders within a template file with provided variables.
     *
     * @param string $name the name of the template file (without extension) to be processed
     * @param array $variables an associative array of placeholders and their replacement values
     *
     * @return string the processed template with placeholders replaced by their corresponding values
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
     * Generates a file with the specified contents and writes it to the given output location.
     * Prompts the user for confirmation if a file with the same name already exists.
     *
     * @param string $outputFile the path to the file to be generated
     * @param string $contents the content to be written to the file
     */
    protected function generateFile(string $outputFile, string $contents): void
    {
        if (file_exists($outputFile) && !$this->force) {
            $answer = $this->ask(sprintf('The file <fg=yellow>%s</> already exists. Overwrite file? [y/n/a]', $outputFile), 'n');
            if ($answer === 'a') {
                $this->force = true;
            } elseif ($answer !== 'y') {
                $this->warning('[SKIP] ' . $outputFile);
                return;
            }
        }

        file_put_contents($outputFile, $contents);
        $this->success($outputFile);
    }

    protected function createService(string $indexName): void
    {
        $className = Str::studly(Str::singular($indexName));

        $variables = [
            'class_name' => $className,
            'cache_prefix' => Str::snake($className) . ':entity',
        ];

        $outputDir = $this->outputDir('/app/Service');
        $serviceFile = sprintf('%s/%sService.php', $outputDir, $className);
        $template = $this->parseTemplate('service', $variables);

        $this->generateFile($serviceFile, $template);
    }

    /**
     * Creates the necessary entity classes based on the provided index name and mapping configuration.
     *
     * @param string $indexName the name of the index for which entities need to be created
     */
    protected function createEntities(string $indexName): void
    {
        $serviceName = Str::snake($indexName);
        $schemaName = Str::singular($serviceName);
        $className = ucfirst(Str::camel($schemaName));
        $namespace = sprintf('App\Entity\%s', $className);
        $outputDir = $this->outputDir(sprintf('/app/Entity/%s', $className));

        $this->generateEntityFromMapping($this->fetchMapping($this->getIndexName(removePrefix: false)), $className, $namespace, $outputDir, false);
    }

    /**
     * Generates a PHP entity class based on a provided mapping and other parameters.
     *
     * @param array $mapping the mapping details containing field definitions and their properties
     * @param string $className the name of the class to be generated
     * @param string $namespace the namespace for the generated class
     * @param string $outputDir the directory where the generated class file will be saved
     * @param bool $isChild indicates if the current entity is a child entity (default is false)
     */
    private function generateEntityFromMapping(array $mapping, string $className, string $namespace, string $outputDir, bool $isChild = false): void
    {
        $ignoredFields = ['@timestamp', '@version'];
        $readOnlyFields = ['id', 'created_at', 'updated_at', 'deleted', 'removed', '@version', '@timestamp'];
        $traits = "    use HasLogicRemoval;\n";
        $traits .= "    use HasTimestamps;\n";

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

            if (in_array(Str::plural($fieldName), $this->arrayFields)) {
                $type = 'array_object';
            }

            switch ($type) {
                case 'object':
                    $nestedClassName = ucfirst(Str::camel($fieldName));
                    $this->generateEntityFromMapping($details, $nestedClassName, $namespace, $outputDir, true);
                    $phpType = "\\{$namespace}\\{$nestedClassName}";
                    $docSchema = substr(strtolower(preg_replace('/\W+/', '.', $phpType)), 1);
                    $attributes .= "    #[SA\\Property(\n";
                    $attributes .= "        property: '{$fieldName}',\n";
                    $attributes .= "        ref: '#/components/schemas/{$docSchema}',\n";
                    $attributes .= "        x: ['php_type' => '{$phpType}']\n";
                    $attributes .= "    )]\n";
                    break;
                case 'nested':
                case 'array_object':
                    $nestedClassName = ucfirst(Str::camel($fieldName));
                    $fieldName = Str::plural($fieldName);
                    $this->generateEntityFromMapping($details, $nestedClassName, $namespace, $outputDir, true);
                    $phpType = 'array';
                    $docType = "\\{$namespace}\\{$nestedClassName}[]";
                    $docSchema = substr(strtolower(preg_replace('/\W+/', '.', $docType)), 1, -1);
                    $attributes .= "    #[SA\\Property(\n";
                    $attributes .= "        property: '{$fieldName}',\n";
                    $attributes .= "        type: 'array',\n";
                    $attributes .= "        items: new SA\\Items(ref: '#/components/schemas/{$docSchema}'),\n";
                    $attributes .= "        x: ['php_type' => '{$docType}']\n";
                    $attributes .= "    )]\n";
                    break;
                case 'date':
                    $attributes .= "    #[SA\\Property(\n";
                    $attributes .= "        property: '{$fieldName}',\n";
                    $attributes .= "        type: 'string',\n";
                    $attributes .= "        format: 'date-time',\n";
                    $attributes .= "        x: ['php_type' => '\\DateTime']\n";
                    $attributes .= "    )]\n";
                    break;
                case 'time':
                    $attributes .= "    #[SA\\Property(\n";
                    $attributes .= "        property: '{$fieldName}',\n";
                    $attributes .= "        type: 'string',\n";
                    $attributes .= "        format: 'string',\n";
                    $attributes .= "        x: ['php_type' => '\\DateTime', 'params' => ['format' => 'H:i:s']]\n";
                    $attributes .= "    )]\n";
                    break;
                case 'date_nanos':
                    $attributes .= "    #[SA\\Property(\n";
                    $attributes .= "        property: '{$fieldName}',\n";
                    $attributes .= "        type: 'string',\n";
                    $attributes .= "        format: 'date-time',\n";
                    $attributes .= in_array($fieldName, $readOnlyFields) ? "        readOnly: true,\n" : '';
                    $attributes .= "        x: ['php_type' => '\\DateTimeImmutable']\n";
                    $attributes .= "    )]\n";
                    break;
                case 'bool':
                case 'boolean':
                    $phpType = 'bool|int';
                    $attributes .= "    #[SA\\Property(\n";
                    $attributes .= "        property: '{$fieldName}',\n";
                    $attributes .= "        type: 'boolean',\n";
                    $attributes .= in_array($fieldName, $readOnlyFields) ? "        readOnly: true,\n" : '';
                    $attributes .= "        example: true\n";
                    $attributes .= "    )]\n";
                    break;
                case 'integer':
                case 'long':
                    $phpType = 'int';
                    $attributes .= "    #[SA\\Property(\n";
                    $attributes .= "        property: '{$fieldName}',\n";
                    $attributes .= "        type: 'integer',\n";
                    $attributes .= in_array($fieldName, $readOnlyFields) ? "        readOnly: true,\n" : '';
                    $attributes .= "        example: 5\n";
                    $attributes .= "    )]\n";
                    break;
                case 'float':
                case 'double':
                    $attributes .= "    #[SA\\Property(\n";
                    $attributes .= "        property: '{$fieldName}',\n";
                    $attributes .= "        type: 'number',\n";
                    $attributes .= "        format: 'float',\n";
                    $attributes .= in_array($fieldName, $readOnlyFields) ? "        readOnly: true,\n" : '';
                    $attributes .= "        example: 123.45\n";
                    $attributes .= "    )]\n";
                    break;
                case 'geo_point':
                    $phpType = 'array';
                    $attributes .= "    #[SA\\Property(\n";
                    $attributes .= "        property: '{$fieldName}',\n";
                    $attributes .= "        type: 'array',\n";
                    $attributes .= "        example: [0.00,0.00]\n";
                    $attributes .= "    )]\n";
                    break;
                default:
                    $attributes .= "    #[SA\\Property(\n";
                    $attributes .= "        property: '{$fieldName}',\n";
                    if (str_ends_with($fieldName, '_identifier')) {
                        $aliasField = explode('_', $fieldName)[0];
                        $attributes .= "        description: 'An alias of {$aliasField} id',\n";
                    }
                    $attributes .= "        type: 'string',\n";
                    $attributes .= (in_array($fieldName, $readOnlyFields) || str_ends_with($fieldName, '_identifier')) ? "        readOnly: true,\n" : '';
                    $attributes .= "        example: ''\n";
                    $attributes .= "    )]\n";
                    break;
            }

            $property = Str::camel($field);
            $attributes .= "    protected null|{$phpType} \${$property} = null;\n\n";
        }

        $schema = sprintf('%s.%s', str_replace('\\', '.', strtolower($namespace)), Str::snake($className));
        $template = $this->parseTemplate('entity', ['class_name' => $className, 'schema' => $schema, 'attributes' => $attributes, 'namespace' => $namespace, 'traits' => $traits]);
        $fileName = sprintf('%s/%s.php', $outputDir, $className);

        $this->generateFile($fileName, $template);
    }

    /**
     * Maps an Elasticsearch data type to its corresponding PHP type.
     *
     * @param string $elasticType the Elasticsearch data type to be mapped
     *
     * @return string the PHP type equivalent of the provided Elasticsearch data type
     */
    private function mapElasticTypeToPhpType(string $elasticType): string
    {
        return match ($elasticType) {
            'date', 'date_nanos' => 'DateTimeInterface',
            'long', 'integer', 'short', 'byte' => 'int',
            'double', 'float' => 'float',
            'boolean' => 'bool',
            'nested' => 'array',
            'object' => 'object',
            default => 'string',
        };
    }

    /**
     * Retrieves the mapping configuration for the specified index in Elasticsearch.
     *
     * @param string $indexName the name of the Elasticsearch index whose mapping is to be fetched
     *
     * @return null|array an associative array representing the index mapping if it exists, or null if the mapping cannot be retrieved
     */
    private function fetchMapping(string $indexName): ?array
    {
        try {
            $response = $this->esClient->indices()->getMapping(['index' => $indexName]);
            return $response[$indexName]['mappings'] ?? null;
        } catch (Exception $e) {
            $this->failed($e->getMessage());
            return null;
        }
    }

    protected function getIndexName(bool $removePrefix = true): string
    {
        $indexName = $this->input->getOption('index');
        if (empty($indexName)) {
            $indexName = $this->ask('Please enter the elasticsearch index name:');
        }

        if ($this->indexPrefix && str_starts_with($indexName, $this->indexPrefix) && $removePrefix) {
            return substr($indexName, strlen($this->indexPrefix) + 1);
        }

        if ($this->indexPrefix && !str_starts_with($indexName, $this->indexPrefix) && !$removePrefix) {
            return sprintf('%s_%s', $this->indexPrefix, $indexName);
        }

        if (!$this->esClient->indices()->exists(['index' => sprintf('%s_%s', $this->indexPrefix, $indexName)])) {
            throw new IndexNotFoundException(__('hf-repository.command.index_not_found', ['index' => $indexName]));
        }

        return $indexName;
    }

    /**
     * Creates a repository class file for the specified index name.
     *
     * @param string $indexName the name of the index to create the repository for
     */
    protected function createRepository(string $indexName): void
    {
        $namespace = 'App\Repository';
        $serviceName = Str::snake($indexName);
        $schemaName = Str::singular($serviceName);
        $className = ucfirst(Str::camel($schemaName));
        $entity = str_replace('Repository', '', sprintf('App\Entity\%s\%s', $className, $className));

        $template = $this->parseTemplate('repository', ['entity' => $entity, 'namespace' => $namespace, 'class_name' => $className]);
        $outputDir = $this->outputDir('/app/Repository');
        $repositoryFile = sprintf('%s/%sRepository.php', $outputDir, $className);

        if (file_exists($repositoryFile) && !$this->force) {
            $this->warn('Repository class already exists at %s', [$repositoryFile]);
            return;
        }

        $this->generateFile($repositoryFile, $template);
    }
}
