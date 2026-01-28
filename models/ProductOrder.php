<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;
use yii\db\ActiveRecord;
use app\core\CoreModel;
use app\helpers\Constants;

class ProductOrder extends ActiveRecord
{
    public static $connection = 'db';

    public static function tableName()
    {
        return 'product_order';
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
        return Constants::OPTIMISTIC_LOCK;
    }

    public function rules()
    {
        return ArrayHelper::merge(
            [
                [[''], 'string', 'max' => 255],
                [['id', 'member_profile_id', 'status'], 'integer'],
                [['detail_address', 'pricing_summary', 'items', 'note', 'detail_info'], 'safe'],

                [['member_profile_id'], 'required', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE]],
                
                [['member_profile_id'], 'filter', 'filter' => 'intval', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE]],
                [['items'], function ($attribute) {
                    CoreModel::validateAttributeArray($this, $attribute, $this->getAttributeLabel($attribute));
                }],
                 [['detail_address'], function ($attribute) {
                    CoreModel::validateAttributeArray($this, $attribute, $this->getAttributeLabel($attribute));
                }],
            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRulesOnly(),
            CoreModel::getSyncMdbRules(),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['member_profile_id', 'items', 'detail_address', 'pricing_summary', 'note', 'status', 'detail_info', 'sync_mdb', 'lock_version'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['member_profile_id', 'items', 'detail_address', 'pricing_summary', 'note', 'status', 'detail_info', 'sync_mdb', 'lock_version'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status', 'sync_mdb', 'lock_version'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'member_profile_id' => 'Member Profile ID',
            'items' => 'Items',
            'detail_address' => 'Detail Address',
            'pricing_summary' => 'Pricing Summary',
            'note' => 'Note',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
            'sync_mdb' => 'Sync',
            'lock_version' => 'Lock Version',
        ];
    }

    public static function find()
    {
        return new \app\models\query\ProductOrderQuery(get_called_class());
    }

    public function fields()
    {
        $fields = parent::fields();
        unset($fields['sync_mdb']);

        return $fields;
    }

    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            $this->note = CoreModel::htmlPurifier($this->note);

            if ($this->items === null) {
                $this->items = [];
            }

            if ($this->detail_address === null) {
                $this->detail_address = [];
            }

            if ($this->pricing_summary === null) {
                $this->pricing_summary = [];
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
