# CoreMySQL Documentation

**Namespace**: `app\core`  
**Version**: 1.0.0  
**Last Updated**: 2025-11-04

## Deskripsi

`CoreMySQL` adalah base ActiveQuery class untuk MySQL database queries. Extends Yii's ActiveQuery dengan common query methods termasuk:
- Status filtering
- Sorting methods
- ID dan name based filtering
- Created/updated date range filters
- User-based filtering
- JSON field filtering
- Detail info change log filters

## Fitur Utama

- ✅ Status filtering (active, inactive, draft, deleted, etc.)
- ✅ Sorting by name dan sort order
- ✅ ID dan name based filtering
- ✅ Date range filtering
- ✅ User-based filtering
- ✅ JSON field filtering
- ✅ Chainable query methods

## Inheritance

```php
class CoreMySQL extends ActiveQuery implements ActiveQueryInterface
```

Extends dari `yii\db\ActiveQuery`

## Properties

```php
public $fieldStatus;      // Field name untuk status filtering
public $fieldSortOrder;   // Field name untuk sorting
public $fieldId;          // Field name untuk ID
public $fieldName;        // Field name untuk name
```

## Constructor

```php
public function __construct($modelClass, $config = [])
{
    parent::__construct($modelClass, $config);
    $this->fieldStatus = 'status';
    $this->fieldSortOrder = 'sort_order';
    $this->fieldId = 'id';
    $this->fieldName = 'name';
}
```

## Record Retrieval Methods

### `all($db = null): array`

Retrieve semua records matching the query.

**Parameters**:
- `$db` (Connection|null) - Database connection

**Return**: `array` - List of models

**Contoh**:
```php
$users = User::find()->active()->all();
```

---

### `one($db = null): object|null`

Retrieve single record matching the query.

**Parameters**:
- `$db` (Connection|null) - Database connection

**Return**: `object|null` - Single model atau null

**Contoh**:
```php
$user = User::find()->findById(1)->one();
```

## Status Filter Methods

### `byStatus($status): CoreMySQL`

Filter records by specific status.

**Parameters**:
- `$status` (int) - Status value

**Return**: `CoreMySQL` - Query object untuk chaining

**Contoh**:
```php
$records = User::find()->byStatus(Constants::STATUS_ACTIVE)->all();
```

---

### `inactive(): CoreMySQL`

Filter records dengan inactive status.

**Return**: `CoreMySQL`

**Contoh**:
```php
$records = User::find()->inactive()->all();
```

---

### `active(): CoreMySQL`

Filter records dengan active status.

**Return**: `CoreMySQL`

**Contoh**:
```php
$records = User::find()->active()->all();
```

---

### `draft(): CoreMySQL`

Filter records dengan draft status.

**Return**: `CoreMySQL`

**Contoh**:
```php
$records = User::find()->draft()->all();
```

---

### `completed(): CoreMySQL`

Filter records dengan completed status.

**Return**: `CoreMySQL`

**Contoh**:
```php
$records = User::find()->completed()->all();
```

---

### `deleted(): CoreMySQL`

Filter records dengan deleted status.

**Return**: `CoreMySQL`

**Contoh**:
```php
$records = User::find()->deleted()->all();
```

---

### `maintenance(): CoreMySQL`

Filter records dengan maintenance status.

**Return**: `CoreMySQL`

**Contoh**:
```php
$records = User::find()->maintenance()->all();
```

---

### `approved(): CoreMySQL`

Filter records dengan approved status.

**Return**: `CoreMySQL`

**Contoh**:
```php
$records = User::find()->approved()->all();
```

---

### `rejected(): CoreMySQL`

Filter records dengan rejected status.

**Return**: `CoreMySQL`

**Contoh**:
```php
$records = User::find()->rejected()->all();
```

## Sorting Methods

### `orderBySortOrder($direction = SORT_ASC): CoreMySQL`

Order records by sort_order field.

**Parameters**:
- `$direction` (int) - SORT_ASC atau SORT_DESC

**Return**: `CoreMySQL`

**Contoh**:
```php
$records = User::find()->orderBySortOrder(SORT_DESC)->all();
```

---

### `orderByName($direction = SORT_ASC): CoreMySQL`

Order records by name field.

**Parameters**:
- `$direction` (int) - SORT_ASC atau SORT_DESC

**Return**: `CoreMySQL`

**Contoh**:
```php
$records = User::find()->orderByName(SORT_ASC)->all();
```

## ID dan Name Filter Methods

### `findById($id): CoreMySQL`

Filter records by single ID.

**Parameters**:
- `$id` (int) - Record ID

**Return**: `CoreMySQL`

**Contoh**:
```php
$record = User::find()->findById(5)->one();
```

---

### `findByIds($ids): CoreMySQL`

Filter records by multiple IDs.

**Parameters**:
- `$ids` (array) - Array of IDs

**Return**: `CoreMySQL`

**Contoh**:
```php
$records = User::find()->findByIds([1, 2, 3])->all();
```

---

### `findByName($name): CoreMySQL`

Filter records by exact name.

**Parameters**:
- `$name` (string) - Name value

**Return**: `CoreMySQL`

**Contoh**:
```php
$record = User::find()->findByName('John Doe')->one();
```

---

### `findByNameLike($name): CoreMySQL`

Filter records by name dengan LIKE operator.

**Parameters**:
- `$name` (string) - Name pattern

**Return**: `CoreMySQL`

**Contoh**:
```php
$records = User::find()->findByNameLike('John')->all();
```

## Contoh Penggunaan Lengkap

### Basic Query

```php
use app\models\User;
use app\core\CoreConstants;

// Get all active users
$users = User::find()->active()->all();

// Get active users ordered by name
$users = User::find()
    ->active()
    ->orderByName(SORT_ASC)
    ->all();

// Get specific user by ID
$user = User::find()->findById(1)->one();
```

### Complex Query

```php
// Get active users with specific IDs, ordered by name
$users = User::find()
    ->active()
    ->findByIds([1, 2, 3, 4, 5])
    ->orderByName(SORT_ASC)
    ->all();

// Get users with name like 'John'
$users = User::find()
    ->active()
    ->findByNameLike('John')
    ->all();
```

### With Pagination

```php
use yii\data\ActiveDataProvider;

$dataProvider = new ActiveDataProvider([
    'query' => User::find()
        ->active()
        ->orderByName(SORT_ASC),
    'pagination' => [
        'pageSize' => 10,
    ],
]);

$users = $dataProvider->getModels();
```

### With Additional Conditions

```php
// Combine dengan andWhere untuk custom conditions
$users = User::find()
    ->active()
    ->andWhere(['>=', 'created_at', '2025-01-01'])
    ->orderByName(SORT_ASC)
    ->all();
```

### Status Transitions

```php
// Get draft records
$drafts = Order::find()->draft()->all();

// Get completed records
$completed = Order::find()->completed()->all();

// Get all non-deleted records
$active = Order::find()
    ->andWhere(['<>', 'status', CoreConstants::STATUS_DELETED])
    ->all();
```

## Custom Field Names

Jika model menggunakan field names yang berbeda:

```php
class Product extends ActiveRecord
{
    public static function find()
    {
        $query = new CoreMySQL(static::class);
        $query->fieldStatus = 'product_status';
        $query->fieldName = 'product_name';
        $query->fieldId = 'product_id';
        return $query;
    }
}

// Usage
$products = Product::find()
    ->active()
    ->orderByName(SORT_ASC)
    ->all();
```

## Best Practices

### 1. Chain Methods untuk Readability

```php
// Good
$users = User::find()
    ->active()
    ->orderByName(SORT_ASC)
    ->all();

// Less readable
$query = User::find();
$query->active();
$query->orderByName(SORT_ASC);
$users = $query->all();
```

### 2. Use Pagination untuk Large Datasets

```php
$dataProvider = new ActiveDataProvider([
    'query' => User::find()->active(),
    'pagination' => ['pageSize' => 20],
]);
```

### 3. Combine dengan Custom Conditions

```php
$users = User::find()
    ->active()
    ->andWhere(['department' => 'IT'])
    ->orderByName(SORT_ASC)
    ->all();
```

### 4. Use findById untuk Single Records

```php
$user = User::find()->findById($id)->one();

if ($user === null) {
    throw new NotFoundHttpException('User not found');
}
```

## Catatan Penting

- Semua methods adalah chainable (return CoreMySQL)
- Status constants defined dalam CoreConstants
- Default field names: status, sort_order, id, name
- Custom field names dapat dikonfigurasi
- Methods menggunakan andWhere untuk combining conditions
- Sorting methods menggunakan SORT_ASC/SORT_DESC constants
- findById dan findByIds menggunakan 'in' operator untuk multiple IDs
