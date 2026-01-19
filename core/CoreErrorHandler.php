<?php

namespace app\core;

use Yii;
use yii\db\StaleObjectException;
use yii\web\ErrorHandler;
use yii\web\Response;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use app\exceptions\CoreException;

/**
 * Class CoreErrorHandler
 *
 * CoreErrorHandler extends Yii's built-in ErrorHandler and provides
 * standardized JSON responses for API exceptions.
 *
 * Features:
 * - Returns JSON formatted errors for all exceptions
 * - Handles HTTP exceptions, CoreException, and database optimistic lock exceptions
 * - Adds detailed trace information when YII_TRACE is enabled (development)
 * - Standardizes response fields: code, success, message, errors
 *
 * @package app\core
 * @version 1.0.0
 * @since 2025-11-04
 */
class CoreErrorHandler extends ErrorHandler
{
    /**
     * Renders an exception into a JSON response.
     *
     * This method is automatically called by Yii when an exception occurs.
     * It standardizes API error responses and handles different exception types:
     * - CoreException: Uses statusCode and custom errors from the exception
     * - HttpException: Uses the HTTP status code
     * - StaleObjectException: Returns 409 conflict with lock version error
     * - Other exceptions: Returns 500 with exception message
     *
     * Response structure:
     * ```json
     * {
     *   "code": 422,
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {...},
     *   "trace_for_dev": {...} // optional in development
     * }
     * ```
     *
     * @param \Exception $exception The exception instance to render
     * @return void
     *
     * @example
     * ```php
     * // Trigger an exception in a controller action
     * throw new CoreException($model, "Validation failed", 422);
     * 
     * // Output JSON response automatically:
     * {
     *   "code": 422,
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {
     *       "username": ["Username cannot be blank"]
     *   }
     * }
     * ```
     */
    protected function renderException($exception)
    {
        $errors = [];
        $message = Yii::t('app', 'unknownError'); // default message
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Determine HTTP status code
        $statusCode = $exception instanceof HttpException ? $exception->statusCode : 500;

        if ($exception !== null) {
            $message = $exception->getMessage();

            if ($exception instanceof CoreException) {
                // Use status code and errors defined in CoreException
                $statusCode = $exception->getStatusCode();
                $errors = $exception->getErrors();
            } elseif ($exception instanceof StaleObjectException) {
                // Optimistic lock version conflict
                $statusCode = 409;
                $message = Yii::t('app', 'lockVersionOutdated');
            }

            // Build standard response
            $response = [
                'code' => $statusCode,
                'success' => false,
                'message' => $message,
                'errors' => $errors,
            ];

            // Include trace for development environment
            if (YII_TRACE && !($exception instanceof CoreException)) {
                $response['trace_for_dev'] = [
                    'exception' => get_class($exception),
                    'trace' => $exception->getTraceAsString(),
                ];
            }

            Yii::$app->response->data = $response;
            Yii::$app->response->statusCode = $statusCode;
        } else {
            // Generic fallback response for null exceptions
            Yii::$app->response->data = [
                'code' => 500,
                'success' => false,
                'message' => Yii::t('app', 'exceptionOccured'),
            ];
            Yii::$app->response->statusCode = 500;
        }

        // Send JSON response immediately
        Yii::$app->response->send();
    }
}
