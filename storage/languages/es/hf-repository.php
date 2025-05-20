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
    'invalid_entity' => 'Entidad inválida',
    'record_not_found' => 'Registro no encontrado',
    'validation_errors' => 'Errores de validación:',
    'failed_create_entity' => 'Error al crear entidad',
    'failed_create_entity_instance' => 'Error al crear instancia de entidad',
    'failed_update_entity' => 'Error al actualizar entidad',
    'validation_error_with_count' => ':error (y :count errores más]',
    'too_many_requests' => 'Demasiadas solicitudes.',
    'command' => [
        'file_exists' => 'El archivo :filename ya existe. ¿Sobrescribir archivo? [y/n/a]',
        'skip_file' => '[OMITIR] Ignorando :filename',
        'index_not_exists' => 'El índice :index no existe.',
        'index_not_found' => 'Índice <fg=cyan>:index</> no encontrado',
        'confirm_crud_creation' => '¿Está seguro de que desea crear un CRUD para el índice :index? [Y/n]',
        'aborting' => 'Abortando...',
        'creating_crud' => 'Creando el CRUD para el índice :index...',
        'start_creating_entities' => 'Comenzando a crear entidades...',
        'start_creating_repository' => 'Comenzando a crear repositorio...',
        'start_creating_service' => 'Comenzando a crear servicio...',
        'start_creating_controller' => 'Comenzando a crear controlador...',
        'enter_index_name' => 'Por favor, ingrese el nombre del índice de elasticsearch:',
        'repository_exists' => 'La clase del repositorio ya existe en :class',
        'controller_description' => 'Crear una clase controlador basada en repositorio basada en un índice de elasticsearch.',
        'crud_description' => 'Crea un controlador CRUD completo para un índice de Elasticsearch dado.',
        'entity_description' => 'Creando clases de entidad basadas en la configuración de mapeo de elasticsearch.',
        'repository_description' => 'Crear una clase de repositorio.',
        'mapping_name_description' => 'Nombre del mapeo de Elasticsearch:',
        'index_name_description' => 'Nombre del índice de Elasticsearch:',
        'force_description' => 'Reemplazar archivos existentes.',
        'object_mapped_fields_description' => 'Campos mapeados como objetos, separados por coma.',
        'api_version_description' => 'Versión de la API (v1, v2, etc].',
        'unable_create_before' => 'Antes de crear el controlador, debe crear las <fg=cyan>entidades</>, el <fg=cyan>repositorio</> y el <fg=cyan>servicio</>.',
        'enable_shield_strategy' => 'Elija la estrategia de validación hf_shield [bearer|session|public]',
        'api_version' => 'Versión de API:',
        'scope_module_name' => 'Nombre del módulo de ámbito:',
        'scope_resource_name' => 'Nombre del recurso de ámbito:',
        'crud_creation_question' => 'Está a punto de crear un CRUD para el índice <fg=yellow>:index</> con versión de api <fg=yellow>:version</>.',
        'etcd_description' => 'Publicar configuración local en ETCD',
        'config_key_description' => 'La clave a publicar en ETCD',
        'etcd_key_exists' => 'La clave ya existe, ¿desea sobrescribirla? [y/N]',
        'mapping_name' => 'Mapeo',
        'property_not_found' => 'Propiedad no encontrada',
        'service_description' => 'Crear una clase de servicio.',
    ],
    'property_not_found' => 'Propiedad :property no encontrada',
];
