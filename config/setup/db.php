<?php
return [
    'class' => 'yii\db\Connection',
    'dsn' => env_value('database.default.dsn'),
    'username' => env_value('database.default.username'),
    'password' => env_value('database.default.password'),
    'charset' => env_value('database.default.charset'),
    'enableLogging' => YII_ENV_DEV,
    'enableProfiling' => YII_ENV_DEV,

    // Schema cache options (for production environment)
    'enableSchemaCache' => env_value('database.default.enableSchemaCache'),
    'schemaCacheDuration' => env_value('database.default.schemaCacheDuration'),
    'schemaCache' => env_value('database.default.schemaCache'),

    'attributes' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_TIMEOUT => 5,
    ],
    'on beforeOpen' => function($event) {
        $db = $event->sender;
        try {
            $db->createCommand('SELECT 1')->execute();
        } catch (\Exception $e) {
            $db->close();
            $db->open();
        }
    },
];