<?php

namespace app\core;

use Yii;
use yii\i18n\PhpMessageSource;

/**
 * Class CoreMessageSource
 *
 * CoreMessageSource is a custom Yii2 message source that combines
 * translations from both the core system and the application.
 *
 * Features:
 * - Loads messages from `core.php` and `app.php` in the translation folder
 * - Application messages override core messages if keys collide
 * - Provides fallback to the default source language if keys are missing
 * - Standardizes missing translation messages
 *
 * Directory structure example:
 * ```
 * app/
 * ├── translation/
 * │   ├── en/
 * │   │   ├── app.php
 * │   │   └── core.php
 * │   └── id/
 * │       ├── app.php
 * │       └── core.php
 * ```
 *
 * @package app\core
 * @version 1.0.0
 * @since 2025-11-04
 */
class CoreMessageSource extends PhpMessageSource
{
    /**
     * Loads messages for a given category and language.
     * Combines core and app messages, with app messages taking precedence.
     * Falls back to default language if keys are missing.
     *
     * @param string $category The message category (e.g., 'app', 'core')
     * @param string $language The target language (e.g., 'en', 'id')
     * @return array Combined translation messages
     *
     * @example
     * ```php
     * $messages = Yii::$app->i18n->translations['app']->loadMessages('app', 'id');
     * // $messages contains merged core and app translations
     * ```
     */
    protected function loadMessages($category, $language)
    {
        // Load application messages
        $appMessages = parent::loadMessages($category, $language);

        // Load core messages
        $coreFile = Yii::getAlias("@app/translation/{$language}/core.php");
        $coreMessages = file_exists($coreFile) ? require $coreFile : [];

        // Merge (app messages override core)
        $messages = array_replace_recursive($coreMessages, $appMessages);

        // Fallback to default language if some keys missing
        $defaultLang = Yii::$app->sourceLanguage ?? 'en';
        if ($language !== $defaultLang) {
            $fallbackCore = Yii::getAlias("@app/translation/{$defaultLang}/core.php");
            $fallbackApp  = Yii::getAlias("@app/translation/{$defaultLang}/app.php");

            $fallbackCoreMessages = file_exists($fallbackCore) ? require $fallbackCore : [];
            $fallbackAppMessages  = file_exists($fallbackApp) ? require $fallbackApp : [];

            $fallbackMessages = array_replace_recursive($fallbackCoreMessages, $fallbackAppMessages);

            foreach ($fallbackMessages as $key => $value) {
                if (!isset($messages[$key])) {
                    $messages[$key] = $value;
                }
            }
        }

        return $messages;
    }

    /**
     * Translates a message to the specified language.
     * Returns the translated string if found, otherwise returns
     * a standardized missing-message string.
     *
     * @param string $category The message category
     * @param string $message The message key to translate
     * @param string $language The target language (e.g., 'en', 'id')
     * @return string Translated message or standardized missing string
     *
     * @example
     * ```php
     * $message = Yii::$app->i18n->translations['app']->translate('app', 'welcome', 'id');
     * echo $message; // Output: "Selamat datang"
     *
     * $missing = Yii::$app->i18n->translations['app']->translate('app', 'nonexistent', 'id');
     * echo $missing; // Output: "@missing: app.nonexistent for language id @"
     * ```
     */
    public function translate($category, $message, $language)
    {
        $messages = $this->loadMessages($category, $language);

        if (isset($messages[$message])) {
            return $messages[$message];
        }

        // Return standardized missing translation
        return "@missing: {$category}.{$message} for language {$language} @";
    }
}