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
use app\core\CoreModel;
use app\helpers\Constants;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property float $length
 * @property float $package_price
 * @property float $total_price
 * @property array|string $variant_pipe_size
 * @property array|string $pipe_group
 * @property array|string $included_items
 * @property int $status
 * @property array|string $detail_info
 */
class InstallationPackage extends ActiveRecord
{
    public $pipe_group;
    public $variant_pipe_size = [];
    public $variant_detail_specs = [];
    public $pipe_grade_options = [];
    public $total_price = 0;
    public static $connection = 'db';

    public static function tableName()
    {
        return 'pipe_installation';
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
