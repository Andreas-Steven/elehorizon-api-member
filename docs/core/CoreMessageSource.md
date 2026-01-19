# CoreMessageSource Documentation

**Namespace**: `app\core`  
**Version**: 1.0.0  
**Last Updated**: 2025-11-04

## Deskripsi

`CoreMessageSource` adalah custom Yii2 message source yang menggabungkan translations dari core system dan application. Menyediakan fallback mechanism dan message override capabilities.

## Fitur Utama

- ✅ Loads messages dari core.php dan app.php
- ✅ Application messages override core messages
- ✅ Fallback ke default source language
- ✅ Standardized missing translation messages
- ✅ Flexible translation merging

## Inheritance

```php
class CoreMessageSource extends PhpMessageSource
```

Extends dari `yii\i18n\PhpMessageSource`

## Directory Structure

```
app/
├── translation/
│   ├── en/
│   │   ├── app.php      # Application English translations
│   │   └── core.php     # Core English translations
│   └── id/
│       ├── app.php      # Application Indonesian translations
│       └── core.php     # Core Indonesian translations
```

## Metode-Metode

### `loadMessages($category, $language): array`

Loads messages untuk given category dan language. Menggabungkan core dan app messages dengan app messages taking precedence.

**Parameters**:
- `$category` (string) - Message category (e.g., 'app', 'core')
- `$language` (string) - Target language (e.g., 'en', 'id')

**Return**: `array` - Combined translation messages

**Loading Order**:
1. Load application messages
2. Load core messages
3. Merge (app messages override core)
4. Fallback ke default language jika ada missing keys

**Contoh**:
```php
$messages = Yii::$app->i18n->translations['app']->loadMessages('app', 'id');
// $messages contains merged core and app translations
```

---

### `translate($category, $message, $language): string`

Translates message ke specified language. Returns translated string atau standardized missing-message string.

**Parameters**:
- `$category` (string) - Message category
- `$message` (string) - Message key untuk di-translate
- `$language` (string) - Target language (e.g., 'en', 'id')

**Return**: `string` - Translated message atau missing message string

**Contoh**:
```php
$message = Yii::$app->i18n->translations['app']->translate('app', 'welcome', 'id');
echo $message; // Output: "Selamat datang"

$missing = Yii::$app->i18n->translations['app']->translate('app', 'nonexistent', 'id');
echo $missing; // Output: "@missing: app.nonexistent for language id @"
```

## Konfigurasi

### Web Configuration

```php
// config/web.php
return [
    'components' => [
        'i18n' => [
            'translations' => [
                'app*' => [
                    'class' => 'app\core\CoreMessageSource',
                    'basePath' => '@app/translation',
                    'sourceLanguage' => 'en',
                ],
            ],
        ],
    ],
];
```

### Translation Files

**File: translation/en/core.php**
```php
<?php
return [
    'welcome' => 'Welcome',
    'goodbye' => 'Goodbye',
    'success' => 'Operation successful',
    'error' => 'An error occurred',
];
```

**File: translation/en/app.php**
```php
<?php
return [
    'welcome' => 'Welcome to our application',
    'customMessage' => 'This is a custom message',
];
```

**File: translation/id/core.php**
```php
<?php
return [
    'welcome' => 'Selamat datang',
    'goodbye' => 'Sampai jumpa',
    'success' => 'Operasi berhasil',
    'error' => 'Terjadi kesalahan',
];
```

**File: translation/id/app.php**
```php
<?php
return [
    'welcome' => 'Selamat datang di aplikasi kami',
    'customMessage' => 'Ini adalah pesan kustom',
];
```

## Message Merging Logic

### Scenario 1: Message exists in both core dan app

```php
// Core (translation/id/core.php)
'welcome' => 'Selamat datang'

// App (translation/id/app.php)
'welcome' => 'Selamat datang di aplikasi kami'

// Result: App message takes precedence
'welcome' => 'Selamat datang di aplikasi kami'
```

### Scenario 2: Message exists only in core

```php
// Core (translation/id/core.php)
'goodbye' => 'Sampai jumpa'

// App (translation/id/app.php)
// 'goodbye' tidak ada

// Result: Core message digunakan
'goodbye' => 'Sampai jumpa'
```

### Scenario 3: Message exists only in app

```php
// Core (translation/id/core.php)
// 'customMessage' tidak ada

// App (translation/id/app.php)
'customMessage' => 'Pesan kustom'

// Result: App message digunakan
'customMessage' => 'Pesan kustom'
```

### Scenario 4: Message tidak ada di language target, fallback ke default

```php
// Jika language = 'id' tapi message tidak ada
// Akan fallback ke default language (e.g., 'en')

// translation/en/core.php
'welcome' => 'Welcome'

// Result: English message digunakan sebagai fallback
'welcome' => 'Welcome'
```

## Contoh Penggunaan

### Basic Translation

```php
// In controller
echo Yii::t('app', 'welcome');  // Output: "Selamat datang di aplikasi kami"
echo Yii::t('app', 'goodbye');  // Output: "Sampai jumpa"
```

### Translation dengan Parameters

```php
// translation/id/app.php
return [
    'greeting' => 'Selamat datang, {name}!',
];

// In controller
echo Yii::t('app', 'greeting', ['name' => 'John']);
// Output: "Selamat datang, John!"
```

### Conditional Translation

```php
$language = Yii::$app->language;

if ($language === 'id') {
    $message = Yii::t('app', 'welcome');  // Indonesian
} else {
    $message = Yii::t('app', 'welcome');  // English (fallback)
}
```

### Translation dalam Model

```php
class User extends ActiveRecord
{
    public function rules()
    {
        return [
            [['username'], 'required', 'message' => Yii::t('app', 'usernameRequired')],
            [['email'], 'email', 'message' => Yii::t('app', 'invalidEmail')],
        ];
    }
}
```

### Translation dalam View

```php
// In view file
<h1><?= Yii::t('app', 'welcome') ?></h1>
<p><?= Yii::t('app', 'description') ?></p>
```

## Best Practices

### 1. Organize Messages by Category

```php
// translation/id/app.php
return [
    // User messages
    'user.created' => 'User berhasil dibuat',
    'user.updated' => 'User berhasil diupdate',
    'user.deleted' => 'User berhasil dihapus',
    
    // Product messages
    'product.created' => 'Produk berhasil dibuat',
    'product.updated' => 'Produk berhasil diupdate',
];
```

### 2. Use Consistent Key Naming

```php
// Good
'validation.required' => 'Field ini wajib diisi'
'validation.email' => 'Format email tidak valid'

// Bad
'req' => 'Field ini wajib diisi'
'email_invalid' => 'Format email tidak valid'
```

### 3. Keep Core Messages Generic

```php
// translation/id/core.php
return [
    'success' => 'Berhasil',
    'error' => 'Terjadi kesalahan',
    'notFound' => 'Data tidak ditemukan',
];

// translation/id/app.php
return [
    'success' => 'Operasi berhasil dilakukan',
    'userCreated' => 'User berhasil dibuat',
];
```

### 4. Use Plural Forms

```php
// translation/id/app.php
return [
    'items' => '{n, plural, =0{tidak ada item} one{# item} other{# items}}',
];

// Usage
echo Yii::t('app', 'items', ['n' => 0]);  // "tidak ada item"
echo Yii::t('app', 'items', ['n' => 1]);  // "1 item"
echo Yii::t('app', 'items', ['n' => 5]);  // "5 items"
```

## Missing Translation Handling

Ketika translation key tidak ditemukan:

```php
// Result format
"@missing: {category}.{message} for language {language} @"

// Example
"@missing: app.unknownKey for language id @"
```

Ini membantu dalam development untuk mengidentifikasi missing translations.

## Performance Considerations

- Messages di-cache oleh Yii setelah first load
- Merging hanya terjadi saat first load
- Fallback mechanism hanya dijalankan jika key tidak ditemukan

## Catatan Penting

- CoreMessageSource automatically menggabungkan core dan app translations
- App messages selalu override core messages
- Fallback ke default language jika key tidak ada
- Missing translations ditampilkan dalam format yang jelas untuk development
- Translation files harus return array
- Language codes harus konsisten (e.g., 'en', 'id')
