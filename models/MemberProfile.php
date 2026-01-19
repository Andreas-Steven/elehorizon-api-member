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

class MemberProfile extends \yii\db\ActiveRecord
{
    public static $connection = 'db';

    public static function tableName()
    {
        return 'member_profile';
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
                [['email'], 'email'],
                [['phone', 'detail_address'], 'safe'],
                [['avatar'], 'url', 'defaultScheme' => 'https'],
                [['birth_date'], 'date', 'format' => 'php:Y-m-d'],
                [['name', 'gender'], 'string', 'min' => 3, 'max' => 255],

                [['name', 'phone', 'detail_address'], 'required', 'on' => Constants::SCENARIO_CREATE],

                [['phone'], function ($attribute, $params) {
                    CoreModel::validateAttributeArray($this, $attribute, $this->getAttributeLabel($attribute));
                }],
                [['detail_address'], function ($attribute, $params) {
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

        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'email', 'phone', 'avatar', 'gender', 'birth_date', 'detail_address', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['name', 'email', 'phone', 'avatar', 'gender', 'birth_date', 'detail_address', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'avatar' => 'Avatar',
            'gender' => 'Gender',
            'birth_date' => 'Birth Date',
            'detail_address' => 'Detail Address',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\MemberProfileQuery(get_called_class());
    }

    public function fields()
    {
        $fields = parent::fields();

        return $fields;
    }

    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            $this->name = CoreModel::htmlPurifier($this->name);
            $this->email = CoreModel::htmlPurifier($this->email);
            $this->avatar = CoreModel::htmlPurifier($this->avatar);
            $this->gender = CoreModel::htmlPurifier($this->gender);

            if (is_array($this->phone)) {
                $this->phone = CoreModel::purifyArray($this->phone);
            }

            if (is_array($this->detail_address)) {
                $this->detail_address = CoreModel::purifyObject($this->detail_address);
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
