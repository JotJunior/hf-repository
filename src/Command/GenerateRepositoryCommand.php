<?php

declare(strict_types=1);

namespace Jot\HfRepository\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Stringable\Str;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class GenerateRepositoryCommand extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('repo:create');
        $this->setDescription('Create a repository class.');
        $this->addUsage('repo:create --index=index_name');
        $this->addOption('index', 'I', InputOption::VALUE_REQUIRED, 'Elasticsearch index name.');
        $this->addOption('namespace', 'S', InputOption::VALUE_OPTIONAL, 'Repository class namespace.', 'App\\Repository');
        $this->addOption('force', 'F', InputOption::VALUE_NONE, 'Replace existing repository.');
    }

    public function handle()
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', \dirname(__DIR__, 4));
        }
        $repositoryDirectory = BASE_PATH . '/app/Repository';

        if (!is_dir($repositoryDirectory)) {
            @mkdir($repositoryDirectory, 0755, true);
        }

        $name = $this->input->getOption('index');

        $namespace = $this->input->getOption('namespace');
        $force = $this->input->getOption('force');

        if (str_contains($name, '/')) {
            $subDirectories = explode('/', $name);
            $name = array_pop($subDirectories);
            $repositoryDirectory .= '/' . implode('/', $subDirectories);
            $this->line($repositoryDirectory);

            if (!is_dir($repositoryDirectory)) {
                @mkdir($repositoryDirectory, 0755, true);
            }
            if (count($subDirectories) > 0)
                $namespace .= '\\' . implode('\\', $subDirectories);
        }

        $className = ucfirst(Str::camel(Str::singular($name))) . 'Repository';

        $entity = str_replace('Repository', '', sprintf('App\\Entity\\%s\\%s', $className, $className));

        $template = $this->createTemplate($className, $entity, $namespace);

        $repositoryFile = sprintf('%s/%s.php', $repositoryDirectory, $className);

        if (file_exists($repositoryFile) && !$force) {
            $this->line(sprintf('<fg=yellow>[SKIP]</> Repository class already exists at %s', $repositoryFile));
            return;
        }
        file_put_contents($repositoryFile, $template);
        $this->line(sprintf('<fg=green>[OK]</> Repository class created at %s', $repositoryFile));
    }

    private function createTemplate(string $className, string $entityClass, string $namespace): string
    {
        $template = file_get_contents(__DIR__ . '/stubs/repository.stub');
        return str_replace(['{{class}}', '{{entity}}', '{{namespace}}'], [$className, $entityClass, $namespace], $template);

    }

}
