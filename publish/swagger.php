<?php

declare(strict_types=1);
/**
 * This file is part of the hf_repository module, a package build for Hyperf framework that is responsible for manage controllers, entities and repositories.
 *
 * @author   Joao Zanon <jot@jot.com.br>
 * @link     https://github.com/JotJunior/hf-repository
 * @license  MIT
 */
return [
    'enable' => true,
    'port' => 9500,
    'json_dir' => BASE_PATH . '/storage/swagger',
    'html' => null,
    'url' => '/docs',
    'auto_generate' => true,
    'scan' => [
        'paths' => [
            BASE_PATH . '/app',
            // To enable OAuth2 authentication endpoints documentation in Swagger UI, uncomment the hf-shield
            // package path below:
            // BASE_PATH . '/vendor/jot/hf-shield/src',
        ],
    ],
    'processors' => [
        // users can append their own processors here
    ],
    'server' => [
        'http' => [
            'servers' => [
                [
                    'url' => 'http://localhost',
                    'description' => 'Test Server',
                ],
                [
                    'url' => 'http://127.0.0.1:9501',
                    'description' => 'Test Server',
                ],
            ],
        ],
    ],
];
