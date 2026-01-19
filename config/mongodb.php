<?php

return [
    'class' => 'app\components\MongoDbManager',
    'dsn' => env_value('database.mongodb.dsn'),
    'database' => env_value('database.mongodb.dbname'),
];