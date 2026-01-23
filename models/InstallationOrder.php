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
use app\models\reference\Brand;
use app\models\reference\InstallationPackage;
use app\models\reference\InstallationService;
use app\models\reference\PipeGrade;
use app\models\reference\ProductVariant;
use app\models\MemberProfile;

class InstallationOrder extends ActiveRecord
{
    public $service_type_id;
    public static $connection = 'db';

    public static function tableName()
    {
        return 'installation_order';
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
                [['length', 'member_profile_id', 'service_type_id', 'product_variant_id', 'installation_package_id', 'pipe_grade_id', 'qty', 'status'], 'filter', 'filter' => function ($value) {
                    $value = CoreModel::nullSafe($value);
                    return $value === null ? null : (int) $value;
                }],

                [['installation_service', 'pricing', 'detail_info', 'detail_address', 'schedule', 'note'], 'safe'],
                [['service_type_id', 'member_profile_id', 'product_variant_id', 'installation_package_id', 'pipe_grade_id', 'length', 'qty', 'status'], 'integer'],

                [['member_profile_id', 'product_variant_id', 'service_type_id', 'length', 'qty', 'detail_address', 'schedule'], 'required', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE]],
                [['service_type_id'], 'in', 'range' => array_keys(Constants::INSTALLATION_SERVICE_TYPE_LIST), 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE]],

                [['installation_service'], function($attribute) {
                    CoreModel::validateAttributeArray($this, $attribute, 'Installation Service');
                }],
                [['detail_address'], function($attribute) {
                    CoreModel::validateAttributeArray($this, $attribute, $this->getAttributeLabel($attribute));
                }],
                [['schedule'], function($attribute) {
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

        $scenarios[Constants::SCENARIO_CREATE] = ['member_profile_id', 'service_type_id', 'product_variant_id', 'product_variant', 'installation_package_id', 'installation_service', 'pipe_grade_id', 'length', 'qty', 'pricing', 'detail_address', 'schedule', 'note', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['member_profile_id', 'service_type_id', 'product_variant_id', 'product_variant', 'installation_package_id', 'installation_service', 'pipe_grade_id', 'length', 'qty', 'pricing', 'detail_address', 'schedule', 'note', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status', 'detail_info'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'member_profile_id' => 'Member Profile ID',
            'client_uuid' => 'Client UUID',
            'service_type_id' => 'Service Type',
            'product_variant_id' => 'Product Variant ID',
            'product_variant' => 'Product Variant',
            'installation_package_id' => 'Installation Package ID',
            'installation_service' => 'Installation Service',
            'pipe_grade_id' => 'Pipe Grade ID',
            'length' => 'Length',
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
        return new \app\models\query\ServiceOrderQuery(get_called_class());
    }

    public function fields()
    {
        $fields = parent::fields();

        return $fields;
    }

    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            $this->note = CoreModel::htmlPurifier($this->note);

            if (intval($this->service_type_id) == Constants::SERVICE_TYPE_INSTALLATION) {
                if (!isset($this->installation_package_id)) {
                    $this->addError('Service Order', Yii::t('app', 'selectInstallationPackage'));
                    return false;
                }

                if (!isset($this->pipe_grade_id)) {
                    $this->addError('Service Order', Yii::t('app', 'required', ['label' => 'Pipe Grade']));
                    return false;
                }
            }

            if (intval($this->service_type_id) == Constants::SERVICE_TYPE_NON_PACKAGE) {
                if (empty($this->installation_service)) {
                    $this->addError('Service Order', Yii::t('app', 'required', ['label' => 'Installation Service']));
                    return false;
                }
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
            $installationPackage = null;
            $pipeGrade = null;

            if ($this->scenario === Constants::SCENARIO_CREATE) {
                if (intval($this->service_type_id) == Constants::SERVICE_TYPE_INSTALLATION) {
                    $this->validateInstallationService('installation_service');
                    $installationPackage = $this->installationPackage();
                    $pipeGrade = $this->pipeGrade();
                }

                if (intval($this->service_type_id) == Constants::SERVICE_TYPE_NON_PACKAGE) {
                    $this->validateInstallationService('installation_service');
                    $this->installation_package_id = null;
                    $this->pipe_grade_id = null;
                }

                $this->pricing = $this->getPricing(
                    $installationPackage ?? null,
                    $pipeGrade ?? null,
                );

                $serviceType = $this->validateServiceTypeId('service_type_id');
            }

            $this->detail_info = [
                'member_profile' => $memberProfile ?? [],
                'product_variant' => $productVariant ?? [],
                'installation_package' => $installationPackage ?? [],
                'pipe_grade' => $pipeGrade ?? [], 
                'service_type' => $serviceType ?? [],

                'change_log' => CoreModel::getChangeLog($this, $insert),
            ];

            return true;
        }

        return false;
    }

    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {

        }
        
        parent::afterSave($insert, $changedAttributes);
    }

    public function afterFind()
    {
        parent::afterFind();
    }

    public function getInstallationPackage()
    {
        return $this->hasOne(InstallationPackage::class, ['id' => 'installation_package_id']);
    }

    public function getPipeGrade()
    {
        return $this->hasOne(PipeGrade::class, ['id' => 'pipe_grade_id']);
    }

    public function getProductVariant()
    {
        return $this->hasOne(ProductVariant::class, ['id' => 'product_variant_id']);
    }

    public function getMemberProfile()
    {
        return $this->hasOne(MemberProfile::class, ['id' => 'member_profile_id']);
    }

    /**
     * VALIDATION FUNCTIONS
     */
    private function productVariant()
    {
        if ($this->productVariant) {
            $product = $this->productVariant->product;

            $brand = [];
            if ($product) {
                $brandModel = $product->brand ?? null;
                if ($brandModel && (int)($brandModel->status ?? Constants::STATUS_ACTIVE) === Constants::STATUS_ACTIVE) {
                    $brand = [
                        'id' => intval($brandModel->id),
                        'name' => $brandModel->name,
                    ];
                }

                if (empty($brand)) {
                    $brandId = $product->brand_id ?? null;
                    if (!empty($brandId)) {
                        $brandModel = Brand::findOne([
                            'id' => intval($brandId),
                            'status' => Constants::STATUS_ACTIVE,
                        ]);

                        if ($brandModel) {
                            $brand = [
                                'id' => intval($brandModel->id),
                                'name' => $brandModel->name,
                            ];
                        }
                    }
                }

                if (empty($brand) && isset($product->detail_info)) {
                    $detailInfo = $product->detail_info;
                    if (is_string($detailInfo)) {
                        $decoded = json_decode($detailInfo, true);
                        $detailInfo = is_array($decoded) ? $decoded : [];
                    }

                    if (is_array($detailInfo) && isset($detailInfo['brand']) && is_array($detailInfo['brand'])) {
                        $brand = $detailInfo['brand'];
                    }
                }
            }
    
            return [
                'id' => intval($this->productVariant->id),
                'name' => $this->productVariant->name,
                'sku' => $this->productVariant->sku,
                'variant_code' => $this->productVariant->variant_code,
                'brand' => $brand,
                'product' => [
                    'id' => $product ? intval($product->id) : null,
                    'name' => $product ? $product->name : null,
                ],
            ];
        }

        return [];
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

    private function installationPackage()
    {
        if (!empty($this->installation_package_id) && $this->installationPackage) {
            return [
                'id' => intval($this->installationPackage->id),
                'code' => $this->installationPackage->code ?? null,
                'name' => $this->installationPackage->name ?? null,
                'base_price' => floatval($this->installationPackage->package_price),
            ];
        }

        return [];
    }

    private function pipeGrade()
    {
        if (!empty($this->pipe_grade_id) && $this->pipeGrade) {
            $brand = $this->pipeGrade->brand;

            return [
                'id' => intval($this->pipeGrade->id),
                'name' => $this->pipeGrade->name ?? null,
                'thickness_mm' => floatval($this->pipeGrade->thickness_mm),
                'price_per_meter' => floatval($this->pipeGrade->price_per_meter),
                'brand' => [
                    'id' => intval($brand->id),
                    'name' => $brand->name ?? null,
                ],
            ];
        }

        return [];
    }
    
    private function validateInstallationService($attribute)
    {
        $installationService = $this->installation_service;
        $allowedFields = Yii::$app->params['allowedFields']['service']['installationService'];

        foreach ($installationService as $key => $value) {
            CoreModel::validateRequiredFields($this, $attribute, $allowedFields, $value);
            
            $id = CoreModel::nullSafe($value['id']);
            if (empty($id)) {
                $this->addError($attribute, Yii::t('app', 'required', ['label' => 'Installation Service ID']));
                throw new ErrorMessage($this, Yii::t('app', 'serviceOrderFailed'), 422);
            }

            $qty = CoreModel::nullSafe($value['qty']);
            if (empty($qty)) {
                $this->addError($attribute, Yii::t('app', 'required', ['label' => 'Installation Service Quantity']));
                throw new ErrorMessage($this, Yii::t('app', 'serviceOrderFailed'), 422);
            }
            
            $service = InstallationService::findOne(['id' => intval($id), 'status' => Constants::STATUS_ACTIVE]);
            if (empty($service)) {
                $this->addError($attribute, Yii::t('app', 'fieldDataNotFound', ['label' => 'Installation Service']));
                throw new ErrorMessage($this, Yii::t('app', 'serviceOrderFailed'), 422);
            }

            $installationService[$key]['id'] = intval($service->id);
            $installationService[$key]['name'] = $service->name;
            $installationService[$key]['code'] = $service->code;
            $installationService[$key]['unit_type'] = $service->unit_type;
            $installationService[$key]['base_price'] = $service->base_price;
            $installationService[$key]['qty'] = intval($qty);
            $installationService[$key]['subtotal'] = floatval($service->base_price) * intval($qty);
        }

        $this->installation_service = $installationService;
    }

    private function getPricing($installationPackage = null, $pipeGrade = null)
    {
        $installationService = $this->installation_service;
        $installationServicePrice = 0;
        if (!empty($installationService)) {
            foreach ($installationService as $key => $value) {
                $installationServicePrice += floatval($value['subtotal']);
            }  
        }

        $installationPackagePrice = 0;
        if (!empty($installationPackage)) {
            $installationPackagePrice = floatval($installationPackage['base_price']);
        }

        $pipeGradePrice = 0;
        if (!empty($pipeGrade)) {
            $pricePerMeter = floatval($pipeGrade['price_per_meter']);
            $pipeGradePrice = $pricePerMeter * intval($this->length);
        }

        $totalPerUnit = $installationServicePrice + $installationPackagePrice + $pipeGradePrice;
        $grandTotal = $totalPerUnit * intval($this->qty);

        return [
            'installation_service_price' => $installationServicePrice,
            'installation_package_price' => $installationPackagePrice,
            'pipe_grade_price' => $pipeGradePrice,
            'total_per_unit' => $totalPerUnit,
            'grand_total' => $grandTotal,
        ];
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

        $schedule['time_slot'] = CoreModel::nullSafe($schedule['time_slot']);
        if (empty($schedule['time_slot'])) {
            $this->addError($attribute, Yii::t('app', 'required', ['label' => 'Schedule Time Slot']));
            throw new ErrorMessage($this, Yii::t('app', 'serviceOrderFailed'), 422);
        }

        $timeSlotInput = $schedule['time_slot'];
        if (is_numeric($timeSlotInput)) {
            $timeSlotKey = (int) $timeSlotInput;
        } else {
            $timeSlotKey = array_search($timeSlotInput, Constants::TIME_SLOT_LIST, true);
            if ($timeSlotKey === false) {
                $timeSlotKey = null;
            }
        }

        if ($timeSlotKey === null || !array_key_exists($timeSlotKey, Constants::TIME_SLOT_LIST)) {
            $this->addError($attribute, Yii::t('app', 'invalidValue', ['label' => 'Schedule Time Slot']));
            throw new ErrorMessage($this, Yii::t('app', 'serviceOrderFailed'), 422);
        }

        $schedule['time_slot'] = Constants::TIME_SLOT_LIST[$timeSlotKey];

        $this->schedule = $schedule;
    }

    public function validateServiceTypeId($attribute)
    {
        $serviceTypeId = $this->$attribute;

        return [
            'id' => intval($serviceTypeId),
            'name' => Constants::INSTALLATION_SERVICE_TYPE_LIST[intval($serviceTypeId)],
        ];
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