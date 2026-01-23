<?php

namespace app\models\reference;

/**
 * Yii required components
 */
namespace app\models\reference;

use yii\BaseYii as Yii;
use yii\helpers\ArrayHelper;
use yii\db\ActiveRecord;

/**
 * Model required components
 */
use app\core\CoreModel;
use app\helpers\Constants;

class UnitCondition extends ActiveRecord
{
    public static $connection = 'db';

    public static function tableName()
    {
        return 'unit_condition';
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