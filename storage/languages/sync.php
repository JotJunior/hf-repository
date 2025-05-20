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
/**
 * This script synchronizes all array keys across language files.
 * It ensures that all language files have the same keys, setting missing keys to null.
 * It also reads file-specific JSON key files and adds missing keys to the appropriate language files.
 */

// Base directory for language files
$baseDir = __DIR__;

// Get all language directories
$langDirs = array_filter(glob($baseDir . '/*'), 'is_dir');

// Function to recursively get all PHP files in a directory
function getPhpFiles(string $dir): array
{
    $files = [];
    $items = glob($dir . '/*');

    foreach ($items as $item) {
        if (is_dir($item)) {
            $files = array_merge($files, getPhpFiles($item));
        } elseif (pathinfo($item, PATHINFO_EXTENSION) === 'php') {
            $files[] = $item;
        }
    }

    return $files;
}

// Function to recursively get all keys from an array
function getAllKeys(array $array, string $prefix = ''): array
{
    $keys = [];

    foreach ($array as $key => $value) {
        $currentKey = $prefix ? $prefix . '.' . $key : $key;

        if (is_array($value)) {
            $keys = array_merge($keys, getAllKeys($value, $currentKey));
        } else {
            $keys[] = $currentKey;
        }
    }

    return $keys;
}

// Function to set a nested array value using dot notation
function setNestedValue(array &$array, string $path, $value): void
{
    $keys = explode('.', $path);
    $current = &$array;

    foreach ($keys as $i => $key) {
        if ($i === count($keys) - 1) {
            $current[$key] = $value;
        } else {
            if (! isset($current[$key]) || ! is_array($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }
    }
}

// Function to get file basename without extension
function getFileBasename(string $filePath): string
{
    return pathinfo($filePath, PATHINFO_FILENAME);
}

// Group files by their basename
function groupFilesByBasename(array $files): array
{
    $grouped = [];
    foreach ($files as $file) {
        $basename = getFileBasename($file);
        if (! isset($grouped[$basename])) {
            $grouped[$basename] = [];
        }
        $grouped[$basename][] = $file;
    }
    return $grouped;
}

// Collect all language files
$allFiles = [];
foreach ($langDirs as $langDir) {
    $allFiles = array_merge($allFiles, getPhpFiles($langDir));
}

// Group files by their basename (e.g., 'hf-shield.php' -> 'hf-shield')
$fileGroups = groupFilesByBasename($allFiles);

echo 'Found ' . count($fileGroups) . " translation file groups.\n";

// Process each group of files separately
foreach ($fileGroups as $basename => $files) {
    echo "\nProcessing '{$basename}' translation files...\n";

    // Extract all unique keys from this group of language files
    $allKeys = [];
    $fileContents = [];

    foreach ($files as $file) {
        $content = require $file;
        $fileContents[$file] = $content;

        $keys = getAllKeys($content);
        $allKeys = array_merge($allKeys, $keys);
    }

    // Check if there's a JSON keys file for this basename
    $jsonKeysFile = $baseDir . '/' . $basename . '-keys.json';
    if (file_exists($jsonKeysFile)) {
        echo "Reading keys from {$basename}-keys.json...\n";
        $jsonKeys = json_decode(file_get_contents($jsonKeysFile), true);

        if ($jsonKeys) {
            // Add the JSON keys to our list
            foreach ($jsonKeys as $key => $value) {
                $allKeys[] = $key;
            }
        }
    }

    $allKeys = array_unique($allKeys);
    sort($allKeys);

    echo 'Found ' . count($allKeys) . " unique keys for '{$basename}'.\n";

    // Update each language file in this group to include all keys
    $updated = 0;

    foreach ($files as $file) {
        $content = $fileContents[$file];
        $modified = false;

        foreach ($allKeys as $key) {
            // Check if the key exists in the current file
            $keyExists = false;
            $keyParts = explode('.', $key);
            $currentArray = $content;

            foreach ($keyParts as $i => $part) {
                if ($i === count($keyParts) - 1) {
                    $keyExists = isset($currentArray[$part]);
                } else {
                    if (! isset($currentArray[$part]) || ! is_array($currentArray[$part])) {
                        $keyExists = false;
                        break;
                    }
                    $currentArray = $currentArray[$part];
                }
            }

            // If the key doesn't exist, add it with null value
            if (! $keyExists) {
                setNestedValue($content, $key, null);
                $modified = true;
            }
        }

        // Save the updated content back to the file if modified
        if ($modified) {
            $relativePath = str_replace($baseDir . '/', '', $file);
            echo "Updating file: {$relativePath}\n";

            $head = "<?php\n\ndeclare(strict_types=1);\n/**\n * This file is part of the hf_shield module, a package build for Hyperf framework that is responsible for OAuth2 authentication and access control.\n *\n * @author   Joao Zanon <jot@jot.com.br>\n * @link     https://github.com/JotJunior/hf-shield\n * @license  MIT\n */\n";
            $code = 'return ' . var_export($content, true) . ";\n";

            // Format the output to be more readable
            $code = str_replace(['array (', ')', '  '], ['[', ']', '    '], $code);
            $code = preg_replace("/=> \\[\n\\s+\\]/", '=> []', $code);

            file_put_contents($file, $head . $code);
            ++$updated;
        }
    }

    echo "Updated {$updated} files in the '{$basename}' group.\n";
}

echo "\nSynchronization completed.\n";
