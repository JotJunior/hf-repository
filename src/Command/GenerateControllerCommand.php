<?php

declare(strict_types=1);
/**
 * This file is part of hf-repository
 *
 * @link     https://github.com/JotJunior/hf-repository
 * @contact  hf-repository@jot.com.br
 * @license  MIT
 */

namespace Jot\HfRepository\Command;

use Hyperf\Command\Annotation\Command;
use Symfony\Component\Console\Input\InputOption;

use function Hyperf\Translation\__;

#[Command]
class GenerateControllerCommand extends AbstractCommand
{
    protected string $command = 'repo:controller';

    public function configure(): void
    {
        parent::configure();
        $this->setDescription(__('hf-repository.command.controller_description'));
        $this->addUsage('repo:controller --index=index_name [--api-version=v1] [--force]');
        $this->addOption('index', 'I', InputOption::VALUE_REQUIRED, 'Elasticsearch index name.');
        $this->addOption('api-version', 'A', InputOption::VALUE_REQUIRED, 'Api version (v1, v2, etc).', 'v1');
        $this->addOption('force', 'F', InputOption::VALUE_NONE, 'Replace existing controller.');
    }

    public function handle()
    {
        $indexName = $this->getIndexName();

        $apiVersion = $this->input->getOption('api-version');
        $this->force = $this->input->getOption('force');

        $this->createController($indexName, $apiVersion);
    }
}
