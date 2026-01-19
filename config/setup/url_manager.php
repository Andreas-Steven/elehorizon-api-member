<?php
/**
 * URL Manager Configuration
 * 
 * This file returns the URL manager configuration array used in Yii2 application.
 * You can modify the rules here without touching main config files.
 */

return [
    'enablePrettyUrl' => true,
    'showScriptName' => false,
    'rules' => [
        '/' => 'site/index',
        '/v1' => 'site/index',
        '/v1/index' => 'site/index',

        /**
         * --------------------------------------------------------------------------
         * Add your custom rules below
         * --------------------------------------------------------------------------
         */
        
    ],
];
