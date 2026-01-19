<?php
// --- comment out lines for production ---
// defined('YII_DEBUG') or define('YII_DEBUG', true);
// defined('YII_ENV') or define('YII_ENV', 'dev');

$appRoot = __DIR__ . '/..';

// --- Load helper custom untuk ENV ---
require_once $appRoot . '/core/Environment.php';

// --- Composer autoload ---
require $appRoot . '/vendor/autoload.php';

// --- Load .env ---
$dotenv = Dotenv\Dotenv::createImmutable($appRoot);
$dotenv->safeLoad(); // safeLoad agar tidak error jika file .env tidak ada

// --- Define Yii constants dari ENV ---
defined('YII_ENV') or define('YII_ENV', $_ENV['YII_ENV'] ?? 'prod');
defined('YII_DEBUG') or define('YII_DEBUG', filter_var($_ENV['YII_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN));
defined('YII_TRACE') or define('YII_TRACE', filter_var($_ENV['YII_TRACE'] ?? false, FILTER_VALIDATE_BOOLEAN));

// --- Yii bootstrap ---
require $appRoot . '/vendor/yiisoft/yii2/Yii.php';

// --- Load konfigurasi aplikasi ---
$config = require $appRoot . '/config/web.php';

// --- Jalankan aplikasi ---
(new yii\web\Application($config))->run();
