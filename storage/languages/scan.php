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
/*
 * This script scans PHP files for __() function calls and extracts translation keys.
 * Usage: php scan.php /path/to/scan
 */

// Check if directory path is provided
if (! isset($argv[1]) || ! is_dir($argv[1])) {
    echo "Usage: php scan.php /path/to/scan\n";
    exit(1);
}

$scanDir = $argv[1];
$outputDir = __DIR__;

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

// Function to extract translation keys from a PHP file
function extractTranslationKeys(string $filePath): array
{
    $content = file_get_contents($filePath);
    $keys = [];

    // Regular expression to match __() function calls
    // This pattern accounts for both single and double quotes, and for the function having a second parameter
    $pattern = '/\b__\(\s*([\'"])(.*?)\1(?:\s*,\s*.*?)?\s*\)/';

    if (preg_match_all($pattern, $content, $matches)) {
        foreach ($matches[2] as $key) {
            // Validate the key format
            if (validateTranslationKey($key)) {
                $keys[] = $key;
            } else {
                echo "Warning: Invalid translation key format: '{$key}' in {$filePath}\n";
            }
        }
    }

    return $keys;
}

// Function to validate translation key format
function validateTranslationKey(string $key): bool
{
    // Key should have format: file.key or file.section.key
    if (! preg_match('/^[a-zA-Z0-9_-]+\.[a-zA-Z0-9_\.-]+$/', $key)) {
        return false;
    }

    // Extract file name from the key
    $parts = explode('.', $key, 2);
    $file = $parts[0];

    // Check if the translation file exists
    $langDirs = array_filter(glob(__DIR__ . '/*'), 'is_dir');
    $fileExists = false;

    foreach ($langDirs as $langDir) {
        if (file_exists($langDir . '/' . $file . '.php')) {
            $fileExists = true;
            break;
        }
    }

    if (! $fileExists) {
        echo "Warning: Translation file '{$file}.php' does not exist for key: {$key}\n";
    }

    return true;
}

// Function to organize keys by file
function organizeKeysByFile(array $keys): array
{
    $organized = [];

    foreach ($keys as $key) {
        $parts = explode('.', $key, 2);
        if (count($parts) === 2) {
            $file = $parts[0];
            $actualKey = $parts[1];
            if (! isset($organized[$file])) {
                $organized[$file] = [];
            }
            $organized[$file][$actualKey] = null;
        }
    }

    return $organized;
}

echo "Scanning directory: {$scanDir}\n";

// Get all PHP files in the directory and subdirectories
$phpFiles = getPhpFiles($scanDir);
$allKeys = [];

echo 'Found ' . count($phpFiles) . " PHP files to scan.\n";

// Extract translation keys from each file
foreach ($phpFiles as $file) {
    $keys = extractTranslationKeys($file);
    if (! empty($keys)) {
        $relativePath = str_replace($scanDir . '/', '', $file);
        echo 'Found ' . count($keys) . " translation keys in {$relativePath}\n";
        $allKeys = array_merge($allKeys, $keys);
    }
}

// Remove duplicates and sort
$uniqueKeys = array_unique($allKeys);
sort($uniqueKeys);

// Organize keys by file
$organizedKeys = organizeKeysByFile($uniqueKeys);

// Save each file's keys to a separate JSON file
foreach ($organizedKeys as $file => $keys) {
    $outputFile = $outputDir . '/' . $file . '-keys.json';
    $jsonContent = json_encode($keys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($outputFile, $jsonContent);
    echo 'Saved ' . count($keys) . " keys for '{$file}' to {$file}-keys.json\n";
}

echo 'Scan completed. Found ' . count($uniqueKeys) . ' unique translation keys across ' . count($organizedKeys) . " files.\n";
