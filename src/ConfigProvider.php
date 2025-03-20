<?php

namespace Jot\HfRepository;

use Hyperf\Swagger\HttpServer;
use Jot\HfRepository\Command\GenerateControllerCommand;
use Jot\HfRepository\Command\GenerateCrudCommand;
use Jot\HfRepository\Command\GenerateRepositoryCommand;
use Jot\HfRepository\Command\GenerateEntityCommand;
use Jot\HfRepository\Entity\EntityFactory;
use Jot\HfRepository\Entity\EntityFactoryInterface;
use Jot\HfRepository\Exception\Handler\ControllerExceptionHandler;
use Jot\HfRepository\Query\QueryParser;
use Jot\HfRepository\Query\QueryParserInterface;
use Jot\HfRepository\Swagger\SwaggerHttpServer;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                HttpServer::class => SwaggerHttpServer::class,
                QueryParserInterface::class => QueryParser::class,
                EntityFactoryInterface::class => EntityFactory::class,
                RepositoryInterface::class => Repository::class
            ],
            'commands' => [
                GenerateControllerCommand::class,
                GenerateEntityCommand::class,
                GenerateRepositoryCommand::class,
                GenerateCrudCommand::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'listeners' => [
                RequiredConfigListener::class,
            ],
            'publish' => [],
            'exceptions' => [
                'handler' => [
                    'http' => [
                        ControllerExceptionHandler::class
                    ]
                ]
            ]
        ];
    }
}
