<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;
use yii\db\ActiveRecord;
use app\core\CoreModel;
use app\helpers\Constants;

class Checkout extends ActiveRecord
{
    public static $connection = 'db';

    public static function tableName()
    {
        return 'checkout';
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
                [['cart_snapshot', 'pricing', 'payment_detail', 'detail_info'], 'safe'],
                [['member_profile_id'], 'integer'],
                [['voucher_code', 'payment_method', 'payment_status'], 'string', 'max' => 50],
                [['expired_at'], 'safe'],

                [['member_profile_id', 'payment_method'], 'required', 'on' => [Constants::SCENARIO_CREATE]],
            ],
            CoreModel::getStatusRules($this)
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = [
            'member_profile_id',
            'cart_snapshot',
            'pricing',
            'voucher_code',
            'payment_method',
            'payment_detail',
            'payment_status',
            'expired_at',
            'status',
            'detail_info',
        ];
        $scenarios[Constants::SCENARIO_UPDATE] = [
            'payment_status',
            'status',
            'detail_info',
        ];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'member_profile_id' => 'Member Profile ID',
            'cart_snapshot' => 'Cart Snapshot',
            'pricing' => 'Pricing',
            'voucher_code' => 'Voucher Code',
            'payment_method' => 'Payment Method',
            'payment_detail' => 'Payment Detail',
            'payment_status' => 'Payment Status',
            'expired_at' => 'Expired At',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\CheckoutQuery(get_called_class());
    }

    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            if ($this->cart_snapshot === null) {
                $this->cart_snapshot = [];
            }
            if ($this->pricing === null) {
                $this->pricing = [];
            }
            if ($this->payment_detail === null) {
                $this->payment_detail = [];
            }

            return true;
        }

        return false;
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
}
