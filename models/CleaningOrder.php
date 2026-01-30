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
use app\exceptions\ErrorMessage;

use app\models\MemberProfile;
use app\models\reference\CleaningType;
use app\models\reference\ProductVariant;
use app\models\reference\UnitCondition;

class CleaningOrder extends ActiveRecord
{
    public static $connection = 'db';

    public static function tableName()
    {
        return 'cleaning_order';
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
                [['unit_condition', 'pricing', 'detail_address', 'schedule', 'note', 'unit_condition', 'unit_desciption', 'detail_info'], 'safe'],
                [['service_type_id', 'member_profile_id', 'product_variant_id', 'category_id', 'cleaning_type_id', 'qty', 'status'], 'integer'],

                [['member_profile_id', 'service_type_id', 'product_variant_id', 'category_id', 'cleaning_type_id', 'unit_condition', 'qty', 'detail_address', 'schedule'], 'required', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE]],
                [['service_type_id', 'member_profile_id', 'product_variant_id', 'category_id', 'cleaning_type_id', 'qty'], 'filter', 'filter' => 'intval', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE]],
                
                [['unit_condition'], function ($attribute) {
                    CoreModel::validateAttributeArray($this, $attribute, $this->getAttributeLabel($attribute));
                }],
                [['detail_address'], function ($attribute) {
                    CoreModel::validateAttributeArray($this, $attribute, $this->getAttributeLabel($attribute));
                }],
                [['schedule'], function ($attribute) {
                    CoreModel::validateAttributeArray($this, $attribute, $this->getAttributeLabel($attribute));
                }],

                [['schedule'], 'validateSchedule'],
                [['detail_address'], 'validateDetailAddress'],
            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRulesOnly(),
            CoreModel::getSyncMdbRules(),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios[Constants::SCENARIO_CREATE] = ['member_profile_id', 'service_type_id', 'product_variant_id', 'category_id', 'cleaning_type_id', 'unit_condition', 'unit_desciption', 'qty', 'pricing', 'detail_address', 'schedule', 'note', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['member_profile_id', 'service_type_id', 'product_variant_id', 'category_id', 'cleaning_type_id', 'unit_condition', 'unit_desciption', 'qty', 'pricing', 'detail_address', 'schedule', 'note', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status', 'detail_info'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'member_profile_id' => 'Member Profile ID',
            'service_type_id' => 'Service Type',
            'product_variant_id' => 'Product Variant ID',
            'category_id' => 'Category ID',
            'cleaning_type_id' => 'Cleaning Type ID',
            'unit_condition' => 'Unit Condition',
            'unit_desciption' => 'Unit Description',
            'qty' => 'Quantity',
            'pricing' => 'Pricing',
            'detail_address' => 'Detail Address',
            'schedule' => 'Schedule',
            'note' => 'Location Detail',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\CleaningOrderQuery(get_called_class());
    }

    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            $this->note = CoreModel::htmlPurifier($this->note);
            $this->unit_desciption = CoreModel::htmlPurifier($this->unit_desciption);
            
            if (intval($this->service_type_id) !== Constants::SERVICE_TYPE_CLEANING) {
                $this->addError('Cleaning Order', Yii::t('app', 'invalidValue', ['label' => 'Service Type']));
                return false;
            }

            return true;
        }

        return false;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $memberProfile = $this->memberProfile();
            $productVariant = $this->productVariant();
            $cleaningType = $this->cleaningType();

            if ($this->scenario === Constants::SCENARIO_CREATE) {
                $this->pricing = $this->getPricing($cleaningType ?? null);
                $serviceType = $this->validateServiceType('service_type_id');
                $this->validateUnitCondition('unit_condition');
            }

            $this->detail_info = [
                'member_profile' => $memberProfile ?? [],
                'product_variant' => $productVariant ?? [],
                'cleaning_type' => $cleaningType ?? [],
                'service_type' => $serviceType ?? [],
                'change_log' => CoreModel::getChangeLog($this, $insert),
            ];

            return true;
        }

        return false;
    }

    public function getCleaningType()
    {
        return $this->hasOne(CleaningType::class, ['id' => 'cleaning_type_id']);
    }

    public function getProductVariant()
    {
        return $this->hasOne(ProductVariant::class, ['id' => 'product_variant_id']);
    }

    public function getMemberProfile()
    {
        return $this->hasOne(MemberProfile::class, ['id' => 'member_profile_id']);
    }

    private function productVariant(): array
    {
        if (!$this->productVariant) {
            return [];
        }

        $product = $this->productVariant->product;

        return [
            'id' => intval($this->productVariant->id),
            'name' => $this->productVariant->name,
            'sku' => $this->productVariant->sku,
            'brand' => $product && $product->brand ? [
                'id' => intval($product->brand->id),
                'name' => $product->brand->name,
            ] : [],
            'product' => [
                'id' => $product ? intval($product->id) : null,
                'name' => $product ? $product->name : null,
            ],
        ];
    }

    private function memberProfile(): array
    {
        if (!$this->memberProfile) {
            return [];
        }

        $phone = $this->memberProfile->phone;
        if (is_string($phone)) {
            $decoded = json_decode($phone, true);
            $phone = is_array($decoded) ? $decoded : [];
        }

        return [
            'id' => intval($this->memberProfile->id),
            'name' => $this->memberProfile->name,
            'email' => $this->memberProfile->email,
            'phone' => is_array($phone) ? $phone : [],
        ];
    }

    private function cleaningType(): array
    {
        if (!$this->cleaningType) {
            return [];
        }

        return [
            'id' => intval($this->cleaningType->id),
            'name' => $this->cleaningType->name ?? null,
            'base_price' => $this->cleaningType->base_price ?? null,
        ];
    }

    public function validateServiceType($attribute)
    {
        $serviceType = $this->$attribute;

        return [
            'id' => intval($serviceType),
            'name' => Constants::CLEANING_SERVICE_TYPE_LIST[intval($serviceType)] ?? null,
        ];
    }

    private function getPricing($cleaningType = null): array
    {
        $cleaningTypePrice = 0;
        if (is_array($this->pricing) && array_key_exists('cleaning_type_price', $this->pricing)) {
            $cleaningTypePrice = floatval($this->pricing['cleaning_type_price']);
        } elseif (!empty($cleaningType) && array_key_exists('base_price', $cleaningType)) {
            $cleaningTypePrice = floatval($cleaningType['base_price']);
        }

        $totalPerUnit = $cleaningTypePrice;
        $grandTotal = $totalPerUnit * intval($this->qty);

        return [
            'cleaning_type_price' => $cleaningTypePrice,
            'total_per_unit' => $totalPerUnit,
            'grand_total' => $grandTotal,
        ];
    }

    public function validateUnitCondition($attribute)
    {
        $unitCondition = CoreModel::ensureArray($this->$attribute);
        $unitCondition = array_filter($unitCondition, function($condition) {
            return !in_array($condition, ['[]', '', null, 'null'], true);
        });

        $unit_condition = [];
        foreach ($unitCondition as $value) {
            $data = UnitCondition::find()->where(['id' => intval($value), 'status' => Constants::STATUS_ACTIVE])->one();
            if (empty($data)) {
                $this->addError($attribute, Yii::t('app', 'invalidValue', ['label' => 'Unit Condition']));
                throw new ErrorMessage($this, Yii::t('app', 'serviceOrderFailed'), 422);
            }

            $unit_condition[] = [
                'id' => intval($value),
                'name' => $data->name,
            ];
        }

        $this->$attribute = $unit_condition;
    }

    public function validateSchedule($attribute, $params)
    {
        $schedule = $this->$attribute;
        $dateValidator = new \yii\validators\DateValidator(['format' => 'php:Y-m-d']);

        $schedule['date'] = CoreModel::nullSafe($schedule['date']);
        if (empty($schedule['date'])) {
            $this->addError($attribute, Yii::t('app', 'required', ['label' => 'Schedule Date']));
            throw new ErrorMessage($this, Yii::t('app', 'serviceOrderFailed'), 422);
        }

        if (!$dateValidator->validate($schedule['date'], $error)) {
            $this->addError($attribute, Yii::t('app', 'invalidValue', ['label' => 'Schedule Date']));
            throw new ErrorMessage($this, Yii::t('app', 'serviceOrderFailed'), 422);
        }

        $timeKey = array_key_exists('time_slot', $schedule) ? 'time_slot' : 'time';
        $schedule[$timeKey] = CoreModel::nullSafe($schedule[$timeKey] ?? null);
        if (empty($schedule[$timeKey])) {
            $this->addError($attribute, Yii::t('app', 'required', ['label' => 'Schedule Time Slot']));
            throw new ErrorMessage($this, Yii::t('app', 'serviceOrderFailed'), 422);
        }

        $timeSlotInput = $schedule[$timeKey];
        if (is_numeric($timeSlotInput)) {
            $timeSlotKey = (int) $timeSlotInput;
        } else {
            $timeSlotKey = array_search($timeSlotInput, Constants::TIME_SLOT_LIST, true);
            if ($timeSlotKey === false) {
                $normalizedInput = preg_replace('/\s+/', '', (string) $timeSlotInput);
                $timeSlotKey = null;
                foreach (Constants::TIME_SLOT_LIST as $key => $label) {
                    if (preg_replace('/\s+/', '', (string) $label) === $normalizedInput) {
                        $timeSlotKey = $key;
                        break;
                    }
                }
            }
        }

        if ($timeSlotKey === null || !array_key_exists($timeSlotKey, Constants::TIME_SLOT_LIST)) {
            $this->addError($attribute, Yii::t('app', 'invalidValue', ['label' => 'Schedule Time Slot']));
            throw new ErrorMessage($this, Yii::t('app', 'serviceOrderFailed'), 422);
        }

        $schedule['time_slot'] = Constants::TIME_SLOT_LIST[$timeSlotKey];
        if ($timeKey !== 'time_slot') {
            unset($schedule[$timeKey]);
        }

        $this->schedule = $schedule;
    }

    public function validateDetailAddress($attribute)
    {
        $detail_address = $this->detail_address;

        $allowedFields = Yii::$app->params['allowedFields']['service']['detail_address'];
        CoreModel::validateRequiredFields($this, $attribute.'[detail_address]', $allowedFields, $detail_address);

        if (empty($detail_address['city'])) {
            $this->addError($attribute.'[detail_address][city]', Yii::t('app', 'required', ['label' => 'City']));
        }

        if (empty($detail_address['district'])) {
            $this->addError($attribute.'[detail_address][district]', Yii::t('app', 'required', ['label' => 'District']));
        }

        if (empty($detail_address['sub_district'])) {
            $this->addError($attribute.'[detail_address][sub_district]', Yii::t('app', 'required', ['label' => 'Sub District']));
        }

        if (is_string($detail_address['zip_code']) && !ctype_digit($detail_address['zip_code'])) {
            $this->addError($attribute.'[detail_address][zip_code]', Yii::t('app', 'integer', ['label' => 'Zip Code']));
        }

        $detail_address['location'] = $this->validateLocation($attribute.'[location]', $detail_address['location']);

        if ($this->hasErrors()) {
            throw new ErrorMessage($this, Yii::t('app', 'logisticFailed'), 422);
        }

        $this->detail_address = $detail_address;
    }

    public function validateLocation($attribute, $location = null)
    {
        $allowedFields = Yii::$app->params['allowedFields']['service']['location'];
        if (empty($location)) {
            $this->addError($attribute, Yii::t('app', 'required', ['label' => 'Location']));
            return;
        }

        CoreModel::validateRequiredFields($this, $attribute, $allowedFields, $location);

        if (!filter_var($location['lat'], FILTER_VALIDATE_FLOAT)) {
            $this->addError($attribute.'[lat]', Yii::t('app', 'invalidLatFormat'));
            return;
        }

        if (!filter_var($location['lng'], FILTER_VALIDATE_FLOAT)) {
            $this->addError($attribute.'[lng]', Yii::t('app', 'invalidLngFormat'));
            return;
        }

        $latitude = (float) $location['lat'];
        $longitude = (float) $location['lng'];

        if ($latitude < -90 || $latitude > 90) {
            $this->addError($attribute, Yii::t('app', 'invalidLat'));
        }

        if ($longitude < -180 || $longitude > 180) {
            $this->addError($attribute, Yii::t('app', 'invalidLng'));
        }

        return [
            'lat' => $latitude,
            'lng' => $longitude,
        ];
    }
}
