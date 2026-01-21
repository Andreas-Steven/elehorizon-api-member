<?php

namespace app\models\reference;

/**
 * Yii required components
 */
use yii\BaseYii as Yii;
use yii\helpers\ArrayHelper;
use yii\db\ActiveRecord;

/**
 * Model required components
 */
use app\core\CoreModel;
use app\helpers\Constants;

/**
 * This is the model class for table "product".
 *
 * @property int $id
 * @property string $name
 * @property string $image
 * @property int $category_id
 * @property int $brand_id
 * @property string $badges
 * @property string $price_info
 * @property string $rating
 * @property int $status 0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance
 * @property string $detail_info
 */
class Product extends ActiveRecord
{
    public static $connection = 'db';

    public static function tableName()
    {
        return 'product';
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