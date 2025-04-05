<?php

declare(strict_types=1);
/**
 * This file is part of hf-repository
 *
 * @link     https://github.com/JotJunior/hf-repository
 * @contact  hf-repository@jot.com.br
 * @license  MIT
 */
return [
    // Exception messages
    'invalid_entity' => 'Invalid entity',
    'record_not_found' => 'Record not found',
    'validation_errors' => 'Validation errors:',
    'failed_create_entity' => 'Failed to create entity',
    'failed_create_entity_instance' => 'Failed to create entity instance',
    'failed_update_entity' => 'Failed to update entity',

    // Validation messages
    'validation_error_with_count' => '{0} (and {1} more errors)',

    // Rate limiting
    'too_many_requests' => 'Too Many Requests.',

    // Command messages
    'command' => [
        // General command messages
        'file_exists' => 'The file %s already exists. Overwrite file? [y/n/a]',
        'skip_file' => '[SKIP] %s',
        'index_not_exists' => 'Index %s does not exist.',
        'confirm_crud_creation' => 'Are you sure you want to create a CRUD for index %s? [Y/n]',
        'aborting' => 'Aborting...',
        'creating_crud' => 'Creating the CRUD for index %s...',
        'start_creating_entities' => 'Start creating entities...',
        'start_creating_repository' => 'Start creating repository...',
        'start_creating_controller' => 'Start creating controller...',
        'enter_index_name' => 'Please enter the elasticsearch index name:',
        'repository_exists' => 'Repository class already exists at %s',

        // Command descriptions
        'controller_description' => 'Create a repository based controller class based on an elasticsearch index.',
        'crud_description' => 'Creates a complete crud controller for a given Elasticsearch index.',
        'entity_description' => 'Creating entity classes based on the elasticsearch mapping configuration.',
        'repository_description' => 'Create a repository class.',
    ],
];
