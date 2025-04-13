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
use Hyperf\Stringable\Str;
use Jot\HfRepository\Exception\IndexNotFoundException;
use Symfony\Component\Console\Input\InputOption;

use function Hyperf\Translation\__;

#[Command]
class GenerateCrudCommand extends AbstractCommand
{
    protected string $command = 'repo:crud';

    public function configure(): void
    {
        parent::configure();
        $this->setDescription(__('hf-repository.command.crud_description'));
        $this->addUsage('repo:crud --index=index_name [--api-version=v1] [--force]');
        $this->addOption('index', 'I', InputOption::VALUE_REQUIRED, __('hf-repository.command.index_name_description'));
        $this->addOption('array-fields', 'L', InputOption::VALUE_OPTIONAL, __('hf-repository.command.object_mapped_fields_description'));
        $this->addOption('api-version', 'A', InputOption::VALUE_REQUIRED, __('hf-repository.command.api_version_description'), 'v1');
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

        $this->input->getOption('array-fields') && $this->setArrayFields(explode(',', $this->input->getOption('array-fields')));

        if (! $this->esClient->indices()->exists(['index' => $this->getIndexName(removePrefix: false)])) {
            $this->line(sprintf(__('hf-repository.command.index_not_exists'), $this->getIndexName(removePrefix: false)));
            $this->newLine();
            return;
        }

        $apiVersion = $this->input->getOption('api-version');
        $this->force = $this->input->getOption('force');

        $this->newLine();
        $this->line(sprintf(__('hf-repository.command.crud_creation_question'), $indexName, $apiVersion));
        $this->line('The elasticsearch index related entities, repository and controller will be created during this process.');

        $this->newLine();
        $confirmation = $this->ask(sprintf(__('hf-repository.command.confirm_crud_creation'), $indexName), 'Y');
        if (! Str::contains($confirmation, ['y', 'Y', 'yes'])) {
            $this->line(__('hf-repository.command.aborting'));
            return;
        }

        $this->newLine();
        $this->line(sprintf(__('hf-repository.command.creating_crud'), $indexName));

        $this->newLine();
        $this->line(__('hf-repository.command.start_creating_entities'));
        $this->createEntities($indexName);

        $this->newLine();
        $this->line(__('hf-repository.command.start_creating_repository'));
        $this->createRepository($indexName);

        $this->newLine();
        $this->line(__('hf-repository.command.start_creating_service'));
        $this->createService($indexName);

        $this->newLine();
        $this->line(__('hf-repository.command.start_creating_controller'));
        $this->createController($indexName, $apiVersion);

        $this->newLine();
    }
}
