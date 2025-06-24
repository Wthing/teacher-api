<?php

use app\assets\AppAsset;
use diecoding\aws\s3\Service;

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@mdm'   => '@vendor/mdmsoft',
        '@mdm/admin' => '@mdm/yii2-admin'
    ],

    'modules' => [
        'admin' => [
            'layout' => 'left-menu',
            'class' => 'mdm\admin\Module',
            'controllerMap' => [
                'assignment' => [
                    'class' => 'mdm\admin\controllers\AssignmentController',
                    'userClassName' => 'app\models\User',
                    'idField' => 'id',
                    'usernameField' => 'username',
                    'searchClass' => 'app\models\UserSearch',
                ]
            ],
            'as access' => [
                'class' => 'mdm\admin\components\AccessControl',
                'allowActions' => ['']
            ]
        ],
    ],

    'components' => [

        's3' => [
            'class' => Service::class,
            'endpoint' => $_ENV['S3_ENDPOINT'],
            'usePathStyleEndpoint' => true,
            'credentials' => [ // Aws\Credentials\CredentialsInterface|array|callable
                'key' => $_ENV['S3_AUTH_KEY'],
                'secret' => $_ENV['S3_SECRET_KEY'],
            ],
            'region' => 'my-region',
            'defaultBucket' => $_ENV['S3_BUCKET'],
            'defaultAcl' => 'public-read',
        ],

        'authManager' => [
            'class' => 'yii\rbac\DbManager',
//            'defaultRoles' => ['admin-form'],
        ],
        'as access' => [
            'class' => 'mdm\admin\components\AccessControl',
            'allowActions' => [
            ]
        ],

        'request' => [
            'cookieValidationKey' => 'ZHeB9eXImqVGA8utjmwwivvWa2tu7XVX',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'profile' => 'site/profile',
            ],
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['*'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['127.0.0.1', '::1', '192.168.0.*', '192.168.178.20', '172.18.0.*']
    ];
}

$container = Yii::$container;
$container->set(AppAsset::class);

return $config;
