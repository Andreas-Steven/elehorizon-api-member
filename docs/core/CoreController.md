# CoreController Documentation

**Namespace**: `app\core`  
**Version**: 1.0.0  
**Last Updated**: 2025-05-05

## Deskripsi

`CoreController` adalah base controller untuk RESTful API endpoints. Menyediakan common functionality untuk:
- API response handling
- CORS configuration
- Content negotiation
- CSRF validation
- Request method filtering
- Model finding utilities
- Standardized response formatting

## Fitur Utama

- ✅ Automatic CORS configuration
- ✅ JSON response formatting
- ✅ Standardized error handling
- ✅ CSRF validation configuration
- ✅ Request method filtering
- ✅ Model finding with optimistic locking support
- ✅ Pagination support
- ✅ Custom data response formatting

## Inheritance

```php
class CoreController extends Controller
```

Extends dari `yii\rest\Controller`

## Properties

### `enableCsrfValidation`

```php
public $enableCsrfValidation;
```

CSRF validation status, configurable via params.

## Metode-Metode

### `behaviors(): array`

Mengkonfigurasi controller behaviors termasuk CORS dan content negotiation.

**Return**: `array` - Array of behaviors

**Contoh**:
```php
public function behaviors()
{
    return parent::behaviors();  // Includes CORS, content negotiation, etc.
}
```

**Behaviors yang dikonfigurasi**:
- `corsFilter` - CORS configuration
- `contentNegotiator` - JSON content negotiation
- `authenticator` - JWT bearer authentication
- `verbs` - HTTP verb filtering

---

### `beforeAction($action): bool`

Handles exceptions thrown during action execution sebelum action dijalankan.

**Parameters**:
- `$action` (Action) - Action yang akan dijalankan

**Return**: `bool` - True jika action dapat dilanjutkan

**Throws**: `CoreException` jika ada error

**Contoh**:
```php
public function beforeAction($action)
{
    try {
        return parent::beforeAction($action);
    } catch (CoreException $e) {
        // Handle exception
    }
}
```

---

### `actionIndex(): array`

Default action untuk controller. Mengembalikan basic success response dengan service information.

**Return**: `array` - API response dengan service info

**Response Format**:
```json
{
    "code": 200,
    "success": true,
    "message": "Service Name Version",
    "data": {
        "language": "en",
        "version": "1.0.0",
        "environment": "development"  // Only in dev mode
    }
}
```

**Contoh**:
```php
public function actionIndex()
{
    return CoreController::actionIndex();
}
```

---

### `coreActionIndex(): array`

Alias untuk `actionIndex()`. Menyediakan service information melalui method name yang berbeda.

**Return**: `array` - API response dengan service info

**Contoh**:
```php
public function actionIndex()
{
    return CoreController::coreActionIndex();
}
```

---

### `actionError(): array`

Error action untuk controller. Mengembalikan error response dengan exception message.

**Return**: `array` - API response dengan error details

**Response Format**:
```json
{
    "status": 404,
    "success": false,
    "message": "Not Found",
    "errors": []
}
```

**Contoh**:
```php
// In config/web.php
'errorHandler' => [
    'errorAction' => 'site/error',
],
```

---

### `errorHandler($exception): Response`

Handles exceptions dan mengembalikan JSON response.

**Parameters**:
- `$exception` (Exception) - Exception yang terjadi

**Return**: `Response` - JSON response dengan error details

**Contoh**:
```php
try {
    // Some operation
} catch (Exception $e) {
    return $this->errorHandler($e);
}
```

---

### `coreFindModelOne($model, ?array $paramsID, ?array $otherParams = []): ?object`

Mencari model instance berdasarkan ID atau parameter lain. Automatically handles optimistic locking.

**Parameters**:
- `$model` (string) - Fully qualified model class name
- `$paramsID` (array|null) - ID parameters, typically `['id' => value]`
- `$otherParams` (array|null) - Additional query conditions

**Return**: `object|null` - Model instance atau null jika tidak ditemukan

**Contoh**:
```php
// Find by ID
$user = $this->coreFindModelOne(User::class, ['id' => 123]);

// Find with additional conditions
$activeUser = $this->coreFindModelOne(
    User::class,
    ['id' => 123],
    ['status' => Constants::STATUS_ACTIVE]
);

// Find by other parameters only
$adminUser = $this->coreFindModelOne(
    User::class,
    null,
    ['role' => 'admin', 'is_active' => true]
);

if ($user === null) {
    throw new NotFoundHttpException('User not found');
}
```

---

### `coreFindModel($model, ?array $params): ?object`

Mencari model instance dengan query parameters. Supports query chaining.

**Parameters**:
- `$model` (string) - Fully qualified model class name
- `$params` (array|null) - Query parameters

**Return**: `object|null` - Query object atau null

**Contoh**:
```php
$user = $this->coreFindModel(User::class, ['id' => 123])->one();

if ($user === null) {
    throw new NotFoundHttpException('User not found');
}
```

---

### `coreData($dataProvider): array`

Formats data provider untuk API response. Standardizes pagination dan data format.

**Parameters**:
- `$dataProvider` (object) - Data provider instance

**Return**: `array` - API response dengan pagination

**Response Format**:
```json
{
    "code": 200,
    "success": true,
    "message": "Success",
    "pagination": {
        "page": 1,
        "totalCount": 100,
        "total": 10,
        "display": 10
    },
    "data": [...]
}
```

**Contoh**:
```php
public function actionIndex()
{
    $dataProvider = new ActiveDataProvider([
        'query' => User::find(),
        'pagination' => ['pageSize' => 10],
    ]);
    
    return $this->coreData($dataProvider);
}
```

---

### `coreCustomData($model = [], ?string $message = null): array`

Formats custom data untuk API response. Useful untuk non-standard data structures.

**Parameters**:
- `$model` (array) - Model data
- `$message` (string|null) - Custom message

**Return**: `array` - API response

**Response Format**:
```json
{
    "code": 200,
    "success": true,
    "message": "Custom message",
    "data": {...}
}
```

**Contoh**:
```php
$stats = [
    'total_users' => User::find()->count(),
    'active_users' => User::find()->active()->count()
];
return $this->coreCustomData($stats, 'Statistics retrieved');
```

---

### `coreSuccess($model, ?string $message = null, ?array $customData = null): array`

Formats success response dengan model data.

**Parameters**:
- `$model` (array) - Model data
- `$message` (string|null) - Custom message
- `$customData` (array|null) - Additional data

**Return**: `array` - API response

**Response Format**:
```json
{
    "code": 200,
    "success": true,
    "message": "Success message",
    "data": {...}
}
```

**Contoh**:
```php
$user = User::findOne(1);
return $this->coreSuccess(
    $user,
    Yii::t('app', 'User updated successfully')
);
```

## Konfigurasi

CoreController menggunakan parameter dari `Yii::$app->params`:

```php
'cors' => [
    'origins' => ['*'],
    'requestHeaders' => ['*'],
    'requestOrigin' => ['*'],
    'allowCredentials' => true,
    'requestMethods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowHeaders' => ['*'],
],
'request' => [
    'enableCsrfValidation' => false,
],
'jwt' => [
    'except' => ['login', 'register'],
],
'verbsAction' => [
    'get' => ['GET'],
    'post' => ['POST'],
    'put' => ['PUT'],
    'delete' => ['DELETE'],
],
```

## Contoh Implementasi

### Basic Controller

```php
namespace app\controllers;

use app\core\CoreController;
use app\models\User;
use yii\data\ActiveDataProvider;

class UserController extends CoreController
{
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => User::find(),
            'pagination' => ['pageSize' => 10],
        ]);
        
        return $this->coreData($dataProvider);
    }
    
    public function actionView($id)
    {
        $user = $this->coreFindModelOne(User::class, ['id' => $id]);
        
        if ($user === null) {
            throw new NotFoundHttpException('User not found');
        }
        
        return $this->coreSuccess($user);
    }
    
    public function actionCreate()
    {
        $model = new User();
        $model->load(Yii::$app->request->post());
        
        if ($model->save()) {
            return $this->coreSuccess($model, 'User created successfully');
        }
        
        throw new CoreException($model, 'Validation failed', 422);
    }
}
```

## Catatan Penting

- Semua responses mengikuti standardized format dengan `code`, `success`, `message`, dan `data`
- CORS configuration dapat disesuaikan di params
- Optimistic locking field (`lock_version`) automatically dihapus dari response
- JWT authentication dapat dikonfigurasi untuk exclude certain actions
