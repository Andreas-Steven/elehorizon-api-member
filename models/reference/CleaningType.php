<?php

namespace app\models\reference;

/**
 * Yii required components
 */
use Yii;
use yii\helpers\ArrayHelper;
use yii\db\ActiveRecord;

/**
 * Model required components
 */
use app\helpers\Constants;
use app\core\CoreModel;

class CleaningType extends ActiveRecord
{
    public static $connection = 'db';
    
    public static function tableName()
    {
        return 'cleaning_type';
    }

    public static function getDb()
    {
        return Yii::$app->{static::$connection};
    }

    public static function useDb($connectionName)
    {
        static::$connection = $connectionName;
        return new static();
    }
}
