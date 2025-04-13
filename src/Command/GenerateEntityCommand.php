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
class GenerateEntityCommand extends AbstractCommand
{
    protected string $command = 'repo:entity';

    public function configure(): void
    {
        parent::configure();
        $this->setDescription(__('hf-repository.command.entity_description'));
        $this->addOption('index', 'I', InputOption::VALUE_REQUIRED, __('hf-repository.command.mapping_name'));
        $this->addOption('array-fields', 'L', InputOption::VALUE_OPTIONAL, __('hf-repository.command.object_mapped_fields_description'));
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

        $this->force = boolval($this->input->getOption('force'));
        $this->input->getOption('array-fields') && $this->setArrayFields(explode(',', $this->input->getOption('array-fields')));

        $this->createEntities($indexName);
    }
}
