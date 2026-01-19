<?php
// app/core/CoreParamLoader.php

/**
 * Class CoreParamLoader
 *
 * A simple utility class to load default application parameters from
 * the Yii2 Boilerplate core configuration.
 *
 * Purpose:
 * - Provides a standardized way to access core default parameters for all projects.
 * - Ensures that the core configuration is loaded before any project-specific
 *   or user-specific overrides.
 *
 * Usage:
 * ```php
 * use CoreParamLoader;
 *
 * $coreParams = CoreParamLoader::load();
 * ```
 *
 * The returned array usually comes from `app/core/config/params_core.php` and
 * contains default values such as:
 * - Service metadata (`titleService`, `serviceVersion`, `codeApp`)
 * - Default timestamp and timezone settings
 * - Language defaults
 * - JWT configuration
 * - CORS policies
 * - Request settings
 * - Pagination and HTTP verb rules
 * - Mailer configuration
 *
 * This class is intended to be used as the foundation for parameter
 * merging in combination with:
 * - `params.php` → project-level overrides
 * - `params_app.php` → user-defined or custom parameters
 *
 * @package app\core
 * @version 1.0.0
 * @since 2025-11-04
 */
class CoreParamLoader
{
    /**
     * Load default core parameters.
     *
     * @return array The core parameters array.
     *               Returns an empty array if the file does not exist or is invalid.
     */
    public static function load(): array
    {
        $corePath = __DIR__ . '/config/params_core.php';

        if (is_file($corePath)) {
            $params = require $corePath;
            return is_array($params) ? $params : [];
        }

        return [];
    }
}
