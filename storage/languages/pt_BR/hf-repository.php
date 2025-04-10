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
return [
    // Exception messages
    'invalid_entity' => 'Entidade inválida',
    'record_not_found' => 'Registro não encontrado',
    'validation_errors' => 'Erros de validação:',
    'failed_create_entity' => 'Falha ao criar entidade',
    'failed_create_entity_instance' => 'Falha ao criar instância da entidade',
    'failed_update_entity' => 'Falha ao atualizar entidade',

    // Validation messages
    'validation_error_with_count' => ':error (e mais :count erros)',

    // Rate limiting
    'too_many_requests' => 'Muitas requisições.',

    // Command messages
    'command' => [
        // General command messages
        'file_exists' => 'O arquivo %s já existe. Sobrescrever arquivo? [y/n/a]',
        'skip_file' => '[PULAR] %s',
        'index_not_exists' => 'Índice %s não existe.',
        'index_not_found' => 'Índice <fg=cyan>:index</> não foi encontrado.',
        'confirm_crud_creation' => 'Tem certeza que deseja criar um CRUD para o índice %s? [Y/n]',
        'aborting' => 'Abortando...',
        'creating_crud' => 'Criando o CRUD para o índice %s...',
        'start_creating_entities' => 'Iniciando a criação de entidades...',
        'start_creating_repository' => 'Iniciando a criação do repositório...',
        'start_creating_controller' => 'Iniciando a criação do controlador...',
        'enter_index_name' => 'Por favor, digite o nome do índice elasticsearch:',
        'repository_exists' => 'A classe do repositório já existe em %s',

        // Command descriptions
        'controller_description' => 'Cria uma classe de controlador baseada em repositório a partir de um índice elasticsearch.',
        'crud_description' => 'Cria um controlador CRUD completo para um determinado índice Elasticsearch.',
        'entity_description' => 'Cria classes de entidade baseadas na configuração de mapeamento do elasticsearch.',
        'repository_description' => 'Cria uma classe de repositório.',

        // Controller creation commands
        'unable_create_before' => 'Antes de criar o controlador, você deve criar as <fg=cyan>entidades</>, <fg=cyan>repositório</> e <fg=cyan>serviço</>.',
        'enable_shield_strategy' => 'Escolha a estratégia de validação do hf_shield [bearer|session|public]',
        'api_version' => 'API Version:',
        'scope_module_name' => 'Scope module name:',
        'scope_resource_name' => 'Scope resource name:',

    ],
];
