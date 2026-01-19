# CoreAPI Documentation

**Namespace**: `app\core`  
**Version**: 1.0.0  
**Last Updated**: 2025-05-05

## Deskripsi

`CoreAPI` menyediakan utility methods untuk operasi umum dalam aplikasi termasuk:
- Timestamp management (UTC dan local)
- User session management
- Request parameter validation
- Error handling
- MongoDB synchronization
- Unique string generation
- Database connection management

## Fitur Utama

- ✅ UTC dan local timestamp generation
- ✅ User session retrieval
- ✅ Superadmin role checking
- ✅ Request parameter validation
- ✅ Standardized error responses
- ✅ MongoDB sync status management
- ✅ Unique string generation
- ✅ Development domain checking
- ✅ Database connection targeting

## Metode-Metode

### `UTCTimestamp(): string`

Mendapatkan current UTC timestamp dalam format aplikasi.

**Return**: `string` - UTC timestamp dalam format yang dikonfigurasi

**Contoh**:
```php
$utcTime = Yii::$app->coreAPI::UTCTimestamp();
// Returns: "2025-04-24T10:10:50Z"
```

---

### `timestamp(): string`

Mendapatkan current local timestamp dalam format aplikasi.

**Return**: `string` - Local timestamp dalam format yang dikonfigurasi

**Contoh**:
```php
$localTime = Yii::$app->coreAPI::timestamp();
// Returns: "2025-04-24 17:10:50"
```

---

### `getUsername(): string`

Mendapatkan username dari session user yang sedang login. Jika tidak ada user yang login, akan mengembalikan 'system'.

**Return**: `string` - Username atau 'system'

**Contoh**:
```php
$username = Yii::$app->coreAPI::getUsername();
$model->created_by = $username;
```

---

### `superAdmin(): bool`

Mengecek apakah user saat ini memiliki role superadmin.

**Return**: `bool` - True jika user adalah superadmin

**Contoh**:
```php
if (Yii::$app->coreAPI::superAdmin()) {
    // Allow superadmin operations
} else {
    throw new ForbiddenHttpException('Superadmin access required');
}
```

---

### `unavailableParams($model, ?array $params): void`

Memvalidasi bahwa semua request parameters diizinkan oleh model rules. Jika ada parameter yang tidak diizinkan, akan throw CoreException dengan status 422.

**Parameters**:
- `$model` (object) - Model instance untuk check rules
- `$params` (array|null) - Request parameters untuk divalidasi

**Throws**: `CoreException` dengan status 422 jika validasi gagal

**Contoh**:
```php
$params = Yii::$app->request->post();
Yii::$app->coreAPI::unavailableParams($model, $params);
// Jika ada field yang tidak diizinkan, akan throw CoreException
```

---

### `unauthorizedAccess(?string $message = null): void`

Throw unauthorized access error dengan status 401.

**Parameters**:
- `$message` (string|null) - Custom error message (optional)

**Throws**: `CoreException` dengan status 401

**Contoh**:
```php
if (!Yii::$app->user->can('updatePost')) {
    Yii::$app->coreAPI::unauthorizedAccess('Cannot update post');
}
```

---

### `serverError(?string $message = null): void`

Throw server error dengan status 500.

**Parameters**:
- `$message` (string|null) - Custom error message (optional)

**Throws**: `CoreException` dengan status 500

**Contoh**:
```php
try {
    // Complex operation
} catch (Exception $e) {
    Yii::$app->coreAPI::serverError($e->getMessage());
}
```

---

### `setMongodbSyncFailed($model): void`

Menandai MongoDB model sebagai failed sync dengan mengset sync status ke 1.

**Parameters**:
- `$model` (object) - Model instance untuk dimark sebagai failed sync

**Contoh**:
```php
try {
    $model->save();
} catch (MongoException $e) {
    Yii::$app->coreAPI::setMongodbSyncFailed($model);
    throw $e;
}
```

---

### `generateUniqueString($length = 8): string`

Generate unique string yang menggabungkan timestamp dan random bytes.

**Parameters**:
- `$length` (int) - Desired length dari output string (default: 8)

**Return**: `string` - Unique string dengan panjang yang ditentukan

**Contoh**:
```php
$token = Yii::$app->coreAPI::generateUniqueString(12);
// Returns: "j2kf9x8h5p2q"
```

---

### `coreDevelopmentPurpose($devDomain = []): bool`

Mengecek apakah current domain ada dalam development domain list. Throw CoreException dengan status 403 jika domain tidak dalam list.

**Parameters**:
- `$devDomain` (array) - List development domains (optional)

**Return**: `bool` - True jika domain valid

**Throws**: `CoreException` dengan status 403 jika domain tidak valid

**Contoh**:
```php
if (!Yii::$app->coreAPI::coreDevelopmentPurpose()) {
    throw new CoreException(null, Yii::t('app', 'unauthorizedAccess'), 403);
}
```

---

### `dbConnectionTarget(array &$params): string`

Mendapatkan koneksi database target berdasarkan parameter koneksi.

**Parameters**:
- `$params` (array) - Array parameter yang berisi info koneksi (by reference)

**Return**: `string` - Nama koneksi database target

**Contoh**:
```php
$params = ['connection' => 'second_database'];
$dbTarget = Yii::$app->coreAPI::dbConnectionTarget($params);
// $dbTarget = 'dbBintaro' (sesuai Constants::CONNECTION_LIST)
```

## Konfigurasi

CoreAPI menggunakan parameter dari `Yii::$app->params`:
- `timestamp.UTC` - Format UTC timestamp
- `timestamp.local` - Format local timestamp
- `developmentOnly` - List development domains
- `dbDefault` - Default database connection

## Catatan

- Semua method adalah static, dapat diakses langsung dari class
- Timestamp format dapat dikonfigurasi di params
- User session harus sudah diinisialisasi sebelum memanggil `getUsername()`
