<?php
/**
 * Mailer Configuration
 * 
 * This file returns the mailer configuration array used in Yii2 application.
 * You can modify the rules here without touching main config files.
 */

return [
    'class' => \yii\symfonymailer\Mailer::class,
    'viewPath' => '@app/mail',
    #send all mails to a file by default.
    'useFileTransport' => true,
];
