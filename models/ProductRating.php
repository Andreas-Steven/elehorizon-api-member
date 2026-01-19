<?php

namespace app\models;

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

class ProductRating extends \yii\db\ActiveRecord
{
    public static $connection = 'db';

    public static function tableName()
    {
        return 'product_rating';
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

    public function optimisticLock() 
    {
        // return Constants::OPTIMISTIC_LOCK;
    }

    public function rules()
    {
        return ArrayHelper::merge(
            [
                [['product_variant_id', 'member_profile_id'], 'integer'],
                [['product_variant_id', 'member_profile_id'], 'required', 'on' => Constants::SCENARIO_CREATE],
                [['rating', 'detail_info'], 'safe'],
            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRulesOnly(),
            CoreModel::getSyncMdbRules(),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios[Constants::SCENARIO_CREATE] = ['product_id', 'rating', 'sold', 'review', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['product_id', 'rating', 'sold', 'review', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_id' => 'Product ID',
            'rating' => 'Rating',
            'sold' => 'Sold',
            'review' => 'Review',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\ProductRatingQuery(get_called_class());
    }

    public function fields()
    {
        $fields = parent::fields();

        return $fields;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->detail_info = [
                'change_log' => CoreModel::getChangeLog($this, $insert),
            ];

            return true;
        }

        return false;
    }

    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {
            // Info: Put your code here for insert action

        }
        
        // Info: Call parent afterSave in the end.
        parent::afterSave($insert, $changedAttributes);
    }

    public function afterFind()
    {
        // Info: Put your code here

        // Info: Call parent afterFind in the end.
        parent::afterFind();
    }
}
