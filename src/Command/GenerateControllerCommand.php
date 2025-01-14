<?php

declare(strict_types=1);

namespace Jot\HfRepository\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Stringable\Str;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class GenerateControllerCommand extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('repo:controller');
        $this->setDescription('Create a repository based controller class based on an elasticsearch index.');
        $this->addUsage('repo:controller --index=index_name');
        $this->addOption('index', 'I', InputOption::VALUE_REQUIRED, 'Elasticsearch index name.');
        $this->addOption('force', 'F', InputOption::VALUE_NONE, 'Replace existing controller.');
    }

    public function handle()
    {

        if (!defined('BASE_PATH')) {
            define('BASE_PATH', \dirname(__DIR__, 4));
        }
        $controllerDirectory = BASE_PATH . '/app/Controller';

        if (!is_dir($controllerDirectory)) {
            @mkdir($controllerDirectory, 0755, true);
        }

        $name = $this->input->getOption('index');
        $force = $this->input->getOption('force');

        $namespace = 'App\\Controller\\';
        if (str_contains($name, '/')) {
            $subDirectories = explode('/', $name);
            $name = array_pop($subDirectories);
            $name = Str::singular($name);
            $controllerDirectory .= '/' . implode('/', $subDirectories);

            if (!is_dir($controllerDirectory)) {
                @mkdir($controllerDirectory, 0755, true);
            }
            if (count($subDirectories) > 0)
                $namespace .= '\\' . implode('\\', $subDirectories);
        }

        $serviceName = Str::snake(Str::singular($name));
        $className = ucfirst(Str::camel($serviceName));

        $variables = [
            $name,
            $serviceName,
            $className,
            $namespace
        ];

        $template = $this->createTemplate($variables);

        $controllerFile = sprintf('%s/%sController.php', $controllerDirectory, $className);

        if (file_exists($controllerFile) && !$force) {
            $this->line(sprintf('<fg=yellow>[SKIP]</> Controller class already exists at %s', $controllerFile));
            return;
        }
        file_put_contents($controllerFile, $template);
        $this->line(sprintf('<fg=green>[OK]</> Controller class created at %s', $controllerFile));
    }

    private function createTemplate(array $variables): string
    {
        $template = file_get_contents(__DIR__ . '/stubs/controller.stub');
        return str_replace(['{{service_name}}', '{{schema_name}}', '{{class_name}}', '{{namespace}}'], $variables, $template);

    }

}
