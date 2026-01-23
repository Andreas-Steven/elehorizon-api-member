<?php

namespace app\helpers;

use app\core\CoreConstants;

class Constants extends CoreConstants
{
    #add your new application constants here.  
    const SERVICE_TYPE_INSTALLATION = 1;
    const SERVICE_TYPE_NON_PACKAGE = 2;
    const SERVICE_TYPE_CLEANING = 3;

    const INSTALLATION_SERVICE_TYPE_LIST = [
        self::SERVICE_TYPE_INSTALLATION => 'Installation Package',
        self::SERVICE_TYPE_NON_PACKAGE => 'Non Package Installation',
    ];
    
    const CLEANING_SERVICE_TYPE_LIST = [
        self::SERVICE_TYPE_CLEANING => 'Cleaning',
    ];

    const TIME_SLOT_MORNING = 1;
    const TIME_SLOT_AFTERNOON = 2;
    const TIME_SLOT_NIGHT = 3;

    const TIME_SLOT_LIST = [
        self::TIME_SLOT_MORNING => 'Pagi (08:00 - 12:00)',
        self::TIME_SLOT_AFTERNOON => 'Siang (12:00 - 16:00)',
        self::TIME_SLOT_NIGHT => 'Malam (16:00 - 20:00)',
    ];
}