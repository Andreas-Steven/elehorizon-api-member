<?php

/**
 * Retrieves environment variables grouped by prefix.
 * 
 * Usage:
 * ```php
 * $config = env_group('app');
 * // Returns: ['debug' => true, 'timezone' => 'UTC']
 * ```
 * 
 * @param string $prefix Environment variable prefix
 * @return array Grouped environment variables
 */
function env_group(string $prefix): array
{
    $result = [];
    foreach ($_ENV as $key => $value) {
        if (stripos($key, "$prefix.") === 0) {
            $path = explode('.', strtolower($key));
            $ref =& $result;
            foreach (array_slice($path, 1) as $p) {
                $ref[$p] ??= [];
                $ref =& $ref[$p];
            }
            $ref = parse_env_value($value);
        }
    }
    return $result;
}

/**
 * Retrieves environment variables grouped by prefix.
 * 
 * Usage:
 * ```php
 * $config = env_group('app');
 * // Returns: ['debug' => true, 'timezone' => 'UTC']
 * ```
 * 
 * @param string $prefix Environment variable prefix
 * @return array Grouped environment variables
 */
function env_value(string $key, $default = null)
{
    $key = strtolower($key);
    foreach ($_ENV as $k => $v) {
        if (strtolower($k) === $key) {
            return parse_env_value($v);
        }
    }
    $parts = explode('.', $key);
    $ref = env_group($parts[0]);
    foreach (array_slice($parts, 1) as $p) {
        if (!isset($ref[$p])) return $default;
        $ref = $ref[$p];
    }
    return parse_env_value($ref ?? $default);
}

/**
 * Parses environment variable value.
 * 
 * Usage:
 * ```php
 * $value = parse_env_value('true');
 * // Returns: true
 * ```
 * 
 * @param string $value Environment variable value
 * @return mixed Parsed value
 */
function parse_env_value($value)
{
    if (!is_string($value)) return $value;
    return match (strtolower($value)) {
        'true', '(true)' => true,
        'false', '(false)' => false,
        'null', '(null)' => null,
        default => is_numeric($value) ? $value + 0 : $value,
    };
}
