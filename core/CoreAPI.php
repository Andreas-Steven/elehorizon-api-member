<?php

namespace app\core;

/**
 * CoreAPI functionality for the application.
 * Provides utility methods for timestamps, user management, validation, and error handling.
 * Version: 1.0.0
 * Version Date: 2025-05-05
 */

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\helpers\Constants;
use app\components\CustomException;
use app\exceptions\CoreException;

class CoreAPI 
{
	/**
	 * Gets current UTC timestamp in application format.
	 * 
	 * Usage:
	 * ```php
	 * $utcTime = Yii::$app->coreAPI::UTCTimestamp();
	 * // Returns: "2025-04-24T10:10:50Z" (UTC format)
	 * ```
	 * 
	 * @return string UTC timestamp in configured format
	 */
	public static function UTCTimestamp(): string
	{
		return gmdate(Yii::$app->params['timestamp']['UTC']);
	}

	/**
	 * Gets current local timestamp in application format.
	 * 
	 * Usage:
	 * ```php
	 * $localTime = Yii::$app->coreAPI::timestamp();
	 * // Returns: "2025-04-24 17:10:50" (local format)
	 * ```
	 * 
	 * @return string Local timestamp in configured format
	 */
	public static function timestamp(): string
	{
		return gmdate(Yii::$app->params['timestamp']['local']);
	}

	/**
	 * Gets current username from session.
	 * Returns 'system' if no user is logged in.
	 * 
	 * Usage:
	 * ```php
	 * $username = Yii::$app->coreAPI::getUsername();
	 * $model->created_by = $username;
	 * ```
	 * 
	 * @return string Current username or 'system'
	 */
	public static function getUsername(): string
	{
		return Yii::$app->session->get('username') ?? 'system';
	}

	/**
	 * Checks if current user has superadmin role.
	 * 
	 * Usage:
	 * ```php
	 * if (Yii::$app->coreAPI::superAdmin()) {
	 *     // Allow superadmin operations
	 * } else {
	 *     throw new ForbiddenHttpException('Superadmin access required');
	 * }
	 * ```
	 * 
	 * @return bool True if user has superadmin role
	 */
	public static function superAdmin(): bool
    {
        $roles = Yii::$app->session->get('roles', []);
        return in_array('superadmin', $roles, true);
    }

	/**
	 * Validates that all request parameters are allowed by model rules.
	 * Checks parameters against model validation rules to prevent invalid fields.
	 * Throws CoreException with 422 status if validation fails.
	 * 
	 * Usage:
	 * ```php
	 * $params = Yii::$app->request->post();
	 * Yii::$app->coreAPI::unavailableParams($model, $params);
	 * // If validation fails, throws CoreException
	 * ```
	 * 
	 * @param object $model Model instance to check rules against
	 * @param array|null $params Request parameters to validate
	 * @throws CoreException with 422 status if validation fails
	 */
	public static function unavailableParams($model, ?array $params): void
	{
		unset($params['id']);

		$allowed = array_merge(...array_column($model->rules(), 0));
		$unsupported = array_diff_key($params, array_flip($allowed));

		if (!$unsupported) {
			return;
		}

		foreach ($unsupported as $key => $value) {
			$model->addError($key, Yii::t('app', 'invalidField', ['label' => $key]));
		}

		throw new CoreException($model, Yii::t('app', 'validationFailed'), 422);
	}

	/**
	 * Throws unauthorized access error.
	 * Standardizes 401 unauthorized responses.
	 * Throws CoreException with 401 status.
	 * 
	 * Usage:
	 * ```php
	 * if (!Yii::$app->user->can('updatePost')) {
	 *     Yii::$app->coreAPI::unauthorizedAccess('Cannot update post');
	 * }
	 * ```
	 * 
	 * @param string|null $message Custom error message
	 * @throws CoreException with 401 status
	 */
	public static function unauthorizedAccess(?string $message = null): void
    {
		throw new CoreException(null, Yii::t('app', 'unauthorizedAccess'), 401);
    }

	/**
	 * Throws server error.
	 * Standardizes 500 internal server error responses.
	 * 
	 * Usage:
	 * ```php
	 * try {
	 *     // Complex operation
	 * } catch (Exception $e) {
	 *     Yii::$app->coreAPI::serverError($e->getMessage());
	 * }
	 * ```
	 * 
	 * @param string|null $message Custom error message
	 * @throws CoreException with 500 status
	 */
	public static function serverError(?string $message = null): void
    {
		throw new CoreException(null, Yii::t('app', 'serverError'), 500);
    }

	/**
	 * Marks MongoDB model as failed sync.
	 * Sets sync status to 1 (failed) for error tracking.
	 * 
	 * Usage:
	 * ```php
	 * try {
	 *     $model->save();
	 * } catch (MongoException $e) {
	 *     Yii::$app->coreAPI::setMongodbSyncFailed($model);
	 *     throw $e;
	 * }
	 * ```
	 * 
	 * @param object $model Model instance to mark as failed sync
	 */
	public static function setMongodbSyncFailed($model): void
	{
		if ($model->id !== null) {
			$model->sync = 1;
			$model->save(false);
		}
	}

	/**
	 * Generates a unique string combining timestamp and random bytes.
	 * Useful for creating unique identifiers or temporary tokens.
	 * 
	 * Usage:
	 * ```php
	 * $token = Yii::$app->coreAPI::generateUniqueString(12);
	 * // Returns: "j2kf9x8h5p2q" (12 characters)
	 * ```
	 * 
	 * @param int $length Desired length of output string
	 * @return string Unique string of specified length
	 */
	public static function generateUniqueString($length = 8)
	{
		$microtime = microtime(true);
		$timeString = substr(base_convert($microtime, 10, 36), -4);
		$randomString = substr(bin2hex(random_bytes($length)), 0, $length - 4);

		return strtoupper($timeString . $randomString);
	}
	
	/**
	 * Checks if the current domain is in the development domain list.
	 * Throws CoreException with 403 status if domain is not in the list.
	 * 
	 * Usage:
	 * ```php
	 * if (!Yii::$app->coreAPI::coreDevelopmentPurpose()) {
	 *     throw new CoreException(null, Yii::t('app', 'unauthorizedAccess'), 403);
	 * }
	 * ```
	 * 
	 * @param array $devDomain List of development domains
	 * @return bool True if domain is in the list, false otherwise
	 */
	public static function coreDevelopmentPurpose($devDomain = [])
	{
		$developmentOnly = Yii::$app->params['developmentOnly'] ?? $devDomain;

		$host = parse_url(
			Yii::$app->request->headers->get('Host')
			?: Yii::$app->request->referrer
			?: Yii::$app->request->hostName,
			PHP_URL_HOST
		) ?: Yii::$app->request->hostName;

		if (!in_array($host, $developmentOnly, true)) {
			throw new CoreException(null, Yii::t('app', 'unauthorizedAccess'), 403);
		}

		// Kalau domain valid, tidak perlu return apa-apa
		return true;
	}

	/**
	 * Mendapatkan koneksi database target berdasarkan parameter koneksi.
	 * 
	 * Fungsi ini digunakan untuk menentukan koneksi database yang akan dipakai
	 * berdasarkan parameter yang dikirim (misalnya dari request atau controller).
	 * Jika parameter tidak ada, maka koneksi default akan digunakan.
	 *
	 * @param array $params Array parameter yang berisi info koneksi.
	 *                      Contoh: ['connection' => 'second_database']
	 * @return string Nama koneksi database target yang sesuai dengan config Yii::$app
	 * 
	 * Contoh penggunaan:
	 * ```php
	 * $params = ['connection' => 'second_database'];
	 * $dbTarget = Yii::$app->coreAPI::::dbConnectionTarget($params);
	 * // $dbTarget = 'dbBintaro' (sesuai Constants::CONNECTION_LIST)
	 * ```
	 */
	public static function dbConnectionTarget(array &$params): string
	{
		$connectionName = Constants::PARAMS_CONNECTION;   // key parameter koneksi
		$connectionList = Constants::CONNECTION_LIST;     // daftar mapping koneksi
		$dbDefault = Constants::DB_DEFAULT;               // koneksi default

		// Ambil nilai parameter koneksi jika ada, jika tidak pakai default
		$targetDb = $params[$connectionName] ?? $dbDefault;

		// Ambil nama koneksi sebenarnya dari daftar koneksi
		$targetConnection = $connectionList[$targetDb] ?? $dbDefault;

		// Hapus parameter koneksi agar tidak tersisa di array
		unset($params[$connectionName]);

		return $targetConnection;
	}
}