<?php

/*
 * Web configuration for the application.
 * 
 * Version: 1.0.0
 * Version Date: 2025-05-05
 */

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
$modules = require __DIR__ . '/modules.php';

$mongodb = include __DIR__ . '/mongodb.php';
$urlManagerFile = include __DIR__ . '/url_manager.php';
$dbManagerFile = include __DIR__ . '/db_manager.php';
$mailerFile = include __DIR__ . '/mailer.php';

$bootstrap = [
    'log',
];

$aliases = [
    '@bower' => '@vendor/bower-asset',
    '@npm'   => '@vendor/npm-asset',
];

$request = [
    #insert a secret key in the following (if it is empty) - this is required by cookie validation
    'cookieValidationKey' => $params['request']['cookieValidationKey'] . '-' . $params['request']['extraCookies'],
    'enableCsrfValidation' => $params['request']['enableCsrfValidation'],
    'enableCookieValidation' => $params['request']['enableCookieValidation'],
    'parsers' => [
        'application/json' => 'yii\web\JsonParser',
    ]
];

$urlManager = $urlManagerFile ?: [
    'enablePrettyUrl' => true,
    'showScriptName' => false,
    'rules' => [
        '/' => 'site/index',
        '/v1' => 'site/index',
        '/v1/index' => 'site/index',
    ],
];

$dbManager = $dbManagerFile ?: [];

$user = [
    'identityClass' => 'app\models\User',
    'enableAutoLogin' => true,
    'enableSession' => false,
    'loginUrl' => null
];

$mailer = $mailerFile ?: [
    'class' => \yii\symfonymailer\Mailer::class,
    'viewPath' => '@app/mail',
    #send all mails to a file by default.
    'useFileTransport' => true,
];

$config = [
    'id' => 'basic-'.$params['request']['extraCookies'],
    'name' => $params['titleService'],
    'timeZone' => $params['timestamp']['timeZone'],
    'basePath' => dirname(__DIR__),
    'bootstrap' => $bootstrap,
    'language' => $params['language']['default'],
    'aliases' => $aliases,
    'params' => $params,
    'modules' => $modules,
    'components' => array_merge($dbManager, 
        [
            'db' => $db,
            'mongodb' => $mongodb ?: null,
            'user' => $user,
            'request' => $request,
            'urlManager' => $urlManager,
            'mailer' => $mailer,
            'coreAPI' => [
                'class' => 'app\core\CoreAPI',
            ],
            'errorHandler' => [
                // 'errorAction' => 'site/error',
                'class' => 'app\core\CoreErrorHandler',
            ],
            'pagination' => [
                'class' => 'yii\data\Pagination',
                'defaultPageSize' => 10,
            ],
            'cache' => [
                'class' => 'yii\caching\FileCache',
            ],
            'i18n' => [
                'translations' => [
                    'app' => [
                        'class' => 'app\core\CoreMessageSource',
                        'basePath' => '@app/translation',
                        'fileMap' => [
                            'app' => 'app.php',
                        ],
                    ],
                ],
            ],
            'log' => [
                'traceLevel' => YII_DEBUG ? 3 : 1,
                'targets' => [
                    [
                        'class' => 'yii\log\FileTarget',
                        'levels' => YII_ENV_DEV ? ['error', 'warning'] : ['error'],
                        'categories' => ['application'],
                        'logFile' => '@runtime/logs/app.log',
                        'logVars' => [],
                        'maxFileSize' => 1024 * 2,
                        'maxLogFiles' => 10,
                    ],
                    [
                        'class' => 'yii\log\FileTarget',
                        'levels' => ['info'],
                        'categories' => ['yii\db\Command::execute'],
                        'logFile' => '@runtime/logs/sql.log',
                        'logVars' => [],
                        'maxFileSize' => 1024 * 2,
                        'maxLogFiles' => 5,
                    ],
                ],
            ],
            'response' => [
                'class' => 'yii\web\Response',
                'on beforeSend' => function ($event) {
                    $response = $event->sender;

                    if (!YII_ENV_DEV){
                        if ($response->data !== null && Yii::$app->request->get('suppress_response_code')) {
                            $response->data = [
                                'success' => $response->isSuccessful,
                                'data' => $response->data,
                            ];
                            $response->statusCode = 200;
                        } else {
                            $response->data['success'] = $response->isSuccessful;
                            unset($response->data['type']);

                            if (!$response->isSuccessful) {
                                unset($response->data['name']);
                            }

                            if ($response->statusCode == 405) {
                                unset($response->data['status']);
                                $response->data['errors'] = [];
                                $response->data['code'] = $response->statusCode;
                            }
                        }
                    }
                },
            ],
        ],
    ),
    'on beforeAction' => function ($event ) use ($params) {
        $req = Yii::$app->request->getBodyParams();
        if (isset($req['language'])) {
            Yii::$app->language = $req['language'];
            if(!in_array(Yii::$app->language, $params['language']['list'])) {
                Yii::$app->language = $params['language']['default'];
            }
        }
    },
    'on beforeRequest' => function ($event) use ($params) {
        $defaultLang = $params['language']['default'];
        $lang = Yii::$app->request->getHeaders()->get('Accept-Language');

        Yii::$app->language = $lang ?? $defaultLang;
        if(!in_array(Yii::$app->language, $params['language']['list'])) {
            Yii::$app->language = $defaultLang;
        }
    },
];

if (YII_ENV_DEV) {
    #configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        #uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        #uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
