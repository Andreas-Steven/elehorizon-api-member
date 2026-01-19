<?php

namespace app\commands;

use yii\console\ExitCode;
use yii\console\controllers\MigrateController as BaseMigrateController;

/**
 * MigrateController extends Yii2's console migration controller
 * to provide enhanced migration management for the application.
 *
 * Features:
 * - Custom migration paths including all subdirectories under @app/migrations
 *   excluding folders containing "backup"
 * - Environment-aware migration execution:
 *   prevents destructive commands in non-development environments
 * - Convenience command `actionFresh` to drop all tables and re-run all migrations
 *
 * Example usage:
 * ```bash
 * # Run all pending migrations
 * php yii migrate
 *
 * # Run all migrations fresh (drop all tables and re-run)
 * php yii migrate/fresh
 *
 * # Run down migration for all tables
 * php yii migrate/down all
 * ```
 *
 * @package app\commands
 * @version 1.0.0
 * @since 2025-11-04
 */
class MigrateController extends BaseMigrateController
{
    /**
     * Initializes the migration paths for this controller.
     *
     * - Default path: @app/migrations
     * - Includes all subdirectories except those containing "backup" and "example"
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        $basePath = \Yii::getAlias('@app/migrations');
        $paths = ['@app/migrations'];

        $excludeFolders = ['backup', 'example'];

        if (is_dir($basePath)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $dir) {
                if ($dir->isDir()) {
                    $folderName = $dir->getFilename();
                    if (!in_array($folderName, $excludeFolders, true)) {
                        $relativePath = str_replace('\\', '/', substr($dir->getPathname(), strlen($basePath)));
                        $paths[] = '@app/migrations' . $relativePath;
                    }
                }
            }
        }

        $this->migrationPath = $paths;
    }

    /**
     * Checks whether migration actions are allowed in the current environment.
     *
     * - Prevents migration execution if 'migrateDb' param is false
     * - Prevents destructive actions (fresh, down) in non-dev environments
     *
     * @param \yii\base\Action $action The action to be executed
     * @return bool|int True if action is allowed, ExitCode::OK to skip
     */
    public function beforeAction($action)
    {
        $params = require(\Yii::getAlias('@app/config/params.php'));

        if ($params['migrateDb'] === false) {
            echo "Skipping the migrate command in non-dev environment.\n";
            return ExitCode::OK;
        }

        if (!YII_ENV_DEV && in_array($action->id, ['fresh', 'down'])) {
            echo "Skipping the migrate/fresh command in non-dev environment.\n";
            return ExitCode::OK;
        }

        return parent::beforeAction($action);
    }

    /**
     * Drops all tables and re-runs all migrations.
     *
     * - Prompts the user for confirmation before execution
     * - Calls `actionDown('all')` then `actionUp()` if confirmed
     *
     * Example usage:
     * ```bash
     * php yii migrate/fresh
     * ```
     *
     * @return int Exit code
     */
    public function actionFresh()
    {
        if ($this->confirm('Are you sure you want to drop all tables and re-run all migrations? This will erase all data.')) {
            $this->actionDown('all');
            return $this->actionUp();
        }

        return ExitCode::OK;
    }
}