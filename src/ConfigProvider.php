<?php

namespace Jot\HfRepository;

use Hyperf\Swagger\HttpServer;
use Jot\HfRepository\Command\GenerateControllerCommand;
use Jot\HfRepository\Command\GenerateCrudCommand;
use Jot\HfRepository\Command\GenerateRepositoryCommand;
use Jot\HfRepository\Command\GenerateEntityCommand;
use Jot\HfRepository\Swagger\SwaggerHttpServer;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                HttpServer::class => SwaggerHttpServer::class
            ],
            'commands' => [
                GenerateControllerCommand::class,
                GenerateEntityCommand::class,
                GenerateRepositoryCommand::class,
                GenerateCrudCommand::class,
            ],
            'listeners' => [
                RequiredConfigListener::class,
            ],
            'publish' => [],
        ];
    }
}