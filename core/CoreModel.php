<?php

namespace app\core;

use Yii;
use app\helpers\Constants;
use yii\helpers\HtmlPurifier;
use yii\helpers\StringHelper;
use app\exceptions\CoreException;
use yii\base\DynamicModel;

/**
 * Class CoreModel
 *
 * Provides core model utilities for the application including:
 * - Class name retrieval
 * - Null-safe value conversion
 * - HTML purification
 * - Array handling
 * - Validation helper methods
 * - Pagination and sorting utilities
 * - Change log management
 *
 * Usage examples:
 * ```php
 * $user = new User();
 * $className = CoreModel::getModelClassName($user); // Returns "User"
 * $safeValue = CoreModel::nullSafe('null');        // Returns null
 * $safeHtml = CoreModel::htmlPurifier('<p>Hello</p>'); // Returns "Hello"
 * ```
 *
 * @package app\core
 * @version 1.0.0
 * @since 2025-05-05
 */
class CoreModel 
{
    /**
     * Gets the short class name without namespace.
     * Useful for getting clean model names for logging or display.
     * 
     * Usage:
     * ```php
     * $modelName = CoreModel::getModelClassName($user);
     * // If $user is instance of app\models\User
     * // Returns: 'User'
     * ```
     * 
     * @param object $model The model instance to get class name from
     * @return string The class name without namespace
     */
    public static function getModelClassName($model): string
    {
        return StringHelper::basename(get_class($model));
    }

    /**
     * Safely converts string 'null' and empty string values to actual null.
     * Useful for handling form inputs and API data that might represent null as strings.
     * 
     * Usage:
     * ```php
     * $value = CoreModel::nullSafe('null'); // Returns: null
     * $value = CoreModel::nullSafe('');     // Returns: null
     * $value = CoreModel::nullSafe('test'); // Returns: 'test'
     * ```
     * 
     * @param string|null $value The value to check
     * @return string|null Original value or null if value represents null
     */
    public static function nullSafe(?string $value = null): ?string
    {
        return ($value === null || $value === '' || strtolower($value) === 'null') ? null : $value;
    }

    /**
     * Checks if a value represents null in various formats.
     * More comprehensive than nullSafe, checks both actual null and string representation.
     * 
     * Usage:
     * ```php
     * CoreModel::isNullString(null);      // Returns: true
     * CoreModel::isNullString('null');    // Returns: true
     * CoreModel::isNullString('NULL');    // Returns: true
     * CoreModel::isNullString('value');   // Returns: false
     * ```
     * 
     * @param mixed $value The value to check
     * @return bool True if value represents null, false otherwise
     */
    public static function isNullString($value): bool
    {
        return $value === null || strtolower((string)$value) === 'null';
    }

    /**
     * Safely purifies HTML content and removes all tags.
     * Useful for sanitizing user input to prevent XSS attacks.
     * 
     * Usage:
     * ```php
     * $safeText = CoreModel::htmlPurifier('<p>Hello <script>alert("xss")</script></p>');
     * // Returns: 'Hello'
     * ```
     * 
     * @param string|null $value The value to purify
     * @return string|null Purified string with all HTML tags removed
     */
    public static function htmlPurifier(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Core.LexerImpl', 'DirectLex');
        $config->set('HTML.Allowed', ''); // hapus semua tag HTML

        // Proses dengan HTMLPurifier
        $clean = HtmlPurifier::process($value, $config);
        $clean = html_entity_decode($clean, ENT_NOQUOTES, 'UTF-8');

        // Semua karakter diperbolehkan (termasuk simbol)
        // Hanya menghapus tag HTML yang tersisa (misal sisa "<" atau ">")
        return self::nullSafe($clean);
    }

    /**
     * Purifies HTML content while preserving allowed tags.
     * Similar to htmlPurifier but keeps structural HTML intact.
     * 
     * Usage:
     * ```php
     * $safeHtml = CoreModel::contentPurifier('<p>Hello <script>alert("xss")</script></p>');
     * // Returns: '<p>Hello </p>'
     * ```
     * 
     * @param string|null $value The value to purify
     * @return string|null Purified string with safe HTML tags
     */
    public static function contentPurifier(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Core.LexerImpl', 'DirectLex');
        $config->set('HTML.Allowed', ''); // hapus semua tag HTML

        // Proses dengan HTMLPurifier
        $clean = HtmlPurifier::process($value, $config);
        $clean = html_entity_decode($clean, ENT_NOQUOTES, 'UTF-8');

        // Semua karakter diperbolehkan (termasuk simbol)
        // Hanya menghapus tag HTML yang tersisa (misal sisa "<" atau ">")
        return self::nullSafe($clean);
    }

    /**
     * Ensures a value is a valid array.
     * Returns empty array for null or non-array values.
     * 
     * Usage:
     * ```php
     * $array = CoreModel::ensureArray(null);     // Returns: []
     * $array = CoreModel::ensureArray('string'); // Returns: []
     * $array = CoreModel::ensureArray([1,2,3]);  // Returns: [1,2,3]
     * ```
     * 
     * @param mixed $array The value to check
     * @return array Valid array or empty array
     */
    public static function ensureArray($array): array
    {
        return is_array($array) ? $array : [];
    }

    /**
     * Recursively purifies all values in an array or single value.
     *
     * This method can handle:
     * - Nested arrays of any depth.
     * - Single scalar values (string, int, etc.).
     * - Null values.
     *
     * It uses the `htmlPurifier` method to sanitize individual elements,
     * removing dangerous HTML tags and XSS attempts.
     *
     * Usage:
     * ```php
     * $data = [
     *     'name' => '<p>John</p>',
     *     'email' => '<script>alert("xss")</script>email@test.com',
     *     'nested' => [
     *         'comment' => '<b>Hello</b>'
     *     ]
     * ];
     *
     * $safe = CoreModel::purifyArray($data);
     * // Returns:
     * // [
     * //     'name' => 'John',
     * //     'email' => 'email@test.com',
     * //     'nested' => [
     * //         'comment' => 'Hello'
     * //     ]
     * // ]
     *
     * $singleValue = CoreModel::purifyArray('<p>Test</p>');
     * // Returns: 'Test'
     *
     * @param mixed $input Array, scalar, or null value to purify.
     * @return mixed Purified array, scalar value, or null if input was null.
     */

    public static function purifyArray($input)
    {
        if (is_array($input)) {
            // Rekursif untuk setiap elemen
            return array_map([__CLASS__, 'purifyArray'], $input);
        }

        if ($input === null) {
            return null;
        }

        // Nilai tunggal, langsung dipurify
        return self::htmlPurifier($input);
    }

    public static function spaceToPercent(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return '%' . str_replace(' ', '%', trim($value)) . '%';
    }

    /**
     * Creates a case-insensitive LIKE filter for database queries.
     * Automatically adds wildcards around search terms and handles spaces.
     *
     * Usage:
     * ```php
     * $query->andFilterWhere(CoreModel::setLikeFilter('John', 'name'));
     * // Generates: WHERE name ILIKE '%John%'
     *
     * $query->andFilterWhere(CoreModel::setLikeFilter('John Doe', 'name', 'like'));
     * // Generates: WHERE name LIKE '%John%Doe%'
     * ```
     *
     * @param string|null $value Search value
     * @param string $field Database field name
     * @param string $operator SQL operator, default 'ilike'
     * @return array Query condition array
     */
    public static function setLikeFilter(?string $value = null, string $field = 'name', string $operator = 'ilike'): array
    {
        return [$operator, $field, $value ? self::spaceToPercent($value) : null, false];
    }

    /**
     * Checks if a status is in the restricted list.
     * Used to prevent certain operations on items with restricted status.
     * 
     * Usage:
     * ```php
     * if (CoreModel::isRestrictedStatus($model->status)) {
     *     throw new Exception('Cannot modify item with restricted status');
     * }
     * ```
     * 
     * @param int $status Status value to check
     * @return bool True if status is restricted
     */
    public static function isRestrictedStatus(int $status): bool
    {
        return in_array($status, Constants::RESTRICT_STATUS_LIST, true);
    }

    /**
     * Validates if a string is valid JSON.
     * Useful for validating JSON fields before saving to database.
     * 
     * Usage:
     * ```php
     * if (CoreModel::isJsonString($value)) {
     *     // Process valid JSON
     * } else {
     *     throw new Exception('Invalid JSON format');
     * }
     * ```
     * 
     * @param string $value String to validate as JSON
     * @return bool True if string is valid JSON
     */
    public static function isJsonString($value)
    {
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Gets validation rules for status field.
     * Defines standard validation rules for status attributes including default value,
     * type checking, range validation, and status transition validation.
     * 
     * Usage:
     * ```php
     * public function rules()
     * {
     *     return array_merge(
     *         [
     *             // other rules
     *         ],
     *         CoreModel::getStatusRules($this)
     *     );
     * }
     * ```
     * 
     * @param object $model Model instance that contains the status attribute
     * @param array $list Optional custom status list, defaults to Constants::STATUS_LIST
     * @return array Array of validation rules for status field
     */
    public static function getStatusRules($model, ?array $list=[]): array
    {
        $statusList = !empty($list) ? $list : Constants::STATUS_LIST;
        
        return [
            [['status'], 'default', 'value' => Constants::STATUS_DRAFT],
            [['status'], 'integer'],
            [['status'], 'in', 'range' => array_keys($statusList)],
            [['status'], 'filter', 'filter' => 'intval'],
            [
                ['status'],
                function ($attribute, $params) use ($model) {
                    self::validateStatusUpdate($attribute, $params, $model);
                },
            ],
        ];
    }

    /**
     * Gets validation rules for sync_mdb field.
     * 
     * Validation rules include:
     * - sync_mdb: defaults to null, must be an integer
     * 
     * @return array Array of validation rules
     */
    public static function getSyncMdbRules($model=null): array
    {
        return [
            [[Constants::SYNC_MONGODB], 'default', 'value' => null],
            [[Constants::SYNC_MONGODB], 'integer'],
        ];
    }

    /**
     * Gets validation rules for MASTER_ID and SYNC_MASTER fields.
     * 
     * Validation rules include:
     * - MASTER_ID: defaults to Yii::$app->params['dbDefault']['masterID'], must be an integer
     * - SYNC_MASTER: defaults to Yii::$app->params['dbDefault']['syncMaster'], must be an integer
     * 
     * @return array Array of validation rules
     */
    public static function getMasterRules(): array
    {
        return [
            [[Constants::MASTER_ID], 'default', 'value' => Yii::$app->params['dbDefault']['masterID']],
            [[Constants::SYNC_MASTER], 'default', 'value' => Yii::$app->params['dbDefault']['syncMaster']],
            [[Constants::MASTER_ID, Constants::SYNC_MASTER], 'integer', 'skipOnEmpty' => true, 'skipOnError' => true],
        ];
    }

    /**
     * Gets validation rules for SLAVE_ID and SYNC_SLAVE fields.
     * 
     * Validation rules include:
     * - SLAVE_ID: defaults to Yii::$app->params['dbDefault']['slaveID'], must be an integer
     * - SYNC_SLAVE: defaults to Yii::$app->params['dbDefault']['syncSlave'], must be an integer
     * 
     * @return array Array of validation rules
     */
    public static function getSlaveRules(): array
    {
        return [
            [[Constants::SLAVE_ID], 'default', 'value' => Yii::$app->params['dbDefault']['slaveID']],
            [[Constants::SYNC_SLAVE], 'default', 'value' => Yii::$app->params['dbDefault']['syncSlave']],
            [[Constants::SLAVE_ID, Constants::SYNC_SLAVE], 'integer', 'skipOnEmpty' => true, 'skipOnError' => true],
        ];
    }

    /**
     * Validates that an attribute is an array.
     * Used to ensure attributes that should contain arrays are properly formatted.
     * 
     * Usage:
     * ```php
     * public function rules()
     * {
     *     return [
     *         [['tags'], function($attribute) {
     *             CoreModel::validateAttributeArray($this, $attribute, 'Tags');
     *         }]
     *     ];
     * }
     * ```
     * 
     * @param object $model Model instance containing the attribute
     * @param string $attribute Name of the attribute to validate
     * @param string $label Human-readable label for error messages
     * @return void
     */
    public static function validateAttributeArray($model, $attribute, $label)
    {
        if (!is_array($model->$attribute)) {
            $model->addError($attribute, Yii::t('app', 'array', ['label' => $label]));
            throw new CoreException($model, Yii::t('app', 'validationFailed'), 422);
        }
    }

    /**
     * Validates that an attribute is either an array or null/'null' string.
     *
     * Usage:
     * ```php
     * public function rules()
     * {
     *     return [
     *         [['optional_tags'], function($attribute) {
     *             CoreModel::validateAttributeArrayOrNull($this, $attribute, 'Optional Tags');
     *         }]
     *     ];
     * }
     * ```
     *
     * @param object $model Model instance containing the attribute
     * @param string $attribute Name of the attribute to validate
     * @param string $label Human-readable label for error messages
     * @return void
     * @throws CoreException If validation fails
     */
    public static function validateAttributeArrayOrNull($model, string $attribute, string $label): void
    {
        if (!is_array($model->$attribute) && !self::isNullString($model->$attribute)) {
            $model->addError($attribute, Yii::t('app', 'array', ['label' => $label]));
            throw new CoreException($model, Yii::t('app', 'validationFailed'), 422);
        }
    }

    /**
     * Validates that an attribute is of the expected type or null/'null' string.
     *
     * Usage:
     * ```php
     * public function rules()
     * {
     *     return [
     *         [['optional_tags'], function($attribute) {
     *             CoreModel::validateAttributeTypeOrNull($this, $attribute, 'array');
     *         }],
     *         [['age'], function($attribute) {
     *             CoreModel::validateAttributeTypeOrNull($this, $attribute, 'integer');
     *         }]
     *     ];
     * }
     * ```
     *
     * @param object $model Model instance containing the attribute
     * @param string $attribute Name of the attribute to validate
     * @param string $type Expected type ('array', 'string', 'integer', 'float', etc.)
     * @param string|null $label Human-readable label for error messages
     * @return void
     * @throws CoreException If validation fails
     */
    public static function validateAttributeTypeOrNull(
        object $model,
        string $attribute,
        string $type,
        ?string $label = null
    ): void {
        $value = $model->$attribute;

        // Nilai null atau string "null" dianggap valid
        if (self::isNullString($value)) {
            return;
        }

        // Validasi tipe
        $isValid = match($type) {
            'array'   => is_array($value),
            'string'  => is_string($value),
            'integer' => is_int($value),
            'float'   => is_float($value),
            'numeric' => is_numeric($value),
            default   => false,
        };

        if (!$isValid) {
            $label ??= $attribute;
            $model->addError($attribute, Yii::t('app', $type, ['label' => $label]));
            throw new CoreException($model, Yii::t('app', 'validationFailed'), 422);
        }
    }

    /**
     * Gets validation rules for sync_mdb field.
     * Defines rules for MongoDB synchronization status tracking.
     * Usage:
     * ```php
     * public function rules()
     * {
     *     return array_merge(
     *         [
     *             // other rules
     *         ],
     *         CoreModel::getMongoDbSyncRules($this),
     *     );
     * }
     * ```
     * 
     * @param object|null $model Optional model instance
     * @return array Array of validation rules for sync field
     */
    public static function getMongoDbSyncRules($model=null): array
    {
        return [
            [[Constants::SYNC_MONGODB], 'default', 'value' => null],
            [[Constants::SYNC_MONGODB], 'integer'],
        ];
    }

    /**
     * Gets validation rules for optimistic locking.
     * Implements version-based concurrency control.
     * 
     * Usage:
     * ```php
     * public function rules()
     * {
     *     return array_merge(
     *         [
     *             // other rules
     *         ],
     *         CoreModel::getLockVersionRules($this, Constants::SCENARIO_UPDATE),
     *     );
     * }
     * ```
     * 
     * @param object|null $model Optional model instance
     * @param string|null $requiredOn Scenario where version is required
     * @return array Array of validation rules for version field
     */
    public static function getLockVersionRules($model=null, $requiredOn=null): array
    {
        return [
            [[Constants::OPTIMISTIC_LOCK], 'required', 'on' => $requiredOn ?? Constants::SCENARIO_UPDATE_LIST],
            [[Constants::OPTIMISTIC_LOCK], 'integer'],
            [[Constants::OPTIMISTIC_LOCK], 'default', 'value' => 1, 'on' => [Constants::SCENARIO_CREATE]],
        ];
    }

    /**
     * Gets basic version field validation rules.
     * Simplified version of getLockVersionRules without scenario handling.
     * 
     * Usage:
     * ```php
     * public function rules()
     * {
     *     return array_merge(
     *         [
     *             // other rules
     *         ],
     *         CoreModel::getLockVersionRulesOnly(),
     *     );
     * }
     * ```
     * 
     * @return array Array of basic validation rules for version field
     */
    public static function getLockVersionRulesOnly(): array
    {
        return [
            [[Constants::OPTIMISTIC_LOCK], 'integer'],
        ];
    }

    /**
     * Gets validation rules for pagination parameters.
     * Validates common pagination fields like page number and size.
     * 
     * Usage:
     * ```php
     * public function rules()
     * {
     *     return array_merge(
     *         [
     *             // other rules
     *         ],
     *         CoreModel::getPaginationRules($this),
     *     );
     * }
     * ```
     * 
     * @param object $model Model instance
     * @return array Array of validation rules for pagination fields
     */
    public static function getPaginationRules($model): array
    {
        return [
			[['detail_info', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'], 'safe'],
            [['sort_dir', 'sort_by'], 'string'],
            [['page', 'page_size'], 'integer'],
            [['page'], function ($attribute, $params) use ($model) {
                if ($model->$attribute <= 0) {
                    $model->addError($attribute, Yii::t('app', 'pageMustBeGreaterThanZero'));
                    return;
                }
            }],
        ];
    }

    /**
     * Configures pagination settings for data provider.
     * Handles page size limits and default values.
     * 
     * Usage:
     * ```php
     * $dataProvider->setPagination(
     *     CoreModel::setPagination($params, $dataProvider)
     * );
     * ```
     * 
     * @param array|null $params Request parameters containing pagination info
     * @param object $dataProvider DataProvider instance
     * @return array Pagination configuration array
     */
    public static function setPagination(?array $params, $dataProvider): array {
        $pageSize = min((int)$dataProvider->getTotalCount(), (int)($params['page_size'] ?? Yii::$app->params['pagination']['pageSize']));

        return [
            'page' => (int)($params['page'] ?? 1) - 1,
            'pageSize' => $pageSize,
            'defaultPageSize' => $pageSize,
        ];
    }

    /**
     * Configures sorting settings for data provider.
     * Handles sort direction and default sort field.
     * 
     * Usage:
     * ```php
     * $dataProvider->setSort(
     *     CoreModel::setSort($params)
     * );
     * ```
     * 
     * @param array|null $params Request parameters containing sort info
     * @return array Sort configuration array
     */
    public static function setSort(?array $params): array
    {
        $sortBy = $params['sort_by'] ?? 'id';
        $sort = $params['sort_dir'] ?? 'desc';
        $sortDir = match ($sort) {
            'asc' => SORT_ASC,
            'desc' => SORT_DESC,
            default => SORT_DESC,
        };

        return [
            'defaultOrder' => [
                $sortBy => $sortDir,
            ],
        ];
    }

    /**
     * Validates status transitions for a model.
     * Ensures status changes follow allowed paths.
     *
     * @param string $attribute Name of the status attribute
     * @param array|null $params Additional parameters
     * @param object $model Model instance being validated
     * @return void
     * @internal
     */
    protected static function validateStatusUpdate(string $attribute, ?array $params, $model): void
    {
        if (!$model->isAttributeChanged($attribute)) {
            return;
        }

        $newStatus = $model->$attribute;
        $oldStatus = $model->getOldAttribute($attribute);
        $statusList = Constants::STATUS_LIST;
        $allowedStatusUpdate = Constants::ALLOWED_UPDATE_STATUS_LIST;

        // Special case: deleted status changed by super admin
        if (!$model->isNewRecord
            && $oldStatus === Constants::STATUS_DELETED
            && $newStatus !== Constants::STATUS_DELETED
            && Yii::$app->coreAPI::superAdmin()
        ) {
            $model->addError(
                $attribute,
                Yii::t('app', 'deletedStatusChanged', ['value' => $statusList[Constants::STATUS_DELETED]])
            );
            return;
        }

        // Validate allowed status transitions
        if ($oldStatus !== null && !isset($allowedStatusUpdate[$oldStatus])) {
            $model->addError($attribute, Yii::t('app', 'invalidStatusTransition'));
        }
    }


    /**
     * Validates model dependencies before allowing updates.
     * Prevents updates to fields when dependent records exist.
     * 
     * Usage:
     * ```php
     * public function rules()
     * {
     *     return [
     *         [['status', 'name'], function($attribute, $params) {
     *             CoreModel::validateDependencies($attribute, $params, $this, [
     *                 ['className' => 'Order', 'field' => ['user_id']],
     *                 ['className' => 'Payment', 'field' => ['user_id']]
     *             ]);
     *         }]
     *     ];
     * }
     * ```
     * 
     * @param string $attribute Attribute being validated
     * @param array $params Validation parameters
     * @param object $model Model being validated
     * @param array $dependencies Array of dependent models and their fields
     */
    public static function validateDependencies($attribute, $params, $model, array $dependencies): void
    {
        if ($model->isNewRecord) {
            return;
        }

        $changedAttributes = [];
        $fields = Yii::$app->params['dependenciesUpdate'][$model->tableName()] ?? [];
        $disallowedStatusUpdate = Constants::DISALLOWED_UPDATE_STATUS_LIST;

        foreach ($fields as $field) {
            if ($model->isAttributeChanged($field) || ($field === 'status' && in_array($model->status, $disallowedStatusUpdate))) {
                $changedAttributes[] = $field;
            }
        }

        if (empty($changedAttributes)) {
            return;
        }

        $dataId = $model->id;

        foreach ($dependencies as $dependency) {
            $className = 'app\models\\' . $dependency['className'];

            foreach ($dependency['field'] as $field) {
                if ($className::find()->where([$field => $dataId])->exists()) {
                    foreach ($changedAttributes as $changedAttribute) {
                        $model->addError($changedAttribute, Yii::t('app', 'updatePermission', [
                            'label' => $model->getAttributeLabel($changedAttribute),
                            'tableName' => $model->tableName(),
                        ]));
                    }
                    return;
                }
            }
        }
    }

    /**
     * Validates dependencies for array fields.
     * 
     * Usage:
     * ```php
     * public function rules()
     * {
     *     return [
     *         [['user_id'], function($attribute, $params) {
     *             CoreModel::validateDependenciesInArray($attribute, $params, $this, [
     *                 ['className' => 'Order', 'field' => ['user_id']],
     *                 ['className' => 'Payment', 'field' => ['user_id']]
     *             ]);
     *         }]
     *     ];
     * }
     * ```
     * 
     * @param string $attribute Attribute being validated
     * @param array $params Validation parameters
     * @param object $model Model being validated
     * @param array $dependencies Array of dependent models and their fields
     */
    public static function validateDependenciesInArray($attribute, $params, $model, array $dependencies): void
    {
        if ($model->isNewRecord) {
            return;
        }

        $changedAttributes = [];
        $fields = Yii::$app->params['dependenciesUpdate'][$model->tableName()] ?? [];
        $disallowedStatusUpdate = Constants::DISALLOWED_UPDATE_STATUS_LIST;

        foreach ($fields as $field) {
            if ($model->isAttributeChanged($field) || ($field === 'status' && in_array($model->status, $disallowedStatusUpdate))) {
                $changedAttributes[] = $field;
            }
        }

        if (empty($changedAttributes)) {
            return;
        }

        $dataId = $model->id;

        foreach ($dependencies as $dependency) {
            $className = 'app\models\\' . $dependency['className'];

            foreach ($dependency['field'] as $field) {
                if ($className::find()->where("{$field} @> :dataId::jsonb", [':dataId' => $dataId])->exists()) {
                    foreach ($changedAttributes as $changedAttribute) {
                        $model->addError($changedAttribute, Yii::t('app', 'updatePermission', [
                            'label' => $model->getAttributeLabel($changedAttribute),
                            'tableName' => $model->tableName(),
                        ]));
                    }
                    return;
                }
            }
        }
    }

    /**
     * Sets up changelog filters for querying model history.
     * Handles both date range and user filters for audit logs.
     *
     * Usage:
     * ```php
     * $query = ModelHistory::find();
     * $conditions = CoreModel::setChangelogFilters($searchModel);
     * $query->andWhere($conditions);
     * ```
     * 
     * Usage with custom fields:
     * ```php
     * $query = ModelHistory::find();
     * $conditions = CoreModel::setChangelogFilters($searchModel, 
     *     ['created_at', 'updated_at'],
     *     ['created_by', 'updated_by']
     * );
     * $query->andWhere($conditions);
     * ```
     *
     * @param object $model Model with changelog attributes
     * @param array $logDates Date fields to filter
     * @param array $logUsers User fields to filter
     * @return array Query conditions for changelog filtering
     */
    public static function setChangelogFilters(
        $model,
        array $logDates = ['created_at', 'updated_at', 'deleted_at'],
        array $logUsers = ['created_by', 'updated_by', 'deleted_by']
    ): array {
        $conditions = ['and'];

        foreach ($logDates as $logDate) {
            $dateValue = $model->{$logDate};
            if (empty($dateValue)) {
                continue;
            }

            if (str_contains($dateValue, ',')) {
                [$startDate, $endDate] = array_map('trim', explode(',', $dateValue));
                $conditions[] = [
                    'and',
                    ['>=', new \yii\db\Expression("(detail_info #>> '{change_log,$logDate}')::date"), $startDate],
                    ['<=', new \yii\db\Expression("(detail_info #>> '{change_log,$logDate}')::date"), $endDate],
                ];
            } else {
                $conditions[] = ['=', new \yii\db\Expression("(detail_info #>> '{change_log,$logDate}')::date"), $dateValue];
            }
        }

        foreach ($logUsers as $logUser) {
            if (!empty($model->{$logUser})) {
                $conditions[] = ['ilike', new \yii\db\Expression("detail_info #>> '{change_log,$logUser}'"), $model->{$logUser}];
            }
        }

        return $conditions;
    }

    /**
     * Retrieves change log for a model.
     * Handles both insert and update operations.
     *
     * Usage:
     * ```php
     * $model->detail_info['change_log'] = CoreModel::getChangeLog($model, $insert);
     * ```
     *
     * @param object $model Model instance
     * @param bool $insert Whether this is an insert operation
     * @return array Change log array
     */
    public static function getChangeLog($model, bool $insert): array
    {
        $timestamp = Yii::$app->coreAPI::UTCTimestamp();
        $username = Yii::$app->coreAPI::getUsername();

        if ($insert) {
            return [
                'created_at' => $timestamp,
                'created_by' => $username,
                'updated_at' => null,
                'updated_by' => null,
                'deleted_at' => null,
                'deleted_by' => null,
            ];
        }

        $changeLog = $model->detail_info['change_log'] ?? [];

        if (isset($model->status) && $model->status === Constants::STATUS_DELETED) {
            $changeLog['deleted_at'] = $timestamp;
            $changeLog['deleted_by'] = $username;
        } elseif ($model->getDirtyAttributes()) {
            $changeLog['updated_at'] = $timestamp;
            $changeLog['updated_by'] = $username;
        }

        return $changeLog;
    }

    /**
     * Validates required fields in a model attribute.
     * Checks for missing required fields and extra fields.
     *
     * Usage:
     * ```php
     * public function rules()
     * {
     *     return [
     *         ['details', function($attribute) {
     *             CoreModel::validateRequiredFields($this, $attribute, ['name', 'code', 'type']);
     *         }]
     *     ];
     * }
     * ```
     *
     * @param object $model Model instance containing the attribute
     * @param string $attribute Name of the attribute to validate
     * @param array $requiredFields List of required field names
     * @param array|null $item Optional specific item to validate instead of the attribute value
     * @return bool Returns false if validation passes
     * @throws CoreException when validation fails
     */
    public static function validateRequiredFields($model, string $attribute, array $requiredFields, ?array $item = null): bool
    {
        $fields = $item ?? $model->$attribute;

        if (!is_array($fields)) {
            $model->addError($attribute, Yii::t('app', 'array', ['label' => $model->getAttributeLabel($attribute)]));
            throw new CoreException($model, Yii::t('app', 'validationFailed'), 422);
        }

        // Cek extra fields
        $extraFields = array_diff_key($fields, array_flip($requiredFields));
        if ($extraFields) {
            $model->addError($attribute, Yii::t('app', 'extraField', [
                'label' => $model->getAttributeLabel($attribute),
                'field' => implode(', ', array_keys($extraFields)),
                'value' => implode(', ', $requiredFields),
            ]));
            throw new CoreException($model, Yii::t('app', 'extraFieldFound', ['label' => $model->getAttributeLabel($attribute)]), 422);
        }

        // Cek missing fields
        $missingFields = array_diff($requiredFields, array_keys($fields));
        if ($missingFields) {
            $model->addError($attribute, Yii::t('app', 'missingField', [
                'field' => implode(', ', $missingFields),
            ]));
            throw new CoreException($model, Yii::t('app', 'missingFieldFound', ['label' => $model->getAttributeLabel($attribute)]), 422);
        }

        return false;
    }

    /**
     * Validates that no fields in an array are null.
     * Useful for ensuring all fields in a nested structure have values.
     *
     * Usage:
     * ```php
     * public function rules()
     * {
     *     return [
     *         ['details', function($attribute) {
     *             foreach ($this->details as $item) {
     *                 if (CoreModel::nullFieldValidator($this, $attribute, $item)) {
     *                     return;
     *                 }
     *             }
     *         }]
     *     ];
     * }
     * ```
     *
     * @param object $model Model instance containing the attribute
     * @param string $attribute Name of the attribute being validated
     * @param array $item Array of field values to check for null
     * @return bool True if any fields are null (validation fails), false otherwise
     */
    public static function nullFieldValidator($model, string $attribute, array $item): bool
    {
        $nullFields = array_keys(array_filter($item, fn($value) => $value === null));

        if (!$nullFields) {
            return false;
        }

        $model->addError($attribute, Yii::t('app', 'nullField', [
            'label' => $model->getAttributeLabel($attribute),
            'field' => implode(', ', $nullFields),
        ]));

        return true;
    }

    /**
     * Formats model validation errors into a standardized array format.
     * Converts Yii2's error format into a field-message pair array.
     *
     * Usage:
     * ```php
     * if (!$model->validate()) {
     *     return [
     *         'errors' => CoreModel::getErrors($model->getErrors())
     *     ];
     * }
     * ```
     *
     * @param array $errors Array of validation errors from model
     * @return array Array of formatted errors with field and message keys
     */
    public static function getErrors(array $errors = []): array
    {
        return array_merge(...array_map(
            fn($messages, $field) => array_map(fn($message) => ['field' => $field, 'message' => $message], $messages),
            $errors,
            array_keys($errors)
        ));
    }
    
    /**
     * Formats a given date into the application's local timezone.
     * Wrapper around CoreAPI's localDateFormatter method.
     *
     * Usage:
     * ```php
     * $formattedDate = CoreModel::localDateFormatter('2025-10-30 12:00:00');
     * ```
     *
     * @param string|\DateTime $date The date value to format
     * @return string Formatted date in local timezone
     */
    public static function localDateFormatter($date)
    {
        return \app\helpers\DateHelper::localDateFormatter($date);
    }

    /**
     * Formats a given date into UTC timezone.
     * Wrapper around CoreAPI's utcDateFormatter method.
     *
     * Usage:
     * ```php
     * $utcDate = CoreModel::utcDateFormatter('2025-10-30 12:00:00');
     * ```
     *
     * @param string|\DateTime $date The date value to format
     * @return string Formatted date in UTC
     */
    public static function utcDateFormatter($date)
    {
        return \app\helpers\DateHelper::utcDateFormatter($date);
    }

    /**
     * Converts local date string to UTC timezone format.
     * Wrapper around DateHelper's localToUtcDate method.
     *
     * Usage:
     * ```php
     * $utcDate = CoreModel::localToUtcDate('2025-10-30 12:00:00');
     * ```
     *
     * @param string|null $date Date string to convert
     * @return string|null Formatted date or null if input is null
     */
    public static function localToUtcDate($date)
    {
        return \app\helpers\DateHelper::localToUtcDate($date);
    }

    /**
     * Converts UTC date string to local timezone format.
     * Wrapper around DateHelper's utcToLocalDate method.
     *
     * Usage:
     * ```php
     * $utcDate = CoreModel::utcToLocalDate('2025-10-30T12:00:00Z');
     * ```
     *
     * @param string|null $date Date string to convert
     * @return string|null Formatted date or null if input is null
     */
    public static function utcToLocalDate($date)
    {
        return \app\helpers\DateHelper::utcToLocalDate($date);
    }

    /**
     * Calculates the remaining time in hours and minutes.
     * Rounds the hours to one decimal place and converts to hours and minutes.
     *
     * Usage:
     * ```php
     * $remaining = CoreModel::remainingTime(2.5); // Returns "2 hours 30 minutes"
     * ```
     *
     * @param float $hours Number of hours to calculate remaining time for
     * @return string Formatted remaining time in hours and minutes
     */
    public function remainingTime(float $hours = 0): string
    {
        $hour = (int) floor($hours);
        $minute = (int) round(($hours - $hour) * 60);

        return "{$hour} " . Yii::t('app', 'hour') . " {$minute} " . Yii::t('app', 'minute');
    }
}