<?php

namespace app\core;

/**
 * CoreController functionality for the application core controller.
 * Provides controller functionality for API response handling, CORS, and content negotiation.
 * Version: 1.0.0
 * Version Date: 2025-05-05
 */

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\components\CustomException;
use app\helpers\Constants;
use app\exceptions\CoreException;
use yii\base\DynamicModel;
use yii\helpers\ArrayHelper;

/**
 * CoreController serves as the base controller for RESTful API endpoints.
 * Provides common functionality for API response handling, CORS, and content negotiation.
 * 
 * Features:
 * - Automatic CORS configuration for cross-domain requests
 * - JSON response formatting
 * - Standardized error handling
 * - CSRF validation configuration
 * - Request method filtering
 * 
 * @property bool $enableCsrfValidation CSRF validation flag, configurable via params
 */
class CoreController extends Controller
{
    /**
     * @var bool CSRF validation status
     */
    public $enableCsrfValidation;

    /**
     * Configures controller behaviors including CORS and content negotiation.
     * Settings are loaded from application parameters.
     * 
     * @return array Array of behaviors
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $params = Yii::$app->params;
        $this->enableCsrfValidation = $params['request']['enableCsrfValidation'];

        unset($behaviors['authenticator']);

        return ArrayHelper::merge($behaviors, [
            'corsFilter' => [
                'class' => '\yii\filters\Cors',
                'cors' => [
                    'Origin' => $params['cors']['origins'], 
                    'Access-Control-Request-Headers' => $params['cors']['requestHeaders'], 
                    'Access-Control-Request-Origin' => $params['cors']['requestOrigin'], 
                    'Access-Control-Allow-Credentials' => $params['cors']['allowCredentials'],
                    'Access-Control-Request-Method' => $params['cors']['requestMethods'],
                    'Access-Control-Allow-Headers' => $params['cors']['allowHeaders'],
                ],
            ],
            'contentNegotiator' => [
                'class' => 'yii\filters\ContentNegotiator',
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
            'authenticator' => [
                'class' => 'app\components\JwtBearerAuth',
                'except' => $params['jwt']['except'],
            ],
            'verbs' => [
                'class' => 'yii\filters\VerbFilter',
                'actions' => $params['verbsAction'],
            ],
        ]);
    }

    /**
     * Handles exceptions thrown during action execution.
     * Standardizes error responses to JSON format.
     * 
     * Usage:
     * ```php
     * // In your controller
     * public function actionIndex()
     * {
     *     return CoreController::actionIndex();
     * }
     * ```
     * 
     * @param \Exception $e Exception to handle
     * @return \yii\web\Response JSON response with error details
     */
    public function beforeAction($action)
    {
        try {
            return parent::beforeAction($action);
        } catch (CoreException $e) {
            Yii::$app->response->statusCode = $e->getStatusCode();
            Yii::$app->response->format = Response::FORMAT_JSON;
            Yii::$app->response->data = $e->toArray();
            Yii::$app->response->send();
            Yii::$app->end();
        }
    }

    /**
     * Default action for the controller.
     * Returns a basic success response with service information.
     * 
     * Usage:
     * ```php
     * // In your controller
     * public function actionIndex()
     * {
     *     return CoreController::actionIndex();
     * }
     * ```
     * 
     * @return array API response
     */
    public function actionIndex()
	{
        $data = [
            'language' => Yii::$app->language,
            'version' => Yii::$app->params['serviceVersion'],
        ];

        if (YII_ENV_DEV) {
            $data['environment'] = 'development';
        }

		return [
			'code' => Yii::$app->response->statusCode = 200,
			'success' => true,
			'message' => Yii::$app->params['titleService'].' '.Yii::$app->params['serviceVersion'],
            'data' => $data,
		];
	}

    /**
     * Alias for actionIndex.
     * Provides the same service information through a different method name.
     * 
     * Usage:
     * ```php
     * // In your controller
     * public function actionIndex()
     * {
     *     return CoreController::coreActionIndex();
     *     // Returns service info in standard format
     * }
     * ```
     * 
     * @return array API response
     */
    public function coreActionIndex()
	{
		return self::actionIndex();
	}

    /**
     * Error action for the controller.
     * Returns an error response with the exception message.
     * 
     * Usage:
     * ```php
     * // In config/web.php
     * 'errorHandler' => [
     *     'errorAction' => 'site/error',
     * ],
     * ```
     * 
     * @return array API response with error details
     */
    public function actionError()
    {
        return [
            'status' => Yii::$app->errorHandler->exception->statusCode,
            'success' => false,
            'message' => Yii::$app->errorHandler->exception->getMessage(),
            'errors' => [],
        ];
    }

    /**
     * Handles exceptions thrown during action execution.
     * Standardizes error responses to JSON format.
     * 
     * Usage:
     * ```php
     * // In your controller
     * public function actionIndex()
     * {
     *     return CoreController::actionIndex();
     * }
     * ```
     * 
     * @param \Exception $e Exception to handle
     * @return \yii\web\Response JSON response with error details
     */
    public function errorHandler($exception)
    {
        $response = new Response();
        $response->setStatusCode($exception->getCode());
        $response->data = [
            'code' => $exception->getCode(),
            'success' => false,
            'message' => 'Custom error message',
        ];
        return $response;
    }

    /**
     * Finds a model instance by ID or other parameters.
     * Supports both single ID lookup and complex queries with additional parameters.
     * Automatically handles optimistic locking version field by removing it from results.
     * 
     * Usage:
     * ```php
     * // Find by ID
     * $user = $this->coreFindModelOne(User::class, ['id' => 123]);
     * 
     * // Find with additional conditions
     * $activeUser = $this->coreFindModelOne(
     *     User::class,
     *     ['id' => 123],
     *     ['status' => Constants::STATUS_ACTIVE]
     * );
     * 
     * // Find by other parameters only
     * $adminUser = $this->coreFindModelOne(
     *     User::class,
     *     null,
     *     ['role' => 'admin', 'is_active' => true]
     * );
     * 
     * if ($user === null) {
     *     throw new NotFoundHttpException('User not found');
     * }
     * ```
     * 
     * @param string $model Fully qualified model class name
     * @param array|null $paramsID ID parameters, typically ['id' => value]
     * @param array|null $otherParams Additional query conditions as key-value pairs
     * @return object|null Model instance if found, null otherwise
     */
    public function coreFindModelOne($model, ?array $paramsID, ?array $otherParams = []): ?object
	{
        $id = $paramsID['id'] ?? null;
        $where = [];

        if ($id) {
            $where = ['id' => $id];
        }

        if ($otherParams) {
            $where = ArrayHelper::merge($where, $otherParams);
        }

		if (!empty($where)) {
			$query = $model::find()->where($where);
			if (($modelInstance = $query->one()) !== null) {
                $lockVersion = Constants::OPTIMISTIC_LOCK;

                if (isset($modelInstance->$lockVersion)) {
                    // Hide lock_version on result data for update/delete.
                    unset($modelInstance->$lockVersion);
                }

				return $modelInstance;
			}
		}

		return null;
	}

    /**
     * Finds a model instance by ID or other parameters.
     * Supports with query parameters.
     * Automatically handles optimistic locking version field by removing it from results.
     * 
     * Usage:
     * ```php
     * // Find by ID
     * $user = $this->coreFindModel(User::class, ['id' => 123])->one();
     * 
     * if ($user === null) {
     *     throw new NotFoundHttpException('User not found');
     * }
     * ```
     * 
     * @param string $model Fully qualified model class name
     * @param array|null $params Query parameters, typically ['id' => value]
     * @return object|null Model instance if found, null otherwise
     */
    public function coreFindModel($model, ?array $params): ?object
	{
		if (!empty($params)) {
			$query = $model::find()->where($params);
            if ($query->exists()) {
                $modelInstance = $query;
                $lockVersion = Constants::OPTIMISTIC_LOCK;

                if (isset($modelInstance->$lockVersion)) {
                    // Hide lock_version on result data for update/delete.
                    unset($modelInstance->$lockVersion);
                }

                return $modelInstance;
            }
		}

		return null;
	}

    /**
     * Formats data provider for API response.
     * Standardizes pagination and data format for list endpoints.
     * 
     * Usage:
     * ```php
     * // In controller action
     * return CoreController::coreData($dataProvider);
     * ```
     * 
     * @param object $dataProvider Data provider instance
     * @return array API response with pagination
     */
    public function coreData($dataProvider): array
    {
        return [
            'code' => Yii::$app->response->statusCode = 200,
            'success' => true,
            'message' => Yii::t('app', 'success'),
            'pagination' => [
                'page' => $dataProvider->pagination->page + 1,
                'totalCount' => $dataProvider->totalCount,
                'total' => max($dataProvider->count, 0),
                'display' => $dataProvider->count,
            ],
            'data' => $dataProvider,
        ];
    }

    /**
     * Formats custom data for API response.
     * Useful for returning non-standard data structures.
     * 
     * Usage:
     * ```php
     * // In controller action
     * $stats = [
     *     'total_users' => User::find()->count(),
     *     'active_users' => User::find()->active()->count()
     * ];
     * return CoreController::coreCustomData($stats, 'Statistics retrieved');
     * ```
     * 
     * @param array $model Model data
     * @param string|null $message Custom message
     * @return array API response
     */
    public function coreCustomData($model=[], ?string $message = null): array
    {
        $response = is_array($model) ? $model : [$model];

        return [
            'code' => Yii::$app->response->statusCode = 200,
            'success' => true,
            'message' => $message ?? Yii::t('app', 'success'),
            'data' => $response,
        ];
    }

    /**
     * Formats success response with model data.
     * Standardizes successful API responses with optional custom message and additional data.
     * 
     * Usage:
     * ```php
     * // In controller action
     * return CoreController::coreSuccess(
     *     $model,
     *     Yii::t('app', 'User updated successfully'),
     * );
     * ```
     * 
     * @param array $model Model data
     * @param string|null $message Custom message
     * @param array|null $customData Additional data
     * @return array API response
     */
    public function coreSuccess($model=[], ?string $message = null, ?array $customData=null): array
    {
        $response = [];
        
        if (!empty($model)) {
            $response = is_array($model) ? $model : [$model];
        }

        // Only try to access scenario if $model is an object
        if ($message === null) {
            if (is_object($model) && property_exists($model, 'scenario')) {
                $message = Yii::t('app', "{$model->scenario}RecordSuccess");
            } else {
                $message = Yii::t('app', "success");
            }
        }

        return [
            'code' => Yii::$app->response->statusCode = 200,
            'success' => true,
            'message' => $message,
            'data' => $response,
        ];
    }

    /**
     * Formats error response with model errors.
     * Standardizes validation error responses with custom messages.
     * 
     * Usage:
     * ```php
     * // In controller action
     * return CoreController::coreError($model, Yii::t('app', 'Failed to create user'));
     * ```
     * 
     * @param object $model Model instance with validation errors
     * @param string|null $message Custom error message
     * @return array API response
     */
    public function coreError($model, ?string $message = null): array 
    {
        if ($message === null) {
            if (is_object($model) && property_exists($model, 'scenario')) {
                $message = Yii::t('app', "{$model->scenario}RecordFailed");
            } else {
                $message = Yii::t('app', "badRequest");
            }
        }

        return [
            'code' => Yii::$app->response->statusCode = 422,
            'success' => false,
            'message' => $message,
            'errors' => isset($model->errors) ? $model : [],
        ];
    }

    /**
     * Formats not found response.
     * Returns a standardized 404 response for missing resources.
     * 
     * Usage:
     * ```php
     * // In controller action
     * if ($model === null) {
     *     return CoreController::coreDataNotFound();
     * }
     * ```
     * 
     * @return array API response with 404 status
     */
    public function coreDataNotFound(): array
    {
        try {
            throw new \Exception(Yii::t('app', 'dataNotFound'));
        } catch (\Exception $e) {
            return [
                'code' => Yii::$app->response->statusCode = 404,
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [],
            ];
        }
    }

    /**
     * Formats bad request response.
     * Returns a standardized 400 response for invalid requests.
     * 
     * Usage:
     * ```php
     * // In controller action
     * return CoreController::coreBadRequest(
     *     $model,
     *     Yii::t('app', 'Invalid bulk update request')
     * );
     * ```
     * 
     * @param object $model Model instance or error array
     * @param string|null $message Custom error message
     * @return array API response with 400 status
     */
    public function coreBadRequest($model, ?string $message = null): array
    {
        return [
            'code' => Yii::$app->response->statusCode = 400,
            'success' => false,
            'message' => $message ?? Yii::t('app', 'badRequest'),
            'errors' => $model ?? [],
        ];
    }

    /**
     * Validates data provider and search model.
     * Ensures data provider and optional search model are valid before processing.
     * 
     * Usage:
     * ```php
     * // In controller action
     * public function actionList()
     * {
     *     $searchModel = new UserSearch();
     *     $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
     *     
     *     CoreController::validateProvider($dataProvider, $searchModel);
     *     
     *     return CoreController::coreData($dataProvider);
     * }
     * ```
     * 
     * @param object $dataProvider Data provider instance
     * @param object|null $searchModel Search model instance
     * @return array|bool API error response or false if validation passes
     * @throws CoreException when validation fails
     */
    public function validateProvider($dataProvider, $searchModel = null): array|bool
    {
        $model = new DynamicModel();
        
        if (isset($dataProvider->errors)) {
            $errors = $dataProvider->errors;

            foreach($errors as $error) {
                $model->addError($error['field'], $error['message']);
            }

            throw new CoreException($model, Yii::t('app', 'validationFailed'), 422);
        }

        return false;
    }

    /**
     * Validates request parameters.
     * Ensures required parameters are present and correctly formatted.
     * 
     * Usage:
     * ```php
     * // In controller action
     * CoreController::validateParams($params, Constants::SCENARIO_UPDATE);
     * ```
     * 
     * @param array|null $params Request parameters
     * @param string $scenario Validation scenario
     * @return array|bool API error response or false if validation passes
     * @throws CoreException when validation fails
     */
    public function validateParams(?array $params, string $scenario = 'default'): array|bool
    {
        $idKey = 'id';
        $messageError = null;
        
        // Create dynamic model with attributes
        $attributes = array_keys($params);
        $model = new DynamicModel($attributes);
        
        // Add validation rules
        if (isset($params[$idKey])) {
            $id = $params[$idKey];
            unset($params[$idKey]);

            if (!is_numeric($id) || intval($id) != $id) {
                $model->addError($idKey, Yii::t('app', 'integer', ['label' => $idKey]));
            }

            if ($scenario === Constants::SCENARIO_UPDATE && empty($params)) {
                $model->addError($idKey, Yii::t('app', 'emptyParams'));
            }
        } else {
            $messageError = Yii::t('app', 'validationFailed');
            $model->addError($idKey, Yii::t('app', 'required', ['label' => $idKey]));
        }

        if ($model->hasErrors()) {
            throw new CoreException($model, Yii::t('app', 'validationFailed'), 422);
        }

        return false;
    }

    /**
     * Checks for empty parameters in update or delete scenarios.
     * Prevents unnecessary database operations when no changes are made.
     * 
     * Usage:
     * ```php
     * // In controller action
     * CoreController::emptyParams($model);
     * 
     * // Continue with update...
     * ```
     * 
     * @param object $model Model instance
     * @param string|null $scenario Validation scenario
     * @return array|bool API error response or false if changes detected
     * @throws CoreException when no changes detected
     */
    public function emptyParams($model, ?string $scenario = null): bool
    {
        $scenario = $scenario ?? Constants::SCENARIO_UPDATE;
        $optimisticLock = Constants::OPTIMISTIC_LOCK;

        // Remove uneditable attributes
        $dirty = $model->getDirtyAttributes();
        unset($dirty['id'], $dirty[$optimisticLock]);

        // Check for update
        if ($scenario === Constants::SCENARIO_UPDATE && empty($dirty)) {
            throw new CoreException($model, Yii::t('app', 'noRecordUpdated'), 400);
        }

        // Check for delete
        if ($scenario === Constants::SCENARIO_DELETE && $model->status === $model->getOldAttribute('status')) {
            throw new CoreException($model, Yii::t('app', 'noRecordDeleted'), 400);
        }

        return false;
    }

    /**
     * Checks for empty parameters in update or delete scenarios.
     * Prevents unnecessary database operations when no changes are made.
     * 
     * Usage:
     * ```php
     * // In controller action
     * CoreController::emptyParams($model);
     * 
     * // Continue with update...
     * ```
     * 
     * @param object $model Model instance
     * @param string|null $scenario Validation scenario
     * @return array|bool API error response or false if changes detected
     * @throws CoreException when no changes detected
     */
    public function coreEmptyParams($model, array $unsetAttributes = []): bool|array
    {
        $scenario = $model->scenario ?? 'default';
        $dirtyAttributes = $model->getDirtyAttributes();

        if (empty($dirtyAttributes) && $scenario === Constants::SCENARIO_UPDATE) {
            throw new CoreException($model, Yii::t('app', 'noRecordUpdated'), 422);
        }

        if ($scenario === Constants::SCENARIO_DELETE && $model->status === $model->getOldAttribute('status')) {
            throw new CoreException($model, Yii::t('app', 'noRecordDeleted'), 422);
        }

        if (!empty($unsetAttributes)) {
            foreach ($unsetAttributes as $attribute) {
                unset($dirtyAttributes[$attribute]);
            }
    
            if (empty($dirtyAttributes)) {
                throw new CoreException($model, Yii::t('app', 'noRecordUpdated'), 422);
            }
        }

        return false;
    }

    /**
     * Check required fields in request params
     * 
     * @param array $params Request parameters
     * @param array $requiredFields Array of required field names
     * @return array|false Returns error array if missing fields, false if all present
     */
    public static function coreCheckRequiredParams(array $params, array $requiredFields): array|false
    {
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($params[$field]) || $params[$field] === '' || $params[$field] === null) {
                $errors[] = [
                    'field' => $field,
                    'message' => Yii::t('app', 'fieldRequired', ['field' => $field])
                ];
            }
        }
        
        if (!empty($errors)) {
            return [
                'code' => Yii::$app->response->statusCode = 422,
                'success' => false,
                'message' => Yii::t('app', 'fieldValidationFailed'),
                'errors' => $errors,
            ];
        }
        
        return false;
    }

    /**
     * Checks for unavailable parameters in the request.
     * Validates that all requested parameters are valid for the model.
     * 
     * Usage:
     * ```php
     * // In controller action
     * CoreController::unavailableParams($model, $params);
     * 
     * ```
     * 
     * @param object $model Model instance
     * @param array $params Request parameters
     * @throws CoreException when invalid parameters detected
     */
    public function unavailableParams($model, array $params): void
    {
        Yii::$app->coreAPI::unavailableParams($model, $params);
    }

    /**
     * Returns unauthorized access response.
     * Standardizes 401 unauthorized responses.
     * 
     * Usage:
     * ```php
     * // In controller action
     * CoreController::unauthorizedAccess();
     * 
     * ```
     * 
     * @return array Unauthorized access response
     */
    public function coreUnauthorizedAccess(): array
    {
		return [
			'code' => Yii::$app->response->statusCode = 401,
			'success' => false,
			'message' => Yii::t('app', 'unauthorizedAccess'),
			'errors' => [],
		];
    }

    /**
     * Returns exception occurred response.
     * Standardizes 422 unprocessable entity responses.
     * 
     * Usage:
     * ```php
     * // In controller action
     * CoreController::exceptionOccured($model, $message, $errors);
     * 
     * ```
     * 
     * @param object $model Model instance
     * @param string|null $message Custom error message
     * @param array|null $errors Model errors
     * @return array Exception occurred response
     */
    public function coreExceptionOccured($model, ?string $message = null, ?array $errors = []): array 
    {
        if (!empty($errors)) {
            $model->addErrors($errors);
        }

        return [
            'code' => Yii::$app->response->statusCode = 422,
            'success' => false,
            'message' => $message ?? Yii::t('app', 'exceptionOccured'),
            'errors' => isset($model->errors) && !empty($model->errors) ? $model : [],
        ];
    }

    /**
     * Checks if the request requires superadmin privileges.
     * Validates if the current user has permission to perform status-restricted operations.
     * 
     * Usage:
     * ```php
     * // In controller action
     * CoreController::superadmin($params);
     * 
     * ```
     * 
     * @param array|null $params Request parameters containing status
     * @return array|bool Error response array if unauthorized, false if authorized
     * @throws CoreException with 403 status code if unauthorized
     */
    public function superadmin(?array $params): array|bool
    {
        $status = (int)($params['status'] ?? null);
        $restrictStatus = Constants::RESTRICT_STATUS_LIST;

        if (
            !$this->isSuperAdmin()
            && $status !== null
            && in_array($status, $restrictStatus, true)
        ) {
            $model = new DynamicModel();
            throw new CoreException($model, Yii::t('app', 'superadminOnly'), 403);
        }

        return false;
    }

    /**
     * Checks if the current user has superadmin role.
     * Used for role-based access control in restricted operations.
     * 
     * Usage:
     * ```php
     * // In controller action
     * CoreController::isSuperAdmin();
     * 
     * ```
     * 
     * @return bool True if user has superadmin role, false otherwise
     */
    public function isSuperAdmin(): bool
    {
        $roles = Yii::$app->session->get('roles', []);
        return !in_array('superadmin', $roles, true);
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
	public static function coreConnection(array &$params): string
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
