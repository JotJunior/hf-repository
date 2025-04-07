<?php

declare(strict_types=1);
/**
 * This file is part of the hf_repository module, a package build for Hyperf framework that is responsible for manage controllers, entities and repositories.
 *
 * @author   Joao Zanon <jot@jot.com.br>
 * @link     https://github.com/JotJunior/hf-repository
 * @license  MIT
 */

namespace Jot\HfRepository;

use Hyperf\Swagger\HttpServer;
use Jot\HfRepository\Command\GenerateControllerCommand;
use Jot\HfRepository\Command\GenerateCrudCommand;
use Jot\HfRepository\Command\GenerateEntityCommand;
use Jot\HfRepository\Command\GenerateRepositoryCommand;
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
                RepositoryInterface::class => Repository::class,
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
            'publish' => [
                [
                    'id' => 'translation-en',
                    'description' => 'The english translation messages for hf-repository.',
                    'source' => __DIR__ . '/../storage/languages/en/hf-repository.php',
                    'destination' => BASE_PATH . '/storage/languages/en/hf-repository.php',
                ],
                [
                    'id' => 'translation-pt_BR',
                    'description' => 'The brazilian portuguese translation messages for hf-repository.',
                    'source' => __DIR__ . '/../storage/languages/pt_BR/hf-repository.php',
                    'destination' => BASE_PATH . '/storage/languages/pt_BR/hf-repository.php',
                ],
            ],
            'exceptions' => [
                'handler' => [
                    'http' => [
                        ControllerExceptionHandler::class,
                    ],
                ],
            ],
        ];
    }
}
