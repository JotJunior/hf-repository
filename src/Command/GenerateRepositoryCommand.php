<?php

declare(strict_types=1);

namespace Jot\HfRepository\Command;

use Hyperf\Command\Annotation\Command;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class GenerateRepositoryCommand extends AbstractCommand
{

    protected string $command = 'repo:repository';

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('Create a repository class.');
        $this->addUsage('repo:repository --index=index_name');
        $this->addOption('index', 'I', InputOption::VALUE_REQUIRED, 'Elasticsearch index name.');
        $this->addOption('force', 'F', InputOption::VALUE_NONE, 'Replace existing repository.');
    }

    public function handle()
    {

        $indexName = $this->getIndexName();

        $this->force = boolval($this->input->getOption('force'));

        $this->createRepository($indexName);
    }

}
