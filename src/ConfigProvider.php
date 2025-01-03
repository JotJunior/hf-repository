<?php

namespace Jot\HfRepository;

use Jot\HfRepository\Command\GenerateEntityCommand;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [],
            'commands' => [
                GenerateEntityCommand::class
            ],
            'listeners' => [],
            'publish' => [],
        ];
    }
}