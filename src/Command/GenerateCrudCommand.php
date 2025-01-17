<?php

declare(strict_types=1);

namespace Jot\HfRepository\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Stringable\Str;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class GenerateCrudCommand extends AbstractCommand
{

    protected string $command = 'repo:crud';

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('Creates a complete crud controller for a given Elasticsearch index.');
        $this->addUsage('repo:crud --index=index_name [--api-version=v1] [--force]');
        $this->addOption('index', 'I', InputOption::VALUE_REQUIRED, 'Elasticsearch index name.');
        $this->addOption('api-version', 'A', InputOption::VALUE_REQUIRED, 'Api version (v1, v2, etc).', 'v1');
        $this->addOption('force', 'F', InputOption::VALUE_NONE, 'Replace existing controller.');
    }

    public function handle()
    {

        $indexName = $this->getIndexName();

        if (!$this->esClient->indices()->exists(['index' => $this->getIndexName(removePrefix: false)])) {
            $this->line(sprintf('Index <fg=yellow>%s</> does not exist.', $this->getIndexName(removePrefix: false)));
            $this->newLine();
            return;
        }

        $apiVersion = $this->input->getOption('api-version');
        $this->force = $this->input->getOption('force');

        $this->newLine();
        $this->line(sprintf('You are about to create a CRUD for index <fg=yellow>%s</> with api version <fg=yellow>%s</>.', $indexName, $apiVersion));
        $this->line('The elasticsearch index related entities, repository and controller will be created during this process.');

        $this->newLine();
        $confirmation = $this->ask(sprintf('Are you sure you want to create a CRUD for index <fg=yellow>%s</>? [Y/n]', $indexName), 'Y');
        if (!Str::contains($confirmation, ['y', 'Y', 'yes'])) {
            $this->line('Aborting...');
            return;
        }

        $this->newLine();
        $this->line(sprintf('Creating the CRUD for index <fg=yellow>%s</>...', $indexName));

        $this->newLine();
        $this->line('Start creating entities...');
        $this->createEntities($indexName);

        $this->newLine();
        $this->line('Start creating repository...');
        $this->createRepository($indexName);

        $this->newLine();
        $this->line('Start creating controller...');
        $this->createController($indexName, $apiVersion);

        $this->newLine();

    }

}
