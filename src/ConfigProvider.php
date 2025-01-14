<?php

namespace Jot\HfRepository;

use Jot\HfRepository\Command\GenerateControllerCommand;
use Jot\HfRepository\Command\GenerateRepositoryCommand;
use Jot\HfRepository\Command\GenerateEntityCommand;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [],
            'commands' => [
                GenerateControllerCommand::class,
                GenerateEntityCommand::class,
                GenerateRepositoryCommand::class,
            ],
            'listeners' => [],
            'publish' => [],
        ];
    }
}