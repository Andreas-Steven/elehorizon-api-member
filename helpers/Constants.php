<?php

namespace app\helpers;

use app\core\CoreConstants;

class Constants extends CoreConstants
{
    #add your new application constants here.  
    const SERVICE_TYPE_INSTALLATION = 1;
    const SERVICE_TYPE_NON_PACKAGE = 2;
    const SERVICE_TYPE_CLEANING = 3;

    const SERVICE_TYPE_LIST = [
        self::SERVICE_TYPE_INSTALLATION => 'Installation',
        self::SERVICE_TYPE_NON_PACKAGE => 'Non Package',
        self::SERVICE_TYPE_CLEANING => 'Cleaning',
    ];
}