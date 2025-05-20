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
    'invalid_entity' => 'Entidade inválida',
    'record_not_found' => 'Registro não encontrado',
    'validation_errors' => 'Erros de validação:',
    'failed_create_entity' => 'Falha ao criar entidade',
    'failed_create_entity_instance' => 'Falha ao criar instância da entidade',
    'failed_update_entity' => 'Falha ao atualizar entidade',
    'validation_error_with_count' => ':error (e mais :count erros]',
    'too_many_requests' => 'Muitas requisições.',
    'command' => [
        'file_exists' => 'O arquivo :filename já existe. Sobrescrever arquivo? [y/n/a]',
        'skip_file' => '[PULAR] Ignorando :filename',
        'index_not_exists' => 'Índice :index não existe.',
        'index_not_found' => 'Índice <fg=cyan>:index</> não encontrado',
        'confirm_crud_creation' => 'Tem certeza que deseja criar um CRUD para o índice :index? [Y/n]',
        'aborting' => 'Abortando...',
        'creating_crud' => 'Criando o CRUD para o índice :index...',
        'start_creating_entities' => 'Iniciando a criação de entidades...',
        'start_creating_repository' => 'Iniciando a criação do repositório...',
        'start_creating_service' => 'Iniciando a criação do serviço...',
        'start_creating_controller' => 'Iniciando a criação do controlador...',
        'enter_index_name' => 'Por favor, digite o nome do índice elasticsearch:',
        'repository_exists' => 'A classe do repositório já existe em :class',
        'controller_description' => 'Cria uma classe de controlador baseada em repositório a partir de um índice elasticsearch.',
        'crud_description' => 'Cria um controlador CRUD completo para um determinado índice Elasticsearch.',
        'entity_description' => 'Criando classes de entidade baseadas na configuração de mapeamento do elasticsearch.',
        'repository_description' => 'Cria uma classe de repositório.',
        'mapping_name_description' => 'Nome do mapeamento Elasticsearch:',
        'index_name_description' => 'Nome do índice Elasticsearch:',
        'force_description' => 'Substituir arquivos existentes.',
        'object_mapped_fields_description' => 'Campos mapeados como objetos, separados por vírgula.',
        'api_version_description' => 'Versão da API (v1, v2, etc].',
        'unable_create_before' => 'Antes de criar o controlador, você deve criar as <fg=cyan>entidades</>, o <fg=cyan>repositório</> e o <fg=cyan>serviço</>.',
        'enable_shield_strategy' => 'Escolha a estratégia de validação do hf_shield [bearer|session|public]',
        'api_version' => 'Versão da API:',
        'scope_module_name' => 'Nome do módulo de escopo:',
        'scope_resource_name' => 'Nome do recurso de escopo:',
        'crud_creation_question' => 'Você está prestes a criar um CRUD para o índice <fg=yellow>:index</> com versão da api <fg=yellow>:version</>.',
        'etcd_description' => 'Publicar configuração local no ETCD',
        'config_key_description' => 'A chave a ser publicada no ETCD',
        'etcd_key_exists' => 'A chave já existe, deseja sobrescrevê-la? [y/N]',
        'property_not_found' => 'A propriedade :property nao foi encontrada.',
        'mapping_name' => 'Nome do mapping',
        'service_description' => 'Descrição do serviço',
    ],
    'property_not_found' => 'A propriedade :property nao foi encontrada.',
];
