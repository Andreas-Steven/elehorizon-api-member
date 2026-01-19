<?php

namespace app\components;

use Yii;
use yii\filters\auth\HttpBearerAuth;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use UnexpectedValueException;
use app\helpers\Constants;

/**
 * JwtBearerAuth is a custom HTTP Bearer authentication component for Yii2.
 *
 * This class extends Yii2's HttpBearerAuth to authenticate users using JWT tokens.
 * It decodes the token, verifies expiration, sets session values, and optionally checks user roles.
 *
 * Features:
 * - Extracts JWT token from the Authorization header.
 * - Decodes and validates the JWT using the secret key and algorithm configured in params.
 * - Checks token expiration.
 * - Sets session variables for username, user ID, department, and role.
 * - Provides a challenge method to respond with 401 Unauthorized for invalid or missing tokens.
 *
 * Usage Example:
 * ```php
 * public function behaviors()
 * {
 *     return [
 *         'authenticator' => [
 *             'class' => \app\components\JwtBearerAuth::class,
 *             'except' => ['index'], // Actions to skip JWT authentication
 *         ],
 *     ];
 * }
 * ```
 *
 * @package app\components
 * @version 1.0.0
 * @since 2025-11-04
 */
class JwtBearerAuth extends HttpBearerAuth
{
    /**
     * @var string|null Role to check for the authenticated user
     */
    public $role;

    /**
     * Authenticates the user using a JWT token from the Authorization header.
     *
     * Steps:
     * 1. Get the Authorization header.
     * 2. Extract the Bearer token.
     * 3. Decode the token using Firebase JWT library.
     * 4. Check expiration (`exp` claim).
     * 5. Set Yii session variables for username, user ID, department, and role.
     * 6. Return the decoded payload for further use.
     *
     * @param \yii\web\User $user The user component
     * @param \yii\web\Request $request The request object
     * @param \yii\web\Response $response The response object
     * @return array|null Decoded JWT payload or null if authentication fails
     *
     * @example
     * ```php
     * $payload = $auth->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);
     * if ($payload === null) {
     *     // Authentication failed
     * }
     * ```
     */
    public function authenticate($user, $request, $response)
    {
        $authHeader = $request->getHeaders()->get('Authorization');

        if (!$authHeader) {
            return null;
        }

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            return null;
        }
        
        try {
            $payload = JWT::decode(
                $token, 
                new Key(Yii::$app->params['jwt']['key'], Yii::$app->params['jwt']['algorithm'])
            );
            $payload = json_decode(json_encode($payload), true);

            // Check token expiration
            if ($payload['exp'] < time()) {
                return null;
            }
        } catch (\LogicException $e) {
            return null;
        } catch (UnexpectedValueException $e) {
            return null;
        }

        // Set session variables
        Yii::$app->session->set('username', $payload['user']['username']);
        Yii::$app->session->set('userid', $payload['user']['id']);
        Yii::$app->session->set('dept', $payload['user']['dept']);

        if (!isset($payload['user']['roles'][Yii::$app->params['codeApp']])) {
            Yii::$app->session->set('role', null);
        } else {
            Yii::$app->session->set('role', $payload['user']['roles'][Yii::$app->params['codeApp']]);
        }

        return $payload;
    }

    /**
     * Sends a challenge response when authentication fails.
     * Sets HTTP status 401 and adds WWW-Authenticate header.
     *
     * @param \yii\web\Response $response The response object
     *
     * @example
     * ```php
     * $auth->challenge(Yii::$app->response); // Responds with 401 Unauthorized
     * ```
     */
    public function challenge($response)
    {
        $response->getHeaders()->set('WWW-Authenticate', 'Bearer');
        $response->setStatusCode(401);
    }
}
