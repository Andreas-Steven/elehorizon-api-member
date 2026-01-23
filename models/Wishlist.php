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
use app\models\reference\ProductVariant;
use app\models\MemberProfile;

class Wishlist extends ActiveRecord
{
    public static $connection = 'db';

    public static function tableName()
    {
        return 'wishlist';
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
                [['detail_info'], 'safe'],
                [['product_variant_id', 'member_profile_id', 'status'], 'integer'],
                [['product_variant_id', 'member_profile_id'], 'required', 'on' => Constants::SCENARIO_CREATE],

                [['product_variant_id', 'member_profile_id', 'status'], 'filter', 'filter' => function ($value) {
                    $value = CoreModel::nullSafe($value);
                    return $value === null ? null : (int) $value;
                }],
            ],
            CoreModel::getStatusRules($this),
            // CoreModel::getLockVersionRulesOnly(),
            // CoreModel::getSyncMdbRules(),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios[Constants::SCENARIO_CREATE] = ['product_variant_id', 'member_profile_id', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['product_variant_id', 'member_profile_id', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['detail_info', 'status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_variant_id' => 'Product Variant ID',
            'member_profile_id' => 'Member Profile ID',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\WishlistQuery(get_called_class());
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->detail_info = [
                'product_variant' => $this->productVariantInfo(),
                'member_profile' => $this->memberProfileInfo(),
                'change_log' => CoreModel::getChangeLog($this, $insert),
            ];

            return true;
        }

        return false;
    }

    public function getProductVariant()
    {
        return $this->hasOne(ProductVariant::class, ['id' => 'product_variant_id']);
    }

    public function getMemberProfile()
    {
        return $this->hasOne(MemberProfile::class, ['id' => 'member_profile_id']);
    }

    private function productVariantInfo(): array
    {
        if (!$this->productVariant) {
            return [];
        }

        return [
            'id' => intval($this->productVariant->id),
            'name' => $this->productVariant->name ?? null,
        ];
    }

    private function memberProfileInfo(): array
    {
        if (!$this->memberProfile) {
            return [];
        }

        return [
            'id' => intval($this->memberProfile->id),
            'name' => $this->memberProfile->name ?? null,
        ];
    }
}
