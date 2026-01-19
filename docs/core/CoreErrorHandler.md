# CoreErrorHandler Documentation

**Namespace**: `app\core`  
**Version**: 1.0.0  
**Last Updated**: 2025-11-04

## Deskripsi

`CoreErrorHandler` extends Yii's built-in ErrorHandler dan menyediakan standardized JSON responses untuk API exceptions. Handles berbagai tipe exception dan mengembalikan response yang konsisten.

## Fitur Utama

- ✅ JSON formatted error responses
- ✅ Handles HTTP exceptions
- ✅ Handles CoreException dengan custom errors
- ✅ Handles database optimistic lock exceptions
- ✅ Detailed trace information dalam development mode
- ✅ Standardized response fields

## Inheritance

```php
class CoreErrorHandler extends ErrorHandler
```

Extends dari `yii\web\ErrorHandler`

## Metode-Metode

### `renderException($exception): void`

Renders exception ke JSON response. Automatically dipanggil oleh Yii ketika exception terjadi.

**Parameters**:
- `$exception` (Exception) - Exception instance untuk di-render

**Return**: `void` - Mengirim JSON response langsung

**Exception Types yang Ditangani**:

1. **CoreException** - Menggunakan statusCode dan custom errors dari exception
2. **HttpException** - Menggunakan HTTP status code
3. **StaleObjectException** - Returns 409 conflict dengan lock version error
4. **Other Exceptions** - Returns 500 dengan exception message

**Response Structure**:

```json
{
    "code": 422,
    "success": false,
    "message": "Validation failed",
    "errors": {
        "username": ["Username cannot be blank"]
    },
    "trace_for_dev": {
        "exception": "app\\exceptions\\CoreException",
        "trace": "..."
    }
}
```

**Contoh**:

```php
// Trigger an exception in a controller action
throw new CoreException($model, "Validation failed", 422);

// Output JSON response automatically:
// {
//   "code": 422,
//   "success": false,
//   "message": "Validation failed",
//   "errors": {
//       "username": ["Username cannot be blank"]
//   }
// }
```

## Response Format Details

### Success Response (tidak ada exception)

```json
{
    "code": 200,
    "success": true,
    "message": "Operation successful",
    "data": {...}
}
```

### Error Response - CoreException

```json
{
    "code": 422,
    "success": false,
    "message": "Validation failed",
    "errors": {
        "field_name": ["Error message 1", "Error message 2"]
    }
}
```

### Error Response - HttpException

```json
{
    "code": 404,
    "success": false,
    "message": "Not Found",
    "errors": {}
}
```

### Error Response - StaleObjectException (Optimistic Lock)

```json
{
    "code": 409,
    "success": false,
    "message": "Lock version outdated",
    "errors": {}
}
```

### Error Response - Generic Exception (Development)

```json
{
    "code": 500,
    "success": false,
    "message": "Internal Server Error",
    "errors": {},
    "trace_for_dev": {
        "exception": "Exception",
        "trace": "Full stack trace..."
    }
}
```

## Exception Handling Flow

```
Exception Occurs
    ↓
CoreErrorHandler::renderException()
    ↓
Determine Exception Type
    ├─ CoreException → Use statusCode & errors
    ├─ HttpException → Use HTTP status code
    ├─ StaleObjectException → Return 409
    └─ Other → Return 500
    ↓
Build Response Array
    ├─ code
    ├─ success
    ├─ message
    ├─ errors
    └─ trace_for_dev (if YII_TRACE enabled)
    ↓
Send JSON Response
```

## Konfigurasi

### Web Configuration

```php
// config/web.php
return [
    'components' => [
        'errorHandler' => [
            'class' => 'app\core\CoreErrorHandler',
        ],
    ],
];
```

### Environment Variables

- `YII_TRACE` - Enable/disable trace information dalam response
- `YII_ENV_DEV` - Development environment flag

## Contoh Penggunaan

### Handling CoreException

```php
use app\exceptions\CoreException;

public function actionCreate()
{
    $model = new User();
    $model->load(Yii::$app->request->post());
    
    if (!$model->save()) {
        // Automatically handled by CoreErrorHandler
        throw new CoreException($model, 'Validation failed', 422);
    }
    
    return ['success' => true];
}
```

### Handling HttpException

```php
use yii\web\NotFoundHttpException;

public function actionView($id)
{
    $model = User::findOne($id);
    
    if ($model === null) {
        // Automatically handled by CoreErrorHandler
        throw new NotFoundHttpException('User not found');
    }
    
    return $model;
}
```

### Handling StaleObjectException

```php
use yii\db\StaleObjectException;

public function actionUpdate($id)
{
    $model = User::findOne($id);
    $model->name = Yii::$app->request->post('name');
    
    try {
        $model->save();
    } catch (StaleObjectException $e) {
        // Automatically handled by CoreErrorHandler
        // Returns 409 Conflict
        throw $e;
    }
    
    return $model;
}
```

### Custom Exception Handling

```php
public function actionComplexOperation()
{
    try {
        // Complex operation
        $result = $this->performComplexOperation();
    } catch (Exception $e) {
        // Automatically handled by CoreErrorHandler
        throw new CoreException(
            null,
            'Operation failed: ' . $e->getMessage(),
            500
        );
    }
    
    return ['success' => true];
}
```

## Development Mode Features

Ketika `YII_TRACE` enabled (development mode):

1. **Full Stack Trace** - Included dalam response
2. **Exception Class** - Menunjukkan exception class yang sebenarnya
3. **Detailed Error Info** - Membantu debugging

**Development Response**:
```json
{
    "code": 500,
    "success": false,
    "message": "Database connection failed",
    "errors": {},
    "trace_for_dev": {
        "exception": "yii\\db\\Exception",
        "trace": "#0 /path/to/file.php(123): ..."
    }
}
```

## Production Mode Features

Ketika `YII_TRACE` disabled (production mode):

1. **No Stack Trace** - Trace information tidak ditampilkan
2. **Generic Messages** - Error messages lebih generic untuk security
3. **Minimal Info** - Hanya informasi yang diperlukan

**Production Response**:
```json
{
    "code": 500,
    "success": false,
    "message": "Internal Server Error",
    "errors": {}
}
```

## HTTP Status Codes

| Code | Meaning | Exception |
|------|---------|-----------|
| 200 | OK | Success |
| 400 | Bad Request | Validation errors |
| 401 | Unauthorized | Authentication failed |
| 403 | Forbidden | Authorization failed |
| 404 | Not Found | Resource not found |
| 409 | Conflict | Optimistic lock conflict |
| 422 | Unprocessable Entity | Validation failed |
| 500 | Internal Server Error | Server error |

## Catatan Penting

- CoreErrorHandler automatically menangani semua exceptions
- Response format selalu JSON
- Trace information hanya ditampilkan dalam development mode
- CoreException errors tidak menampilkan trace untuk security
- StaleObjectException dihandle khusus untuk optimistic locking
- Semua response memiliki standardized structure
