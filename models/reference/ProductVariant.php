<?php

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
use app\models\reference\Product;

class ProductVariant extends ActiveRecord
{
    public $thumbnail;
    public $image;
    public $badges;
    public static $connection = 'db';

    public static function tableName()
    {
        return 'product_variant';
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

    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }
}
