# CoreParamLoader Documentation

**Namespace**: `app\core`  
**Version**: 1.0.0  
**Last Updated**: 2025-11-04

## Deskripsi

`CoreParamLoader` adalah simple utility class untuk load default application parameters dari Yii2 Boilerplate core configuration. Menyediakan standardized way untuk access core default parameters untuk semua projects.

## Fitur Utama

- ✅ Load default core parameters
- ✅ Standardized parameter access
- ✅ Foundation untuk parameter merging
- ✅ Simple dan lightweight

## Metode-Metode

### `load(): array`

Load default core parameters dari `app/core/config/params_core.php`.

**Return**: `array` - Core parameters array, atau empty array jika file tidak ada

**Contoh**:
```php
use app\core\CoreParamLoader;

$coreParams = CoreParamLoader::load();
```

## Default Parameters

CoreParamLoader biasanya mengembalikan array dengan struktur berikut:

```php
[
    // Service metadata
    'titleService' => 'My Application',
    'serviceVersion' => '1.0.0',
    'codeApp' => 'APP',
    
    // Timestamp settings
    'timestamp' => [
        'UTC' => 'Y-m-d\TH:i:s\Z',
        'local' => 'Y-m-d H:i:s',
    ],
    
    // Timezone
    'timezone' => 'UTC',
    
    // Language defaults
    'sourceLanguage' => 'en',
    'language' => 'en',
    
    // JWT configuration
    'jwt' => [
        'secret' => 'your-secret-key',
        'except' => ['login', 'register'],
    ],
    
    // CORS policies
    'cors' => [
        'origins' => ['*'],
        'requestHeaders' => ['*'],
        'allowCredentials' => true,
    ],
    
    // Request settings
    'request' => [
        'enableCsrfValidation' => false,
    ],
    
    // Pagination
    'pagination' => [
        'pageSize' => 10,
    ],
    
    // HTTP verb rules
    'verbsAction' => [
        'get' => ['GET'],
        'post' => ['POST'],
        'put' => ['PUT'],
        'delete' => ['DELETE'],
    ],
    
    // Mailer configuration
    'mailer' => [
        'class' => 'yii\swiftmailer\Mailer',
        'useFileTransport' => true,
    ],
]
```

## Konfigurasi

### Web Configuration

```php
// config/web.php
use app\core\CoreParamLoader;

$coreParams = CoreParamLoader::load();

return [
    'id' => 'app-web',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'params' => array_merge(
        $coreParams,
        require __DIR__ . '/params.php',
        require __DIR__ . '/params_app.php'
    ),
    // ... rest of configuration
];
```

### Console Configuration

```php
// config/console.php
use app\core\CoreParamLoader;

$coreParams = CoreParamLoader::load();

return [
    'id' => 'app-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'params' => array_merge(
        $coreParams,
        require __DIR__ . '/params.php',
        require __DIR__ . '/params_app.php'
    ),
    // ... rest of configuration
];
```

## Parameter Merging Strategy

CoreParamLoader dirancang untuk digunakan dengan parameter merging strategy:

```
Core Parameters (CoreParamLoader)
    ↓
Project Parameters (params.php)
    ↓
User/Custom Parameters (params_app.php)
```

**Contoh**:

```php
// config/web.php
use app\core\CoreParamLoader;

$coreParams = CoreParamLoader::load();
$projectParams = require __DIR__ . '/params.php';
$userParams = require __DIR__ . '/params_app.php';

$params = array_merge($coreParams, $projectParams, $userParams);
```

### Priority Order

1. **Core Parameters** - Default values dari core
2. **Project Parameters** - Project-specific overrides
3. **User Parameters** - User-defined atau custom parameters

Later parameters override earlier ones.

## Contoh Penggunaan

### Basic Usage

```php
use app\core\CoreParamLoader;

$coreParams = CoreParamLoader::load();

// Access specific parameter
$version = $coreParams['serviceVersion'] ?? '1.0.0';
$timezone = $coreParams['timezone'] ?? 'UTC';
```

### In Application Configuration

```php
// config/web.php
use app\core\CoreParamLoader;

$coreParams = CoreParamLoader::load();

return [
    'id' => 'app-web',
    'basePath' => dirname(__DIR__),
    
    'params' => array_merge(
        $coreParams,
        require __DIR__ . '/params.php'
    ),
    
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=myapp',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
        ],
        'mailer' => array_merge(
            $coreParams['mailer'] ?? [],
            [
                'useFileTransport' => YII_ENV_DEV,
            ]
        ),
    ],
];
```

### With Custom Overrides

```php
// config/params.php
return [
    'titleService' => 'My Custom Application',
    'serviceVersion' => '2.0.0',
    'timezone' => 'Asia/Jakarta',
];

// config/web.php
use app\core\CoreParamLoader;

$coreParams = CoreParamLoader::load();
$customParams = require __DIR__ . '/params.php';

$params = array_merge($coreParams, $customParams);

// Result:
// [
//     'titleService' => 'My Custom Application',  // overridden
//     'serviceVersion' => '2.0.0',                 // overridden
//     'timezone' => 'Asia/Jakarta',                // overridden
//     'language' => 'en',                          // from core
//     'jwt' => [...],                              // from core
//     // ... other core params
// ]
```

## File Structure

```
app/
├── core/
│   ├── config/
│   │   └── params_core.php      # Core default parameters
│   └── CoreParamLoader.php      # This class
├── config/
│   ├── web.php                  # Web configuration
│   ├── console.php              # Console configuration
│   ├── params.php               # Project parameters
│   └── params_app.php           # User/custom parameters
```

## Best Practices

### 1. Always Use CoreParamLoader

```php
// Good
use app\core\CoreParamLoader;

$coreParams = CoreParamLoader::load();
$params = array_merge($coreParams, require __DIR__ . '/params.php');

// Avoid
$params = require __DIR__ . '/params.php';  // Missing core defaults
```

### 2. Maintain Parameter Hierarchy

```php
// Good - respects hierarchy
$params = array_merge(
    CoreParamLoader::load(),           // Core defaults
    require __DIR__ . '/params.php',   // Project overrides
    require __DIR__ . '/params_app.php' // User overrides
);

// Avoid - wrong order
$params = array_merge(
    require __DIR__ . '/params.php',
    CoreParamLoader::load()  // Core overwrites project params
);
```

### 3. Handle Missing Files Gracefully

```php
// Good
$coreParams = CoreParamLoader::load();
$projectParams = file_exists(__DIR__ . '/params.php') 
    ? require __DIR__ . '/params.php' 
    : [];

$params = array_merge($coreParams, $projectParams);

// CoreParamLoader already handles missing files
```

### 4. Use Consistent Parameter Keys

```php
// Good - consistent naming
'titleService' => 'My App',
'serviceVersion' => '1.0.0',
'timezone' => 'UTC',

// Avoid - inconsistent
'title' => 'My App',
'version' => '1.0.0',
'tz' => 'UTC',
```

## Catatan Penting

- CoreParamLoader adalah static utility class
- Hanya memiliki satu public method: `load()`
- Returns empty array jika params_core.php tidak ada
- Dirancang untuk digunakan di configuration files
- Menyediakan foundation untuk parameter merging
- Tidak melakukan validasi parameter
- File harus return array
