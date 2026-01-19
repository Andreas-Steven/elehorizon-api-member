# CoreModel Documentation

**Namespace**: `app\core`  
**Version**: 1.0.0  
**Last Updated**: 2025-05-05

## Deskripsi

`CoreModel` menyediakan core model utilities untuk data manipulation dan validation, termasuk:
- Class name retrieval
- Null-safe value conversion
- HTML purification
- Array handling
- Validation helper methods
- Pagination dan sorting utilities
- Change log management

## Fitur Utama

- ✅ Model class name retrieval
- ✅ Null-safe value conversion
- ✅ HTML purification (dengan dan tanpa tags)
- ✅ Array validation dan purification
- ✅ JSON validation
- ✅ Status validation rules
- ✅ Like filter generation
- ✅ Restricted status checking

## Metode-Metode

### `getModelClassName($model): string`

Mendapatkan short class name tanpa namespace.

**Parameters**:
- `$model` (object) - Model instance

**Return**: `string` - Class name tanpa namespace

**Contoh**:
```php
$modelName = CoreModel::getModelClassName($user);
// If $user is instance of app\models\User
// Returns: 'User'
```

---

### `nullSafe(?string $value = null): ?string`

Safely converts string 'null' dan empty string values ke actual null.

**Parameters**:
- `$value` (string|null) - Value untuk di-check

**Return**: `string|null` - Original value atau null

**Contoh**:
```php
$value = CoreModel::nullSafe('null');   // Returns: null
$value = CoreModel::nullSafe('');       // Returns: null
$value = CoreModel::nullSafe('test');   // Returns: 'test'
```

---

### `isNullString($value): bool`

Mengecek apakah value represents null dalam berbagai formats.

**Parameters**:
- `$value` (mixed) - Value untuk di-check

**Return**: `bool` - True jika value represents null

**Contoh**:
```php
CoreModel::isNullString(null);      // Returns: true
CoreModel::isNullString('null');    // Returns: true
CoreModel::isNullString('NULL');    // Returns: true
CoreModel::isNullString('value');   // Returns: false
```

---

### `htmlPurifier(?string $value): ?string`

Safely purifies HTML content dan removes semua tags.

**Parameters**:
- `$value` (string|null) - Value untuk di-purify

**Return**: `string|null` - Purified string dengan semua HTML tags dihapus

**Contoh**:
```php
$safeText = CoreModel::htmlPurifier('<p>Hello <script>alert("xss")</script></p>');
// Returns: 'Hello'
```

---

### `contentPurifier(?string $value): ?string`

Purifies HTML content sambil preserving allowed tags.

**Parameters**:
- `$value` (string|null) - Value untuk di-purify

**Return**: `string|null` - Purified string dengan safe HTML tags

**Contoh**:
```php
$safeHtml = CoreModel::contentPurifier('<p>Hello <script>alert("xss")</script></p>');
// Returns: '<p>Hello </p>'
```

---

### `ensureArray($array): array`

Ensures value adalah valid array. Returns empty array untuk null atau non-array values.

**Parameters**:
- `$array` (mixed) - Value untuk di-check

**Return**: `array` - Valid array atau empty array

**Contoh**:
```php
$array = CoreModel::ensureArray(null);     // Returns: []
$array = CoreModel::ensureArray('string'); // Returns: []
$array = CoreModel::ensureArray([1,2,3]);  // Returns: [1,2,3]
```

---

### `purifyArray($input): mixed`

Recursively purifies semua values dalam array atau single value.

**Parameters**:
- `$input` (mixed) - Array, scalar, atau null value untuk di-purify

**Return**: `mixed` - Purified array, scalar value, atau null

**Contoh**:
```php
$data = [
    'name' => '<p>John</p>',
    'email' => '<script>alert("xss")</script>email@test.com',
    'nested' => [
        'comment' => '<b>Hello</b>'
    ]
];

$safe = CoreModel::purifyArray($data);
// Returns:
// [
//     'name' => 'John',
//     'email' => 'email@test.com',
//     'nested' => [
//         'comment' => 'Hello'
//     ]
// ]
```

---

### `spaceToPercent(?string $value): ?string`

Converts spaces ke percent signs untuk LIKE queries.

**Parameters**:
- `$value` (string|null) - Value untuk di-convert

**Return**: `string|null` - Value dengan spaces converted ke %

**Contoh**:
```php
$result = CoreModel::spaceToPercent('John Doe');
// Returns: '%John%Doe%'
```

---

### `setLikeFilter(?string $value = null, string $field = 'name', string $operator = 'ilike'): array`

Creates case-insensitive LIKE filter untuk database queries.

**Parameters**:
- `$value` (string|null) - Search value
- `$field` (string) - Database field name (default: 'name')
- `$operator` (string) - SQL operator (default: 'ilike')

**Return**: `array` - Query condition array

**Contoh**:
```php
$query->andFilterWhere(CoreModel::setLikeFilter('John', 'name'));
// Generates: WHERE name ILIKE '%John%'

$query->andFilterWhere(CoreModel::setLikeFilter('John Doe', 'name', 'like'));
// Generates: WHERE name LIKE '%John%Doe%'
```

---

### `isRestrictedStatus(int $status): bool`

Mengecek apakah status ada dalam restricted list.

**Parameters**:
- `$status` (int) - Status value untuk di-check

**Return**: `bool` - True jika status restricted

**Contoh**:
```php
if (CoreModel::isRestrictedStatus($model->status)) {
    throw new Exception('Cannot modify item with restricted status');
}
```

---

### `isJsonString($value): bool`

Validates apakah string adalah valid JSON.

**Parameters**:
- `$value` (string) - String untuk di-validate

**Return**: `bool` - True jika string valid JSON

**Contoh**:
```php
if (CoreModel::isJsonString($value)) {
    // Process valid JSON
} else {
    throw new Exception('Invalid JSON format');
}
```

---

### `getStatusRules($model, ?array $list = []): array`

Gets validation rules untuk status field.

**Parameters**:
- `$model` (object) - Model instance
- `$list` (array|null) - Custom status list (optional)

**Return**: `array` - Array of validation rules

**Contoh**:
```php
public function rules()
{
    return array_merge(
        [
            // other rules
        ],
        CoreModel::getStatusRules($this)
    );
}
```

---

### `getSyncMdbRules($model = null): array`

Gets validation rules untuk sync_mdb field.

**Return**: `array` - Array of validation rules

**Contoh**:
```php
public function rules()
{
    return array_merge(
        [
            // other rules
        ],
        CoreModel::getSyncMdbRules()
    );
}
```

---

### `getMasterRules(): array`

Gets validation rules untuk MASTER_ID dan SYNC_MASTER fields.

**Return**: `array` - Array of validation rules

**Contoh**:
```php
public function rules()
{
    return array_merge(
        [
            // other rules
        ],
        CoreModel::getMasterRules()
    );
}
```

---

### `getSlaveRules(): array`

Gets validation rules untuk SLAVE_ID dan SYNC_SLAVE fields.

**Return**: `array` - Array of validation rules

**Contoh**:
```php
public function rules()
{
    return array_merge(
        [
            // other rules
        ],
        CoreModel::getSlaveRules()
    );
}
```

---

### `validateAttributeArray($model, $attribute, $label): void`

Validates bahwa attribute adalah array.

**Parameters**:
- `$model` (object) - Model instance
- `$attribute` (string) - Attribute name
- `$label` (string) - Human-readable label

**Throws**: `CoreException` jika validation gagal

**Contoh**:
```php
public function rules()
{
    return [
        [['tags'], function($attribute) {
            CoreModel::validateAttributeArray($this, $attribute, 'Tags');
        }]
    ];
}
```

---

### `validateAttributeArrayOrNull($model, string $attribute, string $label): void`

Validates bahwa attribute adalah array atau null/'null' string.

**Parameters**:
- `$model` (object) - Model instance
- `$attribute` (string) - Attribute name
- `$label` (string) - Human-readable label

**Throws**: `CoreException` jika validation gagal

**Contoh**:
```php
public function rules()
{
    return [
        [['optional_tags'], function($attribute) {
            CoreModel::validateAttributeArrayOrNull($this, $attribute, 'Optional Tags');
        }]
    ];
}
```

## Contoh Implementasi Lengkap

### Model dengan Status Validation

```php
use app\core\CoreModel;
use app\core\CoreConstants;

class Product extends ActiveRecord
{
    public function rules()
    {
        return array_merge(
            [
                [['name'], 'required'],
                [['description'], 'string'],
            ],
            CoreModel::getStatusRules($this)
        );
    }
    
    public function beforeSave($insert)
    {
        // Purify user input
        $this->name = CoreModel::htmlPurifier($this->name);
        $this->description = CoreModel::htmlPurifier($this->description);
        
        return parent::beforeSave($insert);
    }
}
```

### Search dengan Like Filter

```php
public function actionSearch($keyword)
{
    $query = Product::find();
    
    if ($keyword) {
        $query->andFilterWhere(CoreModel::setLikeFilter($keyword, 'name'));
    }
    
    $dataProvider = new ActiveDataProvider([
        'query' => $query,
    ]);
    
    return $dataProvider;
}
```

### Array Purification

```php
public function actionCreate()
{
    $data = Yii::$app->request->post();
    
    // Purify all input data
    $data = CoreModel::purifyArray($data);
    
    $model = new Product();
    $model->load($data);
    
    if ($model->save()) {
        return ['success' => true];
    }
    
    throw new CoreException($model, 'Validation failed', 422);
}
```

## Catatan Penting

- Semua method adalah static
- HTML purification menggunakan HTMLPurifier library
- Null-safe methods membantu handle form input
- Status rules include default value, type checking, dan range validation
- Array purification recursive untuk nested arrays
- Like filter automatically handles spaces dan wildcards
