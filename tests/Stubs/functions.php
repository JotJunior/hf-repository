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

namespace Jot\HfRepository\Tests\Stubs;

/**
 * Mock for the global __ function used for translations.
 *
 * @param string $key The translation key
 * @param array $replace The replacement parameters
 * @param null|string $locale The locale
 * @return string The translated string
 */
function __($key, array $replace = [], ?string $locale = null): string
{
    // Simple translation mapping for testing
    $translations = [
        'hf-repository.invalid_entity' => 'Invalid entity',
        'hf-repository.record_not_found' => 'Record not found',
        'hf-repository.validation_errors' => 'Validation errors',
        'hf-repository.validation_error_with_count' => '{0} (and {1} more errors)',
        'hf-repository.failed_create_entity' => 'Failed to create entity',
        'hf-repository.failed_create_entity_instance' => 'Failed to create entity instance',
        'hf-repository.failed_update_entity' => 'Failed to update entity',
        'hf-repository.too_many_requests' => 'Too many requests',
    ];

    $translation = $translations[$key] ?? $key;

    // Replace placeholders with values
    if (! empty($replace) && is_string($translation)) {
        foreach ($replace as $index => $value) {
            $translation = str_replace('{' . $index . '}', $value, $translation);
        }
    }

    return $translation;
}
