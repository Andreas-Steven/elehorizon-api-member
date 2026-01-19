<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use app\core\CoreModel;
use app\helpers\Constants;

class MemberCheckoutQuote extends ActiveRecord
{
    public static $connection = 'db';

    public static function tableName()
    {
        return 'service_checkout_preview';
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

    public function rules()
    {
        return ArrayHelper::merge(
            [
                [['member_id', 'installation_package_id', 'pipe_grade_id', 'qty', 'status'], 'integer'],
                [['length'], 'number'],
                [['service_type'], 'string', 'max' => 50],
                [['installation_package_code'], 'string', 'max' => 50],
                [['client_uuid'], 'string', 'max' => 64],
                [['pricing', 'payload', 'detail_info'], 'safe'],
                [['member_id'], 'required', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE]],
            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRulesOnly(),
            CoreModel::getSyncMdbRules(),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios[Constants::SCENARIO_CREATE] = [
            'member_id',
            'client_uuid',
            'service_type',
            'installation_package_id',
            'installation_package_code',
            'pipe_grade_id',
            'length',
            'qty',
            'pricing',
            'payload',
            'status',
            'detail_info',
        ];

        $scenarios[Constants::SCENARIO_UPDATE] = $scenarios[Constants::SCENARIO_CREATE];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->detail_info = [
                'change_log' => CoreModel::getChangeLog($this, $insert),
            ];

            if (!empty($this->pricing) && is_array($this->pricing)) {
                $this->pricing = json_encode($this->pricing);
            }

            if (!empty($this->payload) && is_array($this->payload)) {
                $this->payload = json_encode($this->payload);
            }

            return true;
        }

        return false;
    }

    public function afterFind()
    {
        $this->pricing = $this->decodeJsonField($this->pricing);
        $this->payload = $this->decodeJsonField($this->payload);
        $this->detail_info = $this->decodeJsonField($this->detail_info);
        parent::afterFind();
    }

    private function decodeJsonField($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
