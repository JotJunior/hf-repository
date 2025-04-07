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

use Hyperf\Command\Annotation\Command;
use Jot\HfRepository\Exception\IndexNotFoundException;
use Symfony\Component\Console\Input\InputOption;
use function Hyperf\Translation\__;

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
        try {
            $indexName = $this->getIndexName();
        } catch (IndexNotFoundException $e) {
            $this->failed($e->getMessage());
            return;
        }

        $this->force = boolval($this->input->getOption('force'));

        $this->createRepository($indexName);
    }
}
