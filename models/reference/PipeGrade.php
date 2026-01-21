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
use app\models\reference\Brand;

class PipeGrade extends ActiveRecord
{
    public static $connection = 'db';

    public static function tableName()
    {
        return 'pipe_grade';
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

    public function getBrand()
    {
        return $this->hasOne(Brand::class, ['id' => 'brand_id']);
    }
}
