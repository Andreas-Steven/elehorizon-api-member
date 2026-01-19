# Environment Documentation

**Namespace**: `app\core` (Global Functions)  
**Version**: 1.0.0  
**Last Updated**: 2025-11-04

## Deskripsi

Environment module menyediakan helper functions untuk retrieve dan parse environment variables. Memudahkan access ke environment configuration dengan support untuk grouped variables dan type parsing.

## Fitur Utama

- ✅ Retrieve environment variables by key
- ✅ Group environment variables by prefix
- ✅ Automatic type parsing (boolean, null, numeric)
- ✅ Nested array support
- ✅ Default value fallback
- ✅ Case-insensitive key matching

## Fungsi-Fungsi

### `env_group(string $prefix): array`

Retrieves environment variables grouped by prefix.

**Parameters**:
- `$prefix` (string) - Environment variable prefix

**Return**: `array` - Grouped environment variables

**Contoh**:
```php
// Environment variables:
// APP.DEBUG=true
// APP.TIMEZONE=UTC
// APP.LANGUAGE=en

$config = env_group('app');
// Returns:
// [
//     'debug' => true,
//     'timezone' => 'UTC',
//     'language' => 'en'
// ]
```

---

### `env_value(string $key, $default = null): mixed`

Retrieves environment variable value dengan automatic type parsing.

**Parameters**:
- `$key` (string) - Environment variable key
- `$default` (mixed) - Default value jika key tidak ditemukan

**Return**: `mixed` - Parsed environment value atau default

**Contoh**:
```php
// Environment variables:
// DEBUG=true
// TIMEOUT=30
// DATABASE_HOST=localhost

$debug = env_value('DEBUG');           // Returns: true (boolean)
$timeout = env_value('TIMEOUT');       // Returns: 30 (integer)
$host = env_value('DATABASE_HOST');    // Returns: 'localhost' (string)
$missing = env_value('MISSING', 'default');  // Returns: 'default'
```

---

### `parse_env_value($value): mixed`

Parses environment variable value ke appropriate type.

**Parameters**:
- `$value` (string) - Environment variable value

**Return**: `mixed` - Parsed value

**Type Conversions**:

| Input | Output | Type |
|-------|--------|------|
| `'true'` | `true` | boolean |
| `'(true)'` | `true` | boolean |
| `'false'` | `false` | boolean |
| `'(false)'` | `false` | boolean |
| `'null'` | `null` | null |
| `'(null)'` | `null` | null |
| `'123'` | `123` | integer |
| `'45.67'` | `45.67` | float |
| `'hello'` | `'hello'` | string |

**Contoh**:
```php
$value = parse_env_value('true');      // Returns: true
$value = parse_env_value('false');     // Returns: false
$value = parse_env_value('null');      // Returns: null
$value = parse_env_value('123');       // Returns: 123
$value = parse_env_value('hello');     // Returns: 'hello'
```

## Environment Variable Format

### Flat Variables

```bash
# .env file
DEBUG=true
TIMEOUT=30
DATABASE_HOST=localhost
```

**Access**:
```php
$debug = env_value('DEBUG');
$timeout = env_value('TIMEOUT');
$host = env_value('DATABASE_HOST');
```

---

### Grouped Variables (Dot Notation)

```bash
# .env file
APP.DEBUG=true
APP.TIMEZONE=UTC
APP.LANGUAGE=en

DB.HOST=localhost
DB.PORT=5432
DB.NAME=myapp
```

**Access**:
```php
// Get all APP variables
$appConfig = env_group('app');
// Returns: ['debug' => true, 'timezone' => 'UTC', 'language' => 'en']

// Get specific nested variable
$debug = env_value('app.debug');       // Returns: true
$host = env_value('db.host');          // Returns: 'localhost'
$port = env_value('db.port');          // Returns: 5432
```

---

### Nested Variables (Multiple Dots)

```bash
# .env file
APP.DATABASE.HOST=localhost
APP.DATABASE.PORT=5432
APP.DATABASE.NAME=myapp
APP.CACHE.DRIVER=redis
APP.CACHE.TTL=3600
```

**Access**:
```php
// Get all APP variables (nested)
$config = env_group('app');
// Returns:
// [
//     'database' => [
//         'host' => 'localhost',
//         'port' => 5432,
//         'name' => 'myapp'
//     ],
//     'cache' => [
//         'driver' => 'redis',
//         'ttl' => 3600
//     ]
// ]

// Get specific nested variable
$host = env_value('app.database.host');     // Returns: 'localhost'
$driver = env_value('app.cache.driver');    // Returns: 'redis'
```

## Konfigurasi

### .env File Example

```bash
# Application
APP.DEBUG=true
APP.TIMEZONE=UTC
APP.LANGUAGE=en

# Database
DB.DRIVER=mysql
DB.HOST=localhost
DB.PORT=3306
DB.NAME=myapp
DB.USER=root
DB.PASSWORD=secret

# Cache
CACHE.DRIVER=redis
CACHE.HOST=localhost
CACHE.PORT=6379
CACHE.TTL=3600

# Mail
MAIL.DRIVER=smtp
MAIL.HOST=smtp.mailtrap.io
MAIL.PORT=465
MAIL.USERNAME=user@example.com
MAIL.PASSWORD=password

# API
API.KEY=your-api-key
API.SECRET=your-api-secret
API.TIMEOUT=30
```

### Web Configuration

```php
// config/web.php
use function app\core\env_group;
use function app\core\env_value;

$appConfig = env_group('app');
$dbConfig = env_group('db');
$cacheConfig = env_group('cache');

return [
    'id' => 'app-web',
    'basePath' => dirname(__DIR__),
    
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => sprintf(
                '%s:host=%s;port=%s;dbname=%s',
                $dbConfig['driver'] ?? 'mysql',
                $dbConfig['host'] ?? 'localhost',
                $dbConfig['port'] ?? 3306,
                $dbConfig['name'] ?? 'myapp'
            ),
            'username' => $dbConfig['user'] ?? 'root',
            'password' => $dbConfig['password'] ?? '',
            'charset' => 'utf8',
        ],
        'cache' => [
            'class' => 'yii\caching\RedisCache',
            'redis' => [
                'hostname' => $cacheConfig['host'] ?? 'localhost',
                'port' => $cacheConfig['port'] ?? 6379,
            ],
            'defaultDuration' => $cacheConfig['ttl'] ?? 3600,
        ],
    ],
    
    'params' => [
        'debug' => $appConfig['debug'] ?? false,
        'timezone' => $appConfig['timezone'] ?? 'UTC',
        'language' => $appConfig['language'] ?? 'en',
    ],
];
```

## Contoh Penggunaan

### Basic Usage

```php
// Get single value
$debug = env_value('DEBUG');
$timeout = env_value('TIMEOUT', 30);  // With default

// Get grouped values
$dbConfig = env_group('db');
$host = $dbConfig['host'] ?? 'localhost';
$port = $dbConfig['port'] ?? 5432;
```

---

### Database Configuration

```php
$dbConfig = env_group('db');

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s',
    $dbConfig['host'] ?? 'localhost',
    $dbConfig['port'] ?? 3306,
    $dbConfig['name'] ?? 'myapp'
);

$db = new \yii\db\Connection([
    'dsn' => $dsn,
    'username' => $dbConfig['user'] ?? 'root',
    'password' => $dbConfig['password'] ?? '',
]);
```

---

### Cache Configuration

```php
$cacheConfig = env_group('cache');

$cache = [
    'class' => 'yii\caching\RedisCache',
    'redis' => [
        'hostname' => $cacheConfig['host'] ?? 'localhost',
        'port' => $cacheConfig['port'] ?? 6379,
    ],
    'defaultDuration' => $cacheConfig['ttl'] ?? 3600,
];
```

---

### Mail Configuration

```php
$mailConfig = env_group('mail');

$mailer = [
    'class' => 'yii\swiftmailer\Mailer',
    'transport' => [
        'class' => 'Swift_SmtpTransport',
        'host' => $mailConfig['host'] ?? 'smtp.mailtrap.io',
        'port' => $mailConfig['port'] ?? 465,
        'username' => $mailConfig['username'] ?? '',
        'password' => $mailConfig['password'] ?? '',
    ],
];
```

---

### Conditional Configuration

```php
$debug = env_value('DEBUG', false);
$environment = env_value('ENVIRONMENT', 'production');

if ($debug) {
    // Enable debugging features
    define('YII_DEBUG', true);
    define('YII_ENV', 'dev');
} else {
    // Production mode
    define('YII_DEBUG', false);
    define('YII_ENV', 'prod');
}
```

## Best Practices

### 1. Use Default Values

```php
// Good - provides fallback
$timeout = env_value('TIMEOUT', 30);
$host = env_value('DB.HOST', 'localhost');

// Risky - no fallback
$timeout = env_value('TIMEOUT');
```

---

### 2. Group Related Variables

```bash
# Good - organized
APP.DEBUG=true
APP.TIMEZONE=UTC
DB.HOST=localhost
DB.PORT=5432

# Avoid - scattered
DEBUG=true
TIMEZONE=UTC
DATABASE_HOST=localhost
DATABASE_PORT=5432
```

---

### 3. Use Consistent Naming

```bash
# Good - consistent naming
APP.DEBUG=true
APP.TIMEZONE=UTC
APP.LANGUAGE=en

# Avoid - inconsistent
DEBUG=true
TZ=UTC
LANG=en
```

---

### 4. Document Environment Variables

```php
/**
 * Environment Variables Configuration
 * 
 * APP.DEBUG (boolean) - Enable debug mode
 * APP.TIMEZONE (string) - Application timezone
 * APP.LANGUAGE (string) - Default language
 * 
 * DB.DRIVER (string) - Database driver (mysql, pgsql)
 * DB.HOST (string) - Database host
 * DB.PORT (integer) - Database port
 * DB.NAME (string) - Database name
 * DB.USER (string) - Database user
 * DB.PASSWORD (string) - Database password
 */
```

---

### 5. Validate Environment Variables

```php
$dbHost = env_value('DB.HOST');
if (empty($dbHost)) {
    throw new Exception('DB.HOST environment variable is required');
}

$dbPort = env_value('DB.PORT', 3306);
if (!is_numeric($dbPort)) {
    throw new Exception('DB.PORT must be numeric');
}
```

## Type Parsing Examples

```php
// Boolean parsing
env_value('DEBUG=true');       // true
env_value('DEBUG=(true)');     // true
env_value('DEBUG=false');      // false
env_value('DEBUG=(false)');    // false

// Null parsing
env_value('VALUE=null');       // null
env_value('VALUE=(null)');     // null

// Numeric parsing
env_value('PORT=3306');        // 3306 (integer)
env_value('TIMEOUT=30.5');     // 30.5 (float)

// String parsing
env_value('HOST=localhost');   // 'localhost'
env_value('NAME=my app');      // 'my app'
```

## Catatan Penting

- Semua functions adalah global dan dapat digunakan langsung
- Environment variables case-insensitive untuk keys
- Type parsing automatic untuk common types
- Grouped variables support nested arrays
- Default values dapat diberikan ke env_value()
- env_group() returns empty array jika prefix tidak ditemukan
- Dot notation untuk nested variable access
- Environment variables harus di-load sebelum menggunakan functions ini
