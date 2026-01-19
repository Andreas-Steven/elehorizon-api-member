# Core Documentation Index

**Last Updated**: November 28, 2025  
**Total Documentation Files**: 13  
**Total Size**: ~122 KB

---

## 📖 Documentation Files

### 🏠 Main Documentation
- **[README.md](./README.md)** - Main index dengan quick start dan common use cases
- **[SUMMARY.md](./SUMMARY.md)** - Documentation summary dan statistics
- **[INDEX.md](./INDEX.md)** - File ini (navigation index)

### 🔌 API & Controller Layer

#### [CoreAPI.md](./CoreAPI.md)
**Utility methods untuk timestamps, user management, validation, dan error handling**

Methods:
- `UTCTimestamp()` - Get UTC timestamp
- `timestamp()` - Get local timestamp
- `getUsername()` - Get current username
- `superAdmin()` - Check superadmin role
- `unavailableParams()` - Validate request parameters
- `unauthorizedAccess()` - Throw 401 error
- `serverError()` - Throw 500 error
- `setMongodbSyncFailed()` - Mark MongoDB sync failed
- `generateUniqueString()` - Generate unique string
- `coreDevelopmentPurpose()` - Check development domain
- `dbConnectionTarget()` - Get database connection

---

#### [CoreController.md](./CoreController.md)
**Base controller untuk RESTful API endpoints**

Methods:
- `behaviors()` - Configure CORS, content negotiation, authentication
- `beforeAction()` - Pre-action exception handling
- `actionIndex()` - Default action dengan service info
- `coreActionIndex()` - Alias untuk actionIndex
- `actionError()` - Error action
- `errorHandler()` - Exception handling
- `coreFindModelOne()` - Find single model by ID atau parameters
- `coreFindModel()` - Find model dengan query chaining
- `coreData()` - Format data provider response dengan pagination
- `coreCustomData()` - Format custom data response
- `coreSuccess()` - Format success response dengan model data

---

#### [CoreErrorHandler.md](./CoreErrorHandler.md)
**Custom error handler untuk standardized JSON responses**

Features:
- Handles CoreException dengan custom errors
- Handles HttpException dengan HTTP status codes
- Handles StaleObjectException (optimistic lock)
- Includes trace information dalam development mode
- Standardized response format

---

### 📊 Model & Data Layer

#### [CoreModel.md](./CoreModel.md)
**Core model utilities untuk data manipulation dan validation**

Methods:
- `getModelClassName()` - Get short class name
- `nullSafe()` - Convert null strings ke null
- `isNullString()` - Check null representation
- `htmlPurifier()` - Purify HTML dan remove tags
- `contentPurifier()` - Purify HTML dengan allowed tags
- `ensureArray()` - Ensure value adalah array
- `purifyArray()` - Recursively purify array
- `spaceToPercent()` - Convert spaces ke %
- `setLikeFilter()` - Create case-insensitive LIKE filter
- `isRestrictedStatus()` - Check restricted status
- `isJsonString()` - Validate JSON string
- `getStatusRules()` - Get status validation rules
- `getSyncMdbRules()` - Get MongoDB sync rules
- `getMasterRules()` - Get master sync rules
- `getSlaveRules()` - Get slave sync rules
- `validateAttributeArray()` - Validate array attribute
- `validateAttributeArrayOrNull()` - Validate array or null

---

#### [CoreConstants.md](./CoreConstants.md)
**Standardized constants untuk status codes, scenarios, validation patterns**

Constants:
- **Status Codes**: INACTIVE, ACTIVE, DRAFT, COMPLETED, DELETED, MAINTENANCE, APPROVED, REJECTED
- **Scenarios**: DEFAULT, CREATE, UPDATE, DELETE, DRAFT, VIEW, COMPLETED, RECEIVE, REJECT, APPROVE, DETAIL
- **Synchronization**: OPTIMISTIC_LOCK, SYNC_MONGODB, SYNC_MASTER, SYNC_SLAVE, SLAVE_ID, MASTER_ID
- **Validation Patterns**: DECIMAL_PATTERN
- **Status Lists**: STATUS_LIST, PURCHASE_STATUS_LIST, RESTRICT_STATUS_LIST
- **Status Transitions**: ALLOWED_UPDATE_STATUS_LIST, DISALLOWED_UPDATE_STATUS_LIST

---

### 🗄️ Database Query Builders

#### [CoreMySQL.md](./CoreMySQL.md)
**Base ActiveQuery class untuk MySQL database queries**

Methods:
- **Record Retrieval**: `all()`, `one()`
- **Status Filters**: `byStatus()`, `inactive()`, `active()`, `draft()`, `completed()`, `deleted()`, `maintenance()`, `approved()`, `rejected()`
- **Sorting**: `orderBySortOrder()`, `orderByName()`
- **ID & Name Filters**: `findById()`, `findByIds()`, `findByName()`, `findByNameLike()`

---

#### [CorePostgreSQL.md](./CorePostgreSQL.md)
**Base ActiveQuery class untuk PostgreSQL database queries**

Methods:
- **Record Retrieval**: `all()`, `one()`
- **Status Filters**: `byStatus()`, `inactive()`, `active()`, `draft()`, `completed()`, `deleted()`, `maintenance()`, `approved()`, `rejected()`
- **Sorting**: `orderBySortOrder()`, `orderByName()`
- **ID & Name Filters**: `findById()`, `findByIds()`, `findByName()`, `findByNameLike()`
- **PostgreSQL Features**: JSON field filtering, date range queries, array operations

---

#### [CoreMongodb.md](./CoreMongodb.md)
**Utility methods untuk MongoDB queries**

Methods:
- `getModelClassName()` - Get class name
- `mdbStringLike()` - Case-insensitive string matching
- `mdbStringEqual()` - Exact string match dengan regex anchors
- `mdbNumberEqual()` - Numeric equality filtering
- `mdbNumberMultiple()` - Multiple numbers dengan $in operator
- `mdbStatus()` - Status field filtering
- `mdbStringMatch()` - Array element matching

---

### ⚙️ Configuration & Localization

#### [CoreParamLoader.md](./CoreParamLoader.md)
**Loader untuk default core parameters**

Methods:
- `load()` - Load default core parameters dari params_core.php

Features:
- Foundation untuk parameter merging
- Supports project-level overrides
- Supports user-defined parameters

---

#### [CoreMessageSource.md](./CoreMessageSource.md)
**Custom message source untuk i18n/translation**

Methods:
- `loadMessages()` - Load messages dari core dan app files
- `translate()` - Translate message ke specified language

Features:
- Merges core dan app translations
- App messages override core messages
- Fallback ke default language
- Standardized missing translation messages

---

#### [Environment.md](./Environment.md)
**Environment variable helper functions**

Functions:
- `env_group()` - Get grouped environment variables by prefix
- `env_value()` - Get single environment variable value
- `parse_env_value()` - Parse environment variable ke appropriate type

Features:
- Automatic type parsing (boolean, null, numeric)
- Nested array support dengan dot notation
- Default value fallback
- Case-insensitive key matching

---

## 🔍 Quick Navigation

### By Use Case

**Building API Endpoints**
1. Read [CoreController.md](./CoreController.md)
2. Read [CoreModel.md](./CoreModel.md)
3. Read [CoreErrorHandler.md](./CoreErrorHandler.md)

**Querying Database**
1. Read [CoreMySQL.md](./CoreMySQL.md) or [CorePostgreSQL.md](./CorePostgreSQL.md)
2. Read [CoreMongodb.md](./CoreMongodb.md)
3. Read [CoreConstants.md](./CoreConstants.md)

**Data Validation & Purification**
1. Read [CoreModel.md](./CoreModel.md)
2. Read [CoreAPI.md](./CoreAPI.md)

**Configuration Management**
1. Read [CoreParamLoader.md](./CoreParamLoader.md)
2. Read [Environment.md](./Environment.md)

**Internationalization**
1. Read [CoreMessageSource.md](./CoreMessageSource.md)

---

### By Skill Level

**Beginner**
1. [README.md](./README.md) - Overview dan quick start
2. [CoreAPI.md](./CoreAPI.md) - Utility methods
3. [CoreController.md](./CoreController.md) - Basic API endpoints

**Intermediate**
1. [CoreModel.md](./CoreModel.md) - Data utilities
2. [CoreMySQL.md](./CoreMySQL.md) or [CorePostgreSQL.md](./CorePostgreSQL.md) - Database queries
3. [CoreConstants.md](./CoreConstants.md) - Constants reference

**Advanced**
1. [CoreMongodb.md](./CoreMongodb.md) - MongoDB queries
2. [CoreErrorHandler.md](./CoreErrorHandler.md) - Error handling
3. [CoreMessageSource.md](./CoreMessageSource.md) - i18n
4. [CoreParamLoader.md](./CoreParamLoader.md) - Parameter management
5. [Environment.md](./Environment.md) - Environment configuration

---

## 📊 Statistics

### File Sizes
| File | Size |
|------|------|
| CoreAPI.md | 5.7 KB |
| CoreConstants.md | 7.7 KB |
| CoreController.md | 9.2 KB |
| CoreErrorHandler.md | 7.3 KB |
| CoreMessageSource.md | 8.4 KB |
| CoreModel.md | 10.1 KB |
| CoreMongodb.md | 9.4 KB |
| CoreMySQL.md | 8.6 KB |
| CoreParamLoader.md | 7.8 KB |
| CorePostgreSQL.md | 9.6 KB |
| Environment.md | 10.5 KB |
| README.md | 8.6 KB |
| SUMMARY.md | 9.5 KB |
| **TOTAL** | **~122 KB** |

### Method/Function Count
| Category | Count |
|----------|-------|
| CoreAPI Methods | 11 |
| CoreController Methods | 11 |
| CoreModel Methods | 18 |
| CoreMySQL Methods | 18 |
| CorePostgreSQL Methods | 18 |
| CoreMongodb Methods | 7 |
| CoreConstants | 30+ |
| Environment Functions | 3 |
| **TOTAL** | **70+** |

---

## 🎯 Common Tasks

### Task: Create API Endpoint
**Files to Read**: [CoreController.md](./CoreController.md), [CoreModel.md](./CoreModel.md)

### Task: Query Database
**Files to Read**: [CoreMySQL.md](./CoreMySQL.md) or [CorePostgreSQL.md](./CorePostgreSQL.md), [CoreConstants.md](./CoreConstants.md)

### Task: Validate & Purify Data
**Files to Read**: [CoreModel.md](./CoreModel.md), [CoreAPI.md](./CoreAPI.md)

### Task: Handle Errors
**Files to Read**: [CoreErrorHandler.md](./CoreErrorHandler.md), [CoreAPI.md](./CoreAPI.md)

### Task: Configure Application
**Files to Read**: [CoreParamLoader.md](./CoreParamLoader.md), [Environment.md](./Environment.md)

### Task: Implement i18n
**Files to Read**: [CoreMessageSource.md](./CoreMessageSource.md)

---

## 💡 Tips

1. **Use Ctrl+F** untuk search method/function tertentu
2. **Setiap file independent** - bisa dibaca standalone
3. **Examples copyable** - semua contoh kode siap digunakan
4. **Best practices included** - setiap file punya best practices section
5. **Cross-references** - file saling referensi untuk konteks lebih baik

---

## 📝 File Organization

```
docs/core/
├── INDEX.md                 ← You are here
├── README.md                ← Start here
├── SUMMARY.md               ← Statistics & overview
│
├── CoreAPI.md               ← Utility methods
├── CoreController.md        ← Base API controller
├── CoreErrorHandler.md      ← Error handling
│
├── CoreModel.md             ← Data utilities
├── CoreConstants.md         ← Constants
│
├── CoreMySQL.md             ← MySQL queries
├── CorePostgreSQL.md        ← PostgreSQL queries
├── CoreMongodb.md           ← MongoDB queries
│
├── CoreParamLoader.md       ← Parameter loading
├── CoreMessageSource.md     ← i18n/translation
└── Environment.md           ← Environment variables
```

---

## ✅ Checklist for Using Documentation

- [ ] Read README.md untuk overview
- [ ] Identify relevant class/function untuk task Anda
- [ ] Baca dokumentasi yang relevan
- [ ] Review examples dan best practices
- [ ] Implement sesuai use case Anda
- [ ] Test implementation
- [ ] Refer back ke documentation jika ada pertanyaan

---

## 🔗 Related Resources

- **Yii2 Framework**: https://www.yiiframework.com/
- **Yii2 Guide**: https://www.yiiframework.com/doc/guide/2.0/en
- **Yii2 API**: https://www.yiiframework.com/doc/api/2.0

---

**Last Updated**: November 28, 2025  
**Documentation Version**: 1.0.0  
**Language**: Indonesian (Bahasa Indonesia)
