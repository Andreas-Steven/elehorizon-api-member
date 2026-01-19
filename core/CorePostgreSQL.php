<?php

namespace app\core;

use yii\db\ActiveQuery;
use yii\db\ActiveQueryInterface;
use app\helpers\Constants;

/**
 * CorePostgreSQL provides a base ActiveQuery class for PostgreSQL database queries.
 * It extends Yii's ActiveQuery with common query methods optimized for PostgreSQL,
 * including status filtering, sorting, ID/name filters, date range filters,
 * JSON field queries, and detail_info change log filters.
 * 
 * @package app\core
 * @version 1.0.0
 * @since 2025-11-04
 */
class CorePostgreSQL extends ActiveQuery implements ActiveQueryInterface
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
     * CorePostgreSQL constructor.
     * Initializes default field names.
     *
     * @param string $modelClass The model class associated with this query
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

    /**
     * Retrieves all records from the query.
     *
     * @param \yii\db\Connection|null $db
     * @return array All records
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * Retrieves a single record from the query.
     *
     * @param \yii\db\Connection|null $db
     * @return array|object|null Single record or null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    // ============================
    // Status filters
    // ============================

    /**
     * Filter by arbitrary status value.
     *
     * @param int $status
     * @return CorePostgreSQL
     */
    public function byStatus($status)
    {
        return $this->andWhere([$this->fieldStatus => $status]);
    }

    /**
     * Filter by inactive status.
     *
     * @return CorePostgreSQL
     */
    public function inactive()
    {
        return $this->andWhere([$this->fieldStatus => Constants::STATUS_INACTIVE]);
    }

    /**
     * Filter by active status.
     *
     * @return CorePostgreSQL
     */
    public function active()
    {
        return $this->andWhere([$this->fieldStatus => Constants::STATUS_ACTIVE]);
    }

    /**
     * Filter by draft status.
     *
     * @return CorePostgreSQL
     */
    public function draft()
    {
        return $this->andWhere([$this->fieldStatus => Constants::STATUS_DRAFT]);
    }

    /**
     * Filter by completed status.
     *
     * @return CorePostgreSQL
     */
    public function completed()
    {
        return $this->andWhere([$this->fieldStatus => Constants::STATUS_COMPLETED]);
    }

    /**
     * Filter by deleted status.
     *
     * @return CorePostgreSQL
     */
    public function deleted()
    {
        return $this->andWhere([$this->fieldStatus => Constants::STATUS_DELETED]);
    }

    /**
     * Filter by maintenance status.
     *
     * @return CorePostgreSQL
     */
    public function maintenance()
    {
        return $this->andWhere([$this->fieldStatus => Constants::STATUS_MAINTENANCE]);
    }

    /**
     * Filter by approved status.
     *
     * @return CorePostgreSQL
     */
    public function approved()
    {
        return $this->andWhere([$this->fieldStatus => Constants::STATUS_APPROVED]);
    }

    /**
     * Filter by rejected status.
     *
     * @return CorePostgreSQL
     */
    public function rejected()
    {
        return $this->andWhere([$this->fieldStatus => Constants::STATUS_REJECTED]);
    }

    // ============================
    // Sorting methods
    // ============================

    /**
     * Order results by sort_order field.
     *
     * @param int $direction SORT_ASC or SORT_DESC
     * @return CorePostgreSQL
     */
    public function orderBySortOrder($direction = SORT_ASC)
    {
        return $this->orderBy([$this->fieldSortOrder => $direction]);
    }

    /**
     * Order results by name field.
     *
     * @param int $direction SORT_ASC or SORT_DESC
     * @return CorePostgreSQL
     */
    public function orderByName($direction = SORT_ASC)
    {
        return $this->orderBy([$this->fieldName => $direction]);
    }

    // ============================
    // ID and name filters
    // ============================

    /**
     * Filter by single ID.
     *
     * @param int $id
     * @return CorePostgreSQL
     */
    public function findById($id)
    {
        return $this->andWhere([$this->fieldId => $id]);
    }

    /**
     * Filter by multiple IDs.
     *
     * @param array $ids
     * @return CorePostgreSQL
     */
    public function findByIds($ids)
    {
        return $this->andWhere(['in', $this->fieldId, $ids]);
    }

    /**
     * Filter by exact name match.
     *
     * @param string $name
     * @return CorePostgreSQL
     */
    public function findByName($name)
    {
        return $this->andWhere([$this->fieldName => $name]);
    }

    /**
     * Filter by partial name match using LIKE.
     *
     * @param string $name
     * @return CorePostgreSQL
     */
    public function likeByName($name)
    {
        return $this->andWhere(['like', $this->fieldName, $name]);
    }

    // ============================
    // Date range filters
    // ============================

    /**
     * Filter records by created_at date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return CorePostgreSQL
     */
    public function byCreatedDateRange($startDate, $endDate)
    {
        return $this->andWhere(['>=', 'created_at', $startDate])
                    ->andWhere(['<=', 'created_at', $endDate]);
    }

    /**
     * Filter records by updated_at date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return CorePostgreSQL
     */
    public function byUpdatedDateRange($startDate, $endDate)
    {
        return $this->andWhere(['>=', 'updated_at', $startDate])
                    ->andWhere(['<=', 'updated_at', $endDate]);
    }

    // ============================
    // User filters
    // ============================

    /**
     * Filter by created_by user ID.
     *
     * @param int $userId
     * @return CorePostgreSQL
     */
    public function byCreatedBy($userId)
    {
        return $this->andWhere(['created_by' => $userId]);
    }

    /**
     * Filter by updated_by user ID.
     *
     * @param int $userId
     * @return CorePostgreSQL
     */
    public function byUpdatedBy($userId)
    {
        return $this->andWhere(['updated_by' => $userId]);
    }

    // ============================
    // JSON field filters
    // ============================

    /**
     * Filter by JSON value (PostgreSQL ->> operator).
     *
     * @param string $field JSON field
     * @param string $key Nested key (dot notation supported)
     * @param mixed $value Value to match
     * @return CorePostgreSQL
     */
    public function byJsonValue($field, $key, $value)
    {
        $paramName = ':jsonValue' . uniqid();
        $keys = explode('.', $key);
        $jsonPath = $field;
        
        foreach ($keys as $k) {
            $jsonPath .= "->'{$k}'";
        }
        $jsonPath = str_replace("->'{$keys[count($keys)-1]}'", "->>''{$keys[count($keys)-1]}''", $jsonPath);
        return $this->andWhere("{$jsonPath} = {$paramName}", [$paramName => $value]);
    }

    /**
     * Filter by JSON value with range comparison.
     *
     * @param string $field JSON field
     * @param string $key Nested key (dot notation supported)
     * @param string $operator Comparison operator
     * @param mixed $value Value to compare
     * @return CorePostgreSQL
     */
    public function byJsonRangeValue($field, $key, $operator, $value)
    {
        $paramName = ':jsonValue' . uniqid();
        $keys = explode('.', $key);
        $jsonPath = $field;
        
        foreach ($keys as $k) {
            $jsonPath .= "->'{$k}'";
        }
        $jsonPath = str_replace("->'{$keys[count($keys)-1]}'", "->>''{$keys[count($keys)-1]}''", $jsonPath);
        return $this->andWhere("{$jsonPath} {$operator} {$paramName}", [$paramName => $value]);
    }

    /**
     * Filter by JSON NULL or NOT NULL check.
     *
     * @param string $field JSON field
     * @param string $key Nested key
     * @param bool $isNull TRUE for IS NULL, FALSE for IS NOT NULL
     * @return CorePostgreSQL
     */
    public function byJsonNullCheck($field, $key, $isNull = true)
    {
        $keys = explode('.', $key);
        $jsonPath = $field;
        
        foreach ($keys as $k) {
            $jsonPath .= "->'{$k}'";
        }

        $nullCheck = $isNull ? 'IS NULL' : 'IS NOT NULL';
        return $this->andWhere("{$jsonPath} {$nullCheck}");
    }

    /**
     * Filter by JSON containing value (PostgreSQL @> operator).
     *
     * @param string $field JSON field
     * @param array $jsonData JSON data
     * @return CorePostgreSQL
     */
    public function byJsonContains($field, $jsonData)
    {
        $paramName = ':jsonContains' . uniqid();
        $jsonValue = json_encode($jsonData);
        return $this->andWhere("{$field} @> {$paramName}", [$paramName => $jsonValue]);
    }

    /**
     * Filter by JSON is contained by value (PostgreSQL <@ operator).
     *
     * @param string $field JSON field
     * @param array $jsonData JSON data
     * @return CorePostgreSQL
     */
    public function byJsonContainedBy($field, $jsonData)
    {
        $paramName = ':jsonContainedBy' . uniqid();
        $jsonValue = json_encode($jsonData);
        return $this->andWhere("{$field} <@ {$paramName}", [$paramName => $jsonValue]);
    }

    /**
     * Filter by JSON field having key (PostgreSQL ? operator).
     *
     * @param string $field JSON field
     * @param string $key Key to check
     * @return CorePostgreSQL
     */
    public function byJsonHasKey($field, $key)
    {
        $paramName = ':jsonKey' . uniqid();
        return $this->andWhere("{$field} ? {$paramName}", [$paramName => $key]);
    }

    /**
     * Filter by JSON field having any key (PostgreSQL ?| operator).
     *
     * @param string $field JSON field
     * @param array $keys Keys to check
     * @return CorePostgreSQL
     */
    public function byJsonHasAnyKey($field, $keys)
    {
        $paramName = ':jsonAnyKey' . uniqid();
        $keysArray = '{' . implode(',', $keys) . '}';
        return $this->andWhere("{$field} ?| {$paramName}", [$paramName => $keysArray]);
    }

    /**
     * Filter by JSON field having all keys (PostgreSQL ?& operator).
     *
     * @param string $field JSON field
     * @param array $keys Keys to check
     * @return CorePostgreSQL
     */
    public function byJsonHasAllKeys($field, $keys)
    {
        $paramName = ':jsonAllKeys' . uniqid();
        $keysArray = '{' . implode(',', $keys) . '}';
        return $this->andWhere("{$field} ?& {$paramName}", [$paramName => $keysArray]);
    }

    // ============================
    // detail_info -> change_log filters
    // ============================

    /**
     * Filter by created_by from detail_info->change_log JSON.
     *
     * @param int $userId
     * @return CorePostgreSQL
     */
    public function byDetailCreatedBy($userId)
    {
        return $this->byJsonValue('detail_info', 'change_log.created_by', $userId);
    }

    /**
     * Filter by updated_by from detail_info->change_log JSON.
     *
     * @param int $userId
     * @return CorePostgreSQL
     */
    public function byDetailUpdatedBy($userId)
    {
        return $this->byJsonValue('detail_info', 'change_log.updated_by', $userId);
    }

    /**
     * Filter by deleted_by from detail_info->change_log JSON.
     *
     * @param int $userId
     * @return CorePostgreSQL
     */
    public function byDetailDeletedBy($userId)
    {
        return $this->byJsonValue('detail_info', 'change_log.deleted_by', $userId);
    }

    /**
     * Filter by created date range in detail_info->change_log.
     *
     * @param string $startDate
     * @param string $endDate
     * @return CorePostgreSQL
     */
    public function byDetailCreatedDateRange($startDate, $endDate)
    {
        return $this->byJsonRangeValue('detail_info', 'change_log.created_at', '>=', $startDate)
                    ->byJsonRangeValue('detail_info', 'change_log.created_at', '<=', $endDate);
    }

    /**
     * Filter by updated date range in detail_info->change_log.
     *
     * @param string $startDate
     * @param string $endDate
     * @return CorePostgreSQL
     */
    public function byDetailUpdatedDateRange($startDate, $endDate)
    {
        return $this->byJsonRangeValue('detail_info', 'change_log.updated_at', '>=', $startDate)
                    ->byJsonRangeValue('detail_info', 'change_log.updated_at', '<=', $endDate);
    }

    /**
     * Filter by deleted date range in detail_info->change_log.
     *
     * @param string $startDate
     * @param string $endDate
     * @return CorePostgreSQL
     */
    public function byDetailDeletedDateRange($startDate, $endDate)
    {
        return $this->byJsonRangeValue('detail_info', 'change_log.deleted_at', '>=', $startDate)
                    ->byJsonRangeValue('detail_info', 'change_log.deleted_at', '<=', $endDate);
    }

    /**
     * Filter records that have been deleted in detail_info->change_log.
     *
     * @return CorePostgreSQL
     */
    public function detailDeleted()
    {
        return $this->byJsonNullCheck('detail_info', 'change_log.deleted_at', false);
    }

    /**
     * Filter records that have not been deleted in detail_info->change_log.
     *
     * @return CorePostgreSQL
     */
    public function detailNotDeleted()
    {
        return $this->byJsonNullCheck('detail_info', 'change_log.deleted_at', true);
    }
}
