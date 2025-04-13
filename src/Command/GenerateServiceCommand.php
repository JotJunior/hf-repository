<?php

declare(strict_types=1);
/**
 * This file is part of the hf_repository module, a package build for Hyperf framework that is responsible for
 * manage controllers, entities and repositories.
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
class GenerateServiceCommand extends AbstractCommand
{
    protected string $command = 'repo:service';

    public function configure(): void
    {
        parent::configure();
        $this->setDescription(__('hf-repository.command.service_description'));
        $this->addUsage('repo:service --index=index_name [--force]');
        $this->addOption('index', 'I', InputOption::VALUE_REQUIRED, __('hf-repository.command.index_name_description'));
        $this->addOption('force', 'F', InputOption::VALUE_NONE, __('hf-repository.command.force_description'));
    }

    public function handle()
    {
        try {
            $indexName = $this->getIndexName();
        } catch (IndexNotFoundException $e) {
            $this->failed($e->getMessage());
            return;
        }

        $this->force = $this->input->getOption('force');

        $this->createService($indexName);
    }
}
