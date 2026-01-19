<?php

namespace app\core;

/**
 * CoreMongodb class provides utility methods for interacting with MongoDB.
 * Includes methods for model class retrieval, string matching, numeric filtering,
 * status handling, and array element matching in MongoDB queries.
 * 
 * @package app\core
 * @version 1.0.0
 * @since 2025-11-04
 */

use Yii;
use app\helpers\Constants;
use yii\helpers\StringHelper;

class CoreMongodb 
{
    /**
     * Gets the base class name of a model.
     * Useful for logging and error messages.
     * 
     * @param object $model Model instance
     * @return string Base class name without namespace
     */
    public static function getModelClassName($model): string
    {
		return StringHelper::basename(get_class($model));
	}

	/**
	 * Creates a MongoDB regex filter for a like query.
	 * Adds a case-insensitive pattern matching condition.
	 * 
	 * Usage:
	 * ```php
	 * // Search for documents where 'name' contains 'john doe'
	 * $where = [];
	 * CoreMongodb::mdbStringLike('name', 'john doe', $where);
	 * // Results in: ['name' => ['$regex' => 'john.*doe', '$options' => 'i']]
	 * 
	 * // With OR condition
	 * CoreMongodb::mdbStringLike('name', 'john doe', $where, 'or');
	 * // Results in: [['name' => ['$regex' => 'john.*doe', '$options' => 'i']]]
	 * ```
	 * 
	 * @param string $field Field name to search in
	 * @param string|null $value Search value
	 * @param array &$where Reference to where conditions array
	 * @param string $orWhere Optional 'or' for OR conditions
	 */
	public static function mdbStringLike(string $field, ?string $value, array &$where, ?string $orWhere = ""): void
	{
		if ($value !== null) {
			$query = [
				'$regex' => str_replace(' ', '.*', $value ?? ''),
				'$options' => 'i',
			];
			
			match (strtolower($orWhere)) {
				'or' => $where[] = [$field => $query],
				default => $where[$field] = $query,
			};
		}
	}

	/**
	 * Creates a MongoDB exact string match filter.
	 * Adds a case-insensitive exact matching condition using regex anchors.
	 * 
	 * Usage:
	 * ```php
	 * // Search for documents where 'code' equals 'ABC123'
	 * $where = [];
	 * CoreMongodb::mdbStringEqual('code', 'ABC123', $where);
	 * // Results in: ['code' => ['$regex' => '^ABC123$', '$options' => 'i']]
	 * ```
	 * 
	 * @param string $field Field name to match
	 * @param string|null $value Exact value to match
	 * @param array &$where Reference to where conditions array
	 * @param string $orWhere Optional 'or' for OR conditions
	 */
	public static function mdbStringEqual(string $field, ?string $value, array &$where, ?string $orWhere = ""): void
	{
		if ($value !== null) {
			$query = [
				'$regex' => '^' . $value . '$',
				'$options' => 'i',
			];
			
			match (strtolower($orWhere)) {
				'or' => $where[] = [$field => $query],
				default => $where[$field] = $query,
			};
		}
	}

	/**
	 * Creates a MongoDB numeric equality filter.
	 * Adds a numeric equality condition after converting string to integer.
	 * 
	 * Usage:
	 * ```php
	 * // Search for documents where 'quantity' equals 100
	 * $where = [];
	 * CoreMongodb::mdbNumberEqual('quantity', '100', $where);
	 * // Results in: ['quantity' => 100]
	 * ```
	 * 
	 * @param string $field Field name to compare
	 * @param string|null $value Numeric value as string
	 * @param array &$where Reference to where conditions array
	 * @param string $orWhere Optional 'or' for OR conditions
	 */
	public static function mdbNumberEqual(string $field, ?string $value, array &$where, ?string $orWhere = ""): void
	{
		if ($value !== null) {
			$where[$field] = intval($value);
		}
	}

	/**
	 * Creates a MongoDB multiple number filter using $in operator.
	 * Adds a condition to match multiple numeric values from a comma-separated string.
	 * 
	 * Usage:
	 * ```php
	 * // Search for documents where 'status' is 1, 2, or 3
	 * $where = [];
	 * CoreMongodb::mdbNumberMultiple('status', '1,2,3', $where);
	 * // Results in: ['status' => ['$in' => [1, 2, 3]]]
	 * ```
	 * 
	 * @param string $field Field name to compare
	 * @param string|null $value Comma-separated numbers
	 * @param array &$where Reference to where conditions array
	 * @param string $orWhere Optional 'or' for OR conditions
	 */
	public static function mdbNumberMultiple(string $field, ?string $value, array &$where, ?string $orWhere = ""): void
	{
		if ($value !== null) {
			$value = array_map('intval', explode(',', $value)) ?? [];
			
			$query = [
				'$in' => $value,
			];

			match (strtolower($orWhere)) {
				'or' => $where[] = [$field => $query],
				default => $where[$field] = $query,
			};
		}
	}

	/**
	 * Creates a MongoDB status field filter.
	 * Adds conditions for status field excluding deleted records.
	 * 
	 * Usage:
	 * ```php
	 * // Search for active documents (status = 1, not deleted)
	 * $where = [];
	 * CoreMongodb::mdbStatus('status', '1', $where);
	 * // Results in: ['status' => ['$ne' => -1, '$eq' => 1]]
	 * ```
	 * 
	 * @param string $field Status field name
	 * @param string|null $value Status value to match
	 * @param array &$where Reference to where conditions array
	 * @param string $orWhere Optional 'or' for OR conditions
	 */
	public static function mdbStatus(string $field, ?string $value, array &$where, ?string $orWhere = ""): void
	{
		$query = [
			'$ne' => Constants::STATUS_DELETED,
		];

		if ($value !== null) {
			$query['$eq'] = intval($value);
		}
		
		match (strtolower($orWhere)) {
			'or' => $where[] = [$field => $query],
			default => $where[$field] = $query,
		};
	}

	/**
	 * Matches strings in MongoDB array elements using regex.
	 * Adds an $elemMatch query condition with case-insensitive exact matching.
	 * 
	 * Usage:
	 * ```php
	 * // Search for documents where 'tags' array contains exact match 'php'
	 * $where = [];
	 * CoreMongodb::mdbStringMatch('tags', 'php', $where);
	 * // Results in: ['tags' => ['$elemMatch' => ['$regex' => '^php$', '$options' => 'i']]]
	 * 
	 * // With OR condition
	 * CoreMongodb::mdbStringMatch('tags', 'php', $where, 'or');
	 * // Results in: [['tags' => ['$elemMatch' => ['$regex' => '^php$', '$options' => 'i']]]]
	 * ```
	 * 
	 * @param string $field Field name to search in
	 * @param string|null $value Search value
	 * @param array &$where Reference to where conditions array
	 * @param string $orWhere Optional 'or' for OR conditions
	 */
	public static function mdbStringMatch(string $field, ?string $value, array &$where, ?string $orWhere = ""): void
	{
		if ($value !== null) {
			$query = [
				'$elemMatch' => [
					'$regex' => '^' . $value . '$',
					'$options' => 'i',
				]
			];

			match (strtolower($orWhere)) {
				'or' => $where[] = [$field => $query],
				default => $where[$field] = $query,
			};
		}
	}
}