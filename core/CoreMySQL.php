<?php

namespace app\core;

use yii\db\ActiveQuery;
use yii\db\ActiveQueryInterface;
use app\helpers\Constants;

/**
 * CoreMySQL provides a base ActiveQuery class for MySQL database queries.
 *
 * This class extends Yii's ActiveQuery and provides:
 * - Status filtering methods (active, inactive, draft, deleted, etc.)
 * - Sorting methods by name and sort order
 * - ID and name based filtering
 * - Created/updated date range filters
 * - User-based filtering
 * - JSON field filtering compatible with MySQL (using JSON_EXTRACT and JSON_CONTAINS)
 * - detail_info->change_log filters for auditing
 *
 * Note: MySQL JSON filtering uses JSON_EXTRACT and JSON_CONTAINS functions.
 *
 * @package app\core
 * @version 1.0.0
 * @since 2025-11-04
 */
class CoreMySQL extends ActiveQuery implements ActiveQueryInterface
{
    /**
     * @var string Field name for status filtering
     */
    public $fieldStatus;

    /**
     * @var string Field name for sorting
     */
    public $fieldSortOrder;

    /**
     * @var string Field name for ID
     */
    public $fieldId;

    /**
     * @var string Field name for name
     */
    public $fieldName;

    /**
     * Constructor initializes default field names.
     *
     * @param string $modelClass The model class for this query
     * @param array $config Optional configuration array
     */
    public function __construct($modelClass, $config = [])
    {
        parent::__construct($modelClass, $config);
        $this->fieldStatus = 'status';
        $this->fieldSortOrder = 'sort_order';
        $this->fieldId = 'id';
        $this->fieldName = 'name';
    }

    // ======================================================
    // Record Retrieval
    // ======================================================

    /**
     * Retrieve all records matching the query.
     *
     * @param \yii\db\Connection|null $db Database connection
     * @return array List of models
     * @example
     * ```php
     * $users = User::find()->active()->all();
     * ```
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * Retrieve a single record matching the query.
     *
     * @param \yii\db\Connection|null $db Database connection
     * @return array|object|null Single model or null if not found
     * @example
     * ```php
     * $user = User::find()->findById(1)->one();
     * ```
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    // ======================================================
    // Status Filters
    // ======================================================

    /**
     * Filter records by a specific status.
     *
     * @param int $status Status value to filter
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->byStatus(Constants::STATUS_ACTIVE)->all();
     * ```
     */
    public function byStatus($status)
    {
        return $this->andWhere([$this->fieldStatus => $status]);
    }

    /**
     * Filter records with inactive status.
     *
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->inactive()->all();
     * ```
     */
    public function inactive()
    {
        return $this->andWhere([$this->fieldStatus => Constants::STATUS_INACTIVE]);
    }

    /**
     * Filter records with active status.
     *
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->active()->all();
     * ```
     */
    public function active()
    {
        return $this->andWhere([$this->fieldStatus => Constants::STATUS_ACTIVE]);
    }

    /**
     * Filter records with draft status.
     *
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->draft()->all();
     * ```
     */
    public function draft()
    {
        return $this->andWhere([$this->fieldStatus => Constants::STATUS_DRAFT]);
    }

    /**
     * Filter records with completed status.
     *
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->completed()->all();
     * ```
     */
    public function completed()
    {
        return $this->andWhere([$this->fieldStatus => Constants::STATUS_COMPLETED]);
    }

    /**
     * Filter records with deleted status.
     *
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->deleted()->all();
     * ```
     */
    public function deleted()
    {
        return $this->andWhere([$this->fieldStatus => Constants::STATUS_DELETED]);
    }

    /**
     * Filter records with maintenance status.
     *
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->maintenance()->all();
     * ```
     */
    public function maintenance()
    {
        return $this->andWhere([$this->fieldStatus => Constants::STATUS_MAINTENANCE]);
    }

    /**
     * Filter records with approved status.
     *
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->approved()->all();
     * ```
     */
    public function approved()
    {
        return $this->andWhere([$this->fieldStatus => Constants::STATUS_APPROVED]);
    }

    /**
     * Filter records with rejected status.
     *
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->rejected()->all();
     * ```
     */
    public function rejected()
    {
        return $this->andWhere([$this->fieldStatus => Constants::STATUS_REJECTED]);
    }

    // ======================================================
    // Sorting
    // ======================================================

    /**
     * Order records by sort_order field.
     *
     * @param int $direction SORT_ASC or SORT_DESC
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->orderBySortOrder(SORT_DESC)->all();
     * ```
     */
    public function orderBySortOrder($direction = SORT_ASC)
    {
        return $this->orderBy([$this->fieldSortOrder => $direction]);
    }

    /**
     * Order records by name field.
     *
     * @param int $direction SORT_ASC or SORT_DESC
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->orderByName(SORT_ASC)->all();
     * ```
     */
    public function orderByName($direction = SORT_ASC)
    {
        return $this->orderBy([$this->fieldName => $direction]);
    }

    // ======================================================
    // ID and Name Filters
    // ======================================================

    /**
     * Filter records by a single ID.
     *
     * @param int $id Record ID
     * @return CoreMySQL
     * @example
     * ```php
     * $record = User::find()->findById(5)->one();
     * ```
     */
    public function findById($id)
    {
        return $this->andWhere([$this->fieldId => $id]);
    }

    /**
     * Filter records by multiple IDs.
     *
     * @param array $ids Array of IDs
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->findByIds([1, 2, 3])->all();
     * ```
     */
    public function findByIds($ids)
    {
        return $this->andWhere(['in', $this->fieldId, $ids]);
    }

    /**
     * Filter records by exact name.
     *
     * @param string $name Record name
     * @return CoreMySQL
     * @example
     * ```php
     * $record = User::find()->findByName('John Doe')->one();
     * ```
     */
    public function findByName($name)
    {
        return $this->andWhere([$this->fieldName => $name]);
    }

    /**
     * Filter records by partial name (LIKE query).
     *
     * @param string $name Record name
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->likeByName('John')->all();
     * ```
     */
    public function likeByName($name)
    {
        return $this->andWhere(['like', $this->fieldName, $name]);
    }

    // ======================================================
    // Date Range Filters
    // ======================================================

    /**
     * Filter records by created date range.
     *
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->byCreatedDateRange('2025-01-01', '2025-12-31')->all();
     * ```
     */
    public function byCreatedDateRange($startDate, $endDate)
    {
        return $this->andWhere(['>=', 'created_at', $startDate])
                    ->andWhere(['<=', 'created_at', $endDate]);
    }

    /**
     * Filter records by updated date range.
     *
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->byUpdatedDateRange('2025-01-01', '2025-12-31')->all();
     * ```
     */
    public function byUpdatedDateRange($startDate, $endDate)
    {
        return $this->andWhere(['>=', 'updated_at', $startDate])
                    ->andWhere(['<=', 'updated_at', $endDate]);
    }

    // ======================================================
    // User Filters
    // ======================================================

    /**
     * Filter records by creator user ID.
     *
     * @param int $userId
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->byCreatedBy(5)->all();
     * ```
     */
    public function byCreatedBy($userId)
    {
        return $this->andWhere(['created_by' => $userId]);
    }

    /**
     * Filter records by updater user ID.
     *
     * @param int $userId
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->byUpdatedBy(5)->all();
     * ```
     */
    public function byUpdatedBy($userId)
    {
        return $this->andWhere(['updated_by' => $userId]);
    }

    // ======================================================
    // JSON Filters (MySQL)
    // ======================================================
    /**
     * Filter by JSON value (exact match) using JSON_EXTRACT.
     *
     * @param string $field JSON field name
     * @param string $key Nested key (dot notation supported, e.g. "info.name")
     * @param mixed $value Value to match
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->byJsonValue('meta', 'profile.age', 25)->all();
     * ```
     */
    public function byJsonValue($field, $key, $value)
    {
        $jsonPath = '$.' . str_replace('.', '.', $key);
        $paramName = ':jsonValue' . uniqid();
        return $this->andWhere("JSON_UNQUOTE(JSON_EXTRACT($field, '$jsonPath')) = $paramName", [$paramName => $value]);
    }

    /**
     * Filter by JSON field value with comparison operator.
     *
     * @param string $field JSON field name
     * @param string $key Nested key (dot notation)
     * @param string $operator Comparison operator (>, <, >=, <=, !=)
     * @param mixed $value Value to compare
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->byJsonRangeValue('meta', 'profile.age', '>=', 18)->all();
     * ```
     */
    public function byJsonRangeValue($field, $key, $operator, $value)
    {
        $jsonPath = '$.' . str_replace('.', '.', $key);
        $paramName = ':jsonValue' . uniqid();
        return $this->andWhere("JSON_UNQUOTE(JSON_EXTRACT($field, '$jsonPath')) $operator $paramName", [$paramName => $value]);
    }

    /**
     * Filter by JSON field IS NULL or IS NOT NULL.
     *
     * @param string $field JSON field
     * @param string $key Nested key
     * @param bool $isNull TRUE for IS NULL, FALSE for IS NOT NULL
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->byJsonNullCheck('meta', 'profile.phone', true)->all();
     * ```
     */
    public function byJsonNullCheck($field, $key, $isNull = true)
    {
        $jsonPath = '$.' . str_replace('.', '.', $key);
        $nullCheck = $isNull ? 'IS NULL' : 'IS NOT NULL';
        return $this->andWhere("JSON_EXTRACT($field, '$jsonPath') $nullCheck");
    }

    /**
     * Filter records where JSON contains a specific value (MySQL JSON_CONTAINS).
     *
     * @param string $field JSON field
     * @param array $jsonData JSON data to match
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->byJsonContains('meta', ['role' => 'admin'])->all();
     * ```
     */
    public function byJsonContains($field, $jsonData)
    {
        $paramName = ':jsonContains' . uniqid();
        $jsonValue = json_encode($jsonData);
        return $this->andWhere("JSON_CONTAINS($field, $paramName)", [$paramName => $jsonValue]);
    }

    // ======================================================
    // detail_info -> change_log Filters
    // ======================================================

    /**
     * Filter by created user in detail_info->change_log.
     *
     * @param int $userId Creator user ID
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->byDetailCreatedBy(1)->all();
     * ```
     */
    public function byDetailCreatedBy($userId)
    {
        return $this->byJsonValue('detail_info', 'change_log.created_by', $userId);
    }

    /**
     * Filter by updated user in detail_info->change_log.
     *
     * @param int $userId Updater user ID
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->byDetailUpdatedBy(2)->all();
     * ```
     */
    public function byDetailUpdatedBy($userId)
    {
        return $this->byJsonValue('detail_info', 'change_log.updated_by', $userId);
    }

    /**
     * Filter by deleted user in detail_info->change_log.
     *
     * @param int $userId Deleted user ID
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->byDetailDeletedBy(3)->all();
     * ```
     */
    public function byDetailDeletedBy($userId)
    {
        return $this->byJsonValue('detail_info', 'change_log.deleted_by', $userId);
    }

    /**
     * Filter records by created date range in detail_info->change_log.
     *
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->byDetailCreatedDateRange('2025-01-01', '2025-12-31')->all();
     * ```
     */
    public function byDetailCreatedDateRange($startDate, $endDate)
    {
        return $this->byJsonRangeValue('detail_info', 'change_log.created_at', '>=', $startDate)
                    ->byJsonRangeValue('detail_info', 'change_log.created_at', '<=', $endDate);
    }

    /**
     * Filter records by updated date range in detail_info->change_log.
     *
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->byDetailUpdatedDateRange('2025-01-01', '2025-12-31')->all();
     * ```
     */
    public function byDetailUpdatedDateRange($startDate, $endDate)
    {
        return $this->byJsonRangeValue('detail_info', 'change_log.updated_at', '>=', $startDate)
                    ->byJsonRangeValue('detail_info', 'change_log.updated_at', '<=', $endDate);
    }

    /**
     * Filter records by deleted date range in detail_info->change_log.
     *
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->byDetailDeletedDateRange('2025-01-01', '2025-12-31')->all();
     * ```
     */
    public function byDetailDeletedDateRange($startDate, $endDate)
    {
        return $this->byJsonRangeValue('detail_info', 'change_log.deleted_at', '>=', $startDate)
                    ->byJsonRangeValue('detail_info', 'change_log.deleted_at', '<=', $endDate);
    }

    /**
     * Filter records that have been deleted according to detail_info->change_log.
     *
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->detailDeleted()->all();
     * ```
     */
    public function detailDeleted()
    {
        return $this->byJsonNullCheck('detail_info', 'change_log.deleted_at', false);
    }

    /**
     * Filter records that have not been deleted according to detail_info->change_log.
     *
     * @return CoreMySQL
     * @example
     * ```php
     * $records = User::find()->detailNotDeleted()->all();
     * ```
     */
    public function detailNotDeleted()
    {
        return $this->byJsonNullCheck('detail_info', 'change_log.deleted_at', true);
    }
}