# CoreConstants Documentation

**Namespace**: `app\core`  
**Version**: 1.0.0  
**Last Updated**: 2025-11-04

## Deskripsi

`CoreConstants` menyediakan standardized constants untuk aplikasi core functionality, termasuk:
- Status codes
- Scenario definitions
- Synchronization/locking constants
- Validation patterns
- Status lists dan transitions

## Fitur Utama

- ✅ Standardized status codes
- ✅ Scenario definitions untuk model operations
- ✅ Synchronization constants
- ✅ Validation patterns
- ✅ Status transition rules
- ✅ Filtered status conditions

## Constants

### Status Codes

```php
const STATUS_INACTIVE      = 0;  // Inactive status
const STATUS_ACTIVE        = 1;  // Active status
const STATUS_DRAFT         = 2;  // Draft status
const STATUS_COMPLETED     = 3;  // Completed status
const STATUS_DELETED       = 4;  // Deleted status
const STATUS_MAINTENANCE   = 5;  // Maintenance status
const STATUS_APPROVED      = 6;  // Approved status
const STATUS_REJECTED      = 7;  // Rejected status
```

**Penggunaan**:
```php
$model->status = CoreConstants::STATUS_ACTIVE;
```

---

### Scenario Definitions

```php
const SCENARIO_DEFAULT      = 'default';      // Default scenario
const SCENARIO_CREATE       = 'create';       // Create scenario
const SCENARIO_UPDATE       = 'update';       // Update scenario
const SCENARIO_DELETE       = 'delete';       // Delete scenario
const SCENARIO_DRAFT        = 'draft';        // Draft scenario
const SCENARIO_VIEW         = 'view';         // View scenario
const SCENARIO_COMPLETED    = 'completed';    // Completed scenario
const SCENARIO_RECEIVE      = 'receive';      // Receive scenario
const SCENARIO_RECEIVE_ITEM = 'receiveItem';  // Receive item scenario
const SCENARIO_REJECT       = 'reject';       // Reject scenario
const SCENARIO_REJECT_ITEM  = 'rejectItem';   // Reject item scenario
const SCENARIO_APPROVE      = 'approve';      // Approve scenario
const SCENARIO_DETAIL       = 'detail';       // Detail scenario
```

**Penggunaan**:
```php
$model->scenario = CoreConstants::SCENARIO_CREATE;
```

---

### Synchronization / Locking Constants

```php
const OPTIMISTIC_LOCK = 'lock_version';  // Optimistic lock field
const SYNC_MONGODB    = 'sync_mdb';      // MongoDB sync field
const SYNC_MASTER     = 'sync_master';   // Master sync field
const SYNC_SLAVE      = 'sync_slave';    // Slave sync field
const SLAVE_ID        = 'slave_id';      // Slave ID field
const MASTER_ID       = 'master_id';     // Master ID field
```

**Penggunaan**:
```php
$model->lock_version = $model->{CoreConstants::OPTIMISTIC_LOCK};
```

---

### Validation Patterns

```php
const DECIMAL_PATTERN = '/^\d+(\.\d{1,2})?$/';  // Decimal number pattern
```

**Penggunaan**:
```php
if (preg_match(CoreConstants::DECIMAL_PATTERN, $value)) {
    // Valid decimal number
}
```

---

### Filtered Statuses

```php
const STATUS_NOT_DELETED = [
    '<>', 'status', self::STATUS_DELETED
];
```

**Penggunaan**:
```php
$query->andFilterWhere(CoreConstants::STATUS_NOT_DELETED);
```

---

### Status Labels

```php
const STATUS_LIST = [
    self::STATUS_INACTIVE    => 'Inactive',
    self::STATUS_ACTIVE      => 'Active',
    self::STATUS_DRAFT       => 'Draft',
    self::STATUS_COMPLETED   => 'Completed',
    self::STATUS_DELETED     => 'Deleted',
    self::STATUS_MAINTENANCE => 'Maintenance',
    self::STATUS_APPROVED    => 'Approved',
    self::STATUS_REJECTED    => 'Rejected',
];
```

**Penggunaan**:
```php
echo CoreConstants::STATUS_LIST[$model->status];  // Output: "Active"
```

---

### Purchase Status List

```php
const PURCHASE_STATUS_LIST = self::STATUS_LIST;
```

**Penggunaan**:
```php
$statuses = CoreConstants::PURCHASE_STATUS_LIST;
```

---

### Restricted Status List

```php
const RESTRICT_STATUS_LIST = [
    self::STATUS_DELETED,
    self::STATUS_COMPLETED,
];
```

**Penggunaan**: Digunakan untuk mencegah update pada item dengan status tertentu

```php
if (in_array($model->status, CoreConstants::RESTRICT_STATUS_LIST)) {
    throw new Exception('Cannot modify item with restricted status');
}
```

---

### Scenario Update List

```php
const SCENARIO_UPDATE_LIST = [
    self::SCENARIO_UPDATE,
    self::SCENARIO_DELETE,
];
```

**Penggunaan**: Scenarios yang mengizinkan update operations

---

### Allowed Update Status List

```php
const ALLOWED_UPDATE_STATUS_LIST = [
    self::STATUS_DRAFT => [
        self::STATUS_INACTIVE,
        self::STATUS_ACTIVE,
        self::STATUS_DELETED,
        self::STATUS_MAINTENANCE,
    ],
    self::STATUS_ACTIVE => [
        self::STATUS_COMPLETED,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ],
    self::STATUS_INACTIVE => [
        self::STATUS_ACTIVE,
        self::STATUS_DRAFT,
        self::STATUS_DELETED,
    ],
    self::STATUS_MAINTENANCE => [
        self::STATUS_INACTIVE,
        self::STATUS_ACTIVE,
        self::STATUS_DRAFT,
        self::STATUS_DELETED,
    ],
    self::STATUS_APPROVED => [
        self::STATUS_COMPLETED,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ],
];
```

**Penggunaan**: Mendefinisikan status transitions yang diizinkan

```php
$currentStatus = $model->status;
$newStatus = CoreConstants::STATUS_ACTIVE;

if (!in_array($newStatus, CoreConstants::ALLOWED_UPDATE_STATUS_LIST[$currentStatus] ?? [])) {
    throw new Exception('Invalid status transition');
}
```

---

### Disallowed Update Status List

```php
const DISALLOWED_UPDATE_STATUS_LIST = [
    self::STATUS_COMPLETED,
    self::STATUS_DELETED,
    self::STATUS_REJECTED,
];
```

**Penggunaan**: Status yang tidak dapat diupdate

```php
if (in_array($model->status, CoreConstants::DISALLOWED_UPDATE_STATUS_LIST)) {
    throw new Exception('Cannot update item with this status');
}
```

## Contoh Penggunaan Lengkap

### Status Management

```php
use app\core\CoreConstants;

class Product extends ActiveRecord
{
    public function rules()
    {
        return [
            [['status'], 'in', 'range' => array_keys(CoreConstants::STATUS_LIST)],
        ];
    }
    
    public function getStatusLabel()
    {
        return CoreConstants::STATUS_LIST[$this->status] ?? 'Unknown';
    }
}

// Usage
$product = new Product();
$product->status = CoreConstants::STATUS_DRAFT;
echo $product->getStatusLabel();  // Output: "Draft"
```

### Scenario-based Validation

```php
class Order extends ActiveRecord
{
    public function scenarios()
    {
        return [
            CoreConstants::SCENARIO_CREATE => ['name', 'email', 'items'],
            CoreConstants::SCENARIO_UPDATE => ['name', 'email'],
            CoreConstants::SCENARIO_DELETE => [],
        ];
    }
}

// Usage
$order = new Order();
$order->scenario = CoreConstants::SCENARIO_CREATE;
```

### Status Transition Validation

```php
public function updateStatus($newStatus)
{
    $currentStatus = $this->status;
    $allowed = CoreConstants::ALLOWED_UPDATE_STATUS_LIST[$currentStatus] ?? [];
    
    if (!in_array($newStatus, $allowed)) {
        throw new Exception("Cannot transition from $currentStatus to $newStatus");
    }
    
    $this->status = $newStatus;
    return $this->save();
}
```

## Catatan Penting

- Semua constants adalah class constants, dapat diakses via `CoreConstants::CONSTANT_NAME`
- Status codes digunakan secara konsisten di seluruh aplikasi
- Scenarios membantu dalam conditional validation
- Status transitions harus divalidasi sebelum update
