# CoreMongodb Documentation

**Namespace**: `app\core`  
**Version**: 1.0.0  
**Last Updated**: 2025-11-04

## Deskripsi

`CoreMongodb` menyediakan utility methods untuk interacting dengan MongoDB, termasuk:
- Model class retrieval
- String matching (like dan exact)
- Numeric filtering
- Status handling
- Array element matching

## Fitur Utama

- ✅ Case-insensitive string matching
- ✅ Exact string matching dengan regex
- ✅ Numeric equality filtering
- ✅ Multiple number filtering dengan $in operator
- ✅ Status field filtering
- ✅ Array element matching
- ✅ OR condition support

## Metode-Metode

### `getModelClassName($model): string`

Mendapatkan base class name dari model.

**Parameters**:
- `$model` (object) - Model instance

**Return**: `string` - Base class name tanpa namespace

**Contoh**:
```php
$className = CoreMongodb::getModelClassName($user);
// Returns: 'User'
```

---

### `mdbStringLike(string $field, ?string $value, array &$where, ?string $orWhere = ""): void`

Creates MongoDB regex filter untuk like query.

**Parameters**:
- `$field` (string) - Field name untuk di-search
- `$value` (string|null) - Search value
- `$where` (array) - Reference ke where conditions array
- `$orWhere` (string) - Optional 'or' untuk OR conditions

**Modifies**: `$where` array by reference

**Contoh**:
```php
// Search untuk documents dimana 'name' contains 'john doe'
$where = [];
CoreMongodb::mdbStringLike('name', 'john doe', $where);
// Results in: ['name' => ['$regex' => 'john.*doe', '$options' => 'i']]

// Dengan OR condition
CoreMongodb::mdbStringLike('name', 'john doe', $where, 'or');
// Results in: [['name' => ['$regex' => 'john.*doe', '$options' => 'i']]]
```

---

### `mdbStringEqual(string $field, ?string $value, array &$where, ?string $orWhere = ""): void`

Creates MongoDB exact string match filter.

**Parameters**:
- `$field` (string) - Field name untuk di-match
- `$value` (string|null) - Exact value untuk di-match
- `$where` (array) - Reference ke where conditions array
- `$orWhere` (string) - Optional 'or' untuk OR conditions

**Modifies**: `$where` array by reference

**Contoh**:
```php
// Search untuk documents dimana 'code' equals 'ABC123'
$where = [];
CoreMongodb::mdbStringEqual('code', 'ABC123', $where);
// Results in: ['code' => ['$regex' => '^ABC123$', '$options' => 'i']]
```

---

### `mdbNumberEqual(string $field, ?string $value, array &$where, ?string $orWhere = ""): void`

Creates MongoDB numeric equality filter.

**Parameters**:
- `$field` (string) - Field name untuk di-compare
- `$value` (string|null) - Numeric value as string
- `$where` (array) - Reference ke where conditions array
- `$orWhere` (string) - Optional 'or' untuk OR conditions

**Modifies**: `$where` array by reference

**Contoh**:
```php
// Search untuk documents dimana 'quantity' equals 100
$where = [];
CoreMongodb::mdbNumberEqual('quantity', '100', $where);
// Results in: ['quantity' => 100]
```

---

### `mdbNumberMultiple(string $field, ?string $value, array &$where, ?string $orWhere = ""): void`

Creates MongoDB multiple number filter menggunakan $in operator.

**Parameters**:
- `$field` (string) - Field name untuk di-compare
- `$value` (string|null) - Comma-separated numbers
- `$where` (array) - Reference ke where conditions array
- `$orWhere` (string) - Optional 'or' untuk OR conditions

**Modifies**: `$where` array by reference

**Contoh**:
```php
// Search untuk documents dimana 'status' is 1, 2, atau 3
$where = [];
CoreMongodb::mdbNumberMultiple('status', '1,2,3', $where);
// Results in: ['status' => ['$in' => [1, 2, 3]]]
```

---

### `mdbStatus(string $field, ?string $value, array &$where, ?string $orWhere = ""): void`

Creates MongoDB status field filter.

**Parameters**:
- `$field` (string) - Status field name
- `$value` (string|null) - Status value untuk di-match
- `$where` (array) - Reference ke where conditions array
- `$orWhere` (string) - Optional 'or' untuk OR conditions

**Modifies**: `$where` array by reference

**Contoh**:
```php
// Search untuk active documents (status = 1, not deleted)
$where = [];
CoreMongodb::mdbStatus('status', '1', $where);
// Results in: ['status' => ['$ne' => -1, '$eq' => 1]]
```

---

### `mdbStringMatch(string $field, ?string $value, array &$where, ?string $orWhere = ""): void`

Matches strings dalam MongoDB array elements menggunakan regex.

**Parameters**:
- `$field` (string) - Field name untuk di-search
- `$value` (string|null) - Search value
- `$where` (array) - Reference ke where conditions array
- `$orWhere` (string) - Optional 'or' untuk OR conditions

**Modifies**: `$where` array by reference

**Contoh**:
```php
// Search untuk documents dimana 'tags' array contains exact match 'php'
$where = [];
CoreMongodb::mdbStringMatch('tags', 'php', $where);
// Results in: ['tags' => ['$elemMatch' => ['$regex' => '^php$', '$options' => 'i']]]

// Dengan OR condition
CoreMongodb::mdbStringMatch('tags', 'php', $where, 'or');
// Results in: [['tags' => ['$elemMatch' => ['$regex' => '^php$', '$options' => 'i']]]]
```

## MongoDB Query Operators

### Case-Insensitive String Matching

```php
['$regex' => 'pattern', '$options' => 'i']
```

- `$regex` - Regular expression pattern
- `$options` - 'i' untuk case-insensitive matching

### Exact Match dengan Anchors

```php
['$regex' => '^value$', '$options' => 'i']
```

- `^` - Start of string
- `$` - End of string

### Multiple Values

```php
['$in' => [1, 2, 3]]
```

- `$in` - Match any value dalam array

### Array Element Matching

```php
['$elemMatch' => ['$regex' => '^value$', '$options' => 'i']]
```

- `$elemMatch` - Match array elements

## Contoh Penggunaan Lengkap

### Basic Search

```php
use app\core\CoreMongodb;

public function actionSearch($keyword)
{
    $where = [];
    
    // Search dalam name field
    CoreMongodb::mdbStringLike('name', $keyword, $where);
    
    $models = Product::find()->where($where)->all();
    
    return $models;
}
```

### Multiple Filters

```php
public function actionFilter($name, $status, $quantity)
{
    $where = [];
    
    // String search
    CoreMongodb::mdbStringLike('name', $name, $where);
    
    // Status filter
    CoreMongodb::mdbStatus('status', $status, $where);
    
    // Numeric filter
    CoreMongodb::mdbNumberEqual('quantity', $quantity, $where);
    
    $models = Product::find()->where($where)->all();
    
    return $models;
}
```

### OR Conditions

```php
public function actionSearchMultiple($keyword1, $keyword2)
{
    $where = [];
    
    // Search dalam name field dengan OR
    CoreMongodb::mdbStringLike('name', $keyword1, $where, 'or');
    CoreMongodb::mdbStringLike('description', $keyword2, $where, 'or');
    
    $models = Product::find()->where(['$or' => $where])->all();
    
    return $models;
}
```

### Array Element Search

```php
public function actionSearchTags($tag)
{
    $where = [];
    
    // Search dalam tags array
    CoreMongodb::mdbStringMatch('tags', $tag, $where);
    
    $models = Product::find()->where($where)->all();
    
    return $models;
}
```

### Complex Query

```php
public function actionComplexSearch($name, $statuses, $minQuantity)
{
    $where = [];
    
    // String search
    CoreMongodb::mdbStringLike('name', $name, $where);
    
    // Multiple status values
    CoreMongodb::mdbNumberMultiple('status', $statuses, $where);
    
    // Minimum quantity
    $where['quantity'] = ['$gte' => intval($minQuantity)];
    
    $models = Product::find()->where($where)->all();
    
    return $models;
}
```

## Best Practices

### 1. Null Handling

```php
$where = [];

// Only add filter jika value tidak null
if ($keyword) {
    CoreMongodb::mdbStringLike('name', $keyword, $where);
}

$models = Product::find()->where($where)->all();
```

### 2. Input Validation

```php
$where = [];

// Validate input sebelum query
if (!empty($keyword) && is_string($keyword)) {
    CoreMongodb::mdbStringLike('name', $keyword, $where);
}

$models = Product::find()->where($where)->all();
```

### 3. Combine Multiple Filters

```php
$where = [];

CoreMongodb::mdbStringLike('name', $name, $where);
CoreMongodb::mdbStatus('status', $status, $where);
CoreMongodb::mdbNumberEqual('category_id', $categoryId, $where);

$models = Product::find()->where($where)->all();
```

### 4. Use OR Conditions Carefully

```php
$where = [];

// Add first condition
CoreMongodb::mdbStringLike('name', $keyword, $where, 'or');

// Add second condition
CoreMongodb::mdbStringLike('description', $keyword, $where, 'or');

// Use $or operator
$models = Product::find()->where(['$or' => $where])->all();
```

## Catatan Penting

- Semua method adalah static
- Methods memodify `$where` array by reference
- Null values diabaikan (tidak menambah filter)
- String matching adalah case-insensitive
- Spaces dalam string diconvert ke `.*` untuk like matching
- OR conditions memerlukan `['$or' => $where]` wrapper
- Numeric values automatically diconvert ke integer
- Array element matching menggunakan `$elemMatch` operator
