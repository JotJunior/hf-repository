<?php

declare(strict_types=1);

namespace Jot\HfRepository\Command;

use Hyperf\Command\Annotation\Command;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class GenerateEntityCommand extends AbstractCommand
{

    protected string $command = 'repo:entity';

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('Creating entity classes based on the elasticsearch mapping configuration.');
        $this->addOption('index', 'I', InputOption::VALUE_REQUIRED, 'Elasticsearch mapping name');
        $this->addOption('force', 'F', InputOption::VALUE_NONE, 'Rewrite mapping file');
    }


    public function handle()
    {

        $indexName = $this->getIndexName();

        $this->force = boolval($this->input->getOption('force'));

        $this->createEntities($indexName);
    }

}
