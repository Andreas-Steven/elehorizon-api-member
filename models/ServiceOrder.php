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
use app\models\reference\CleaningType;
use app\models\reference\InstallationPackage;
use app\models\reference\InstallationService;
use app\models\reference\PipeGrade;
use app\models\reference\ProductVariant;

class ServiceOrder extends ActiveRecord
{
    public static $connection = 'db';

    public static function tableName()
    {
        return 'service_order';
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
                [['length', 'member_id', 'service_type', 'product_variant_id', 'installation_package_id', 'cleaning_type_id', 'pipe_grade_id', 'qty', 'status'], 'filter', 'filter' => function ($value) {
                    $value = CoreModel::nullSafe($value);
                    return $value === null ? null : (int) $value;
                }],

                [['installation_service', 'pricing', 'detail_info'], 'safe'],
                [['service_type', 'member_id', 'product_variant_id', 'installation_package_id', 'cleaning_type_id', 'pipe_grade_id', 'length', 'qty', 'status'], 'integer'],

                [['member_id', 'product_variant_id', 'service_type', 'length', 'qty'], 'required', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE]],
                [['service_type'], 'in', 'range' => array_keys(Constants::SERVICE_TYPE_LIST), 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE]],

                
                [['installation_service'], function($attribute) {
                    CoreModel::validateAttributeArray($this, $attribute, 'Installation Service');
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

        $scenarios[Constants::SCENARIO_CREATE] = ['member_id', 'service_type', 'product_variant_id', 'product_variant', 'installation_package_id', 'installation_service', 'cleaning_type_id', 'pipe_grade_id', 'length', 'qty', 'pricing', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = [$scenarios[Constants::SCENARIO_CREATE]];
        $scenarios[Constants::SCENARIO_DELETE] = ['status', 'detail_info'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'member_id' => 'Member ID',
            'client_uuid' => 'Client UUID',
            'service_type' => 'Service Type',
            'product_variant_id' => 'Product Variant ID',
            'product_variant' => 'Product Variant',
            'installation_package_id' => 'Installation Package ID',
            'installation_service' => 'Installation Service',
            'cleaning_type_id' => 'Cleaning Type ID',
            'pipe_grade_id' => 'Pipe Grade ID',
            'length' => 'Length',
            'qty' => 'Quantity',
            'pricing' => 'Pricing',
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
            if (intval($this->service_type) == Constants::SERVICE_TYPE_INSTALLATION) {
                if (!isset($this->installation_package_id)) {
                    $this->addError('Service Order', Yii::t('app', 'selectInstallationPackage'));
                    return false;
                }

                if (!isset($this->pipe_grade_id)) {
                    $this->addError('Service Order', Yii::t('app', 'required', ['label' => 'Pipe Grade']));
                    return false;
                }
            }

            if (intval($this->service_type) == Constants::SERVICE_TYPE_NON_PACKAGE) {
                if (empty($this->installation_service)) {
                    $this->addError('Service Order', Yii::t('app', 'required', ['label' => 'Installation Service']));
                    return false;
                }
            }

            if (intval($this->service_type) == Constants::SERVICE_TYPE_CLEANING) {
                if (empty($this->cleaning_type_id)) {
                    $this->addError('Service Order', Yii::t('app', 'selectCleaningType'));
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
            if ($this->scenario === Constants::SCENARIO_CREATE) {
                $productVariant = $this->productVariant();

                if (intval($this->service_type) == Constants::SERVICE_TYPE_INSTALLATION) {
                    $installationPackage = $this->installationPackage();
                    $pipeGrade = $this->pipeGrade();
                    $this->cleaning_type_id = null;
                    $this->installation_service = [];
                }

                if (intval($this->service_type) == Constants::SERVICE_TYPE_NON_PACKAGE) {
                    $this->validateInstallationService('installation_service');
                    $this->installation_package_id = null;
                    $this->pipe_grade_id = null;
                }
                
                if (intval($this->service_type) == Constants::SERVICE_TYPE_CLEANING) {
                    $cleaningType = $this->cleaningType();
                    $this->installation_service = [];
                    $this->installation_package_id = null;
                    $this->pipe_grade_id = null;
                }

                $this->pricing = $this->getPricing(
                    $installationPackage ?? null,
                    $pipeGrade ?? null,
                    $cleaningType ?? null
                );
            }

            $this->detail_info = [
                'product_variant' => $productVariant ?? [],
                'installation_package' => $installationPackage ?? [],
                'cleaning_type' => $cleaningType ?? [],
                'pipe_grade' => $pipeGrade ?? [], 

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

    public function getCleaningType()
    {
        return $this->hasOne(CleaningType::class, ['id' => 'cleaning_type_id']);
    }

    public function getPipeGrade()
    {
        return $this->hasOne(PipeGrade::class, ['id' => 'pipe_grade_id']);
    }

    public function getProductVariant()
    {
        return $this->hasOne(ProductVariant::class, ['id' => 'product_variant_id']);
    }

    /**
     * VALIDATION FUNCTIONS
     */
    private function productVariant()
    {
        if ($this->productVariant) {
            $product = $this->productVariant->product;
    
            return [
                'id' => intval($this->productVariant->id),
                'name' => $this->productVariant->name,
                'sku' => $this->productVariant->sku,
                'variant_code' => $this->productVariant->variant_code,
                'product' => [
                    'id' => intval($product->id),
                    'name' => $product->name,
                ],
            ];
        }

        return [];
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

    private function cleaningType()
    {
        if (!empty($this->cleaning_type_id) && $this->cleaningType) {
            return [
                'id' => intval($this->cleaningType->id),
                'name' => $this->cleaningType->name,
                'base_price' => floatval($this->cleaningType->base_price),
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

    private function getPricing($installationPackage = null, $pipeGrade = null, $cleaningType = null)
    {
        $installationService = $this->installation_service;
        $servicePrice = 0;
        if (!empty($installationService)) {
            foreach ($installationService as $key => $value) {
                $servicePrice += floatval($value['subtotal']);
            }
        }

        $basePrice = 0;
        if (!empty($installationPackage)) {
            $basePrice = floatval($installationPackage['base_price']);
        }

        $pricePerMeter = 0;
        $pipePrice = 0;
        if (!empty($pipeGrade)) {
            $pricePerMeter = floatval($pipeGrade['price_per_meter']);
            $pipePrice = $pricePerMeter * intval($this->length);
        }

        $cleaningPrice = 0;
        if (!empty($cleaningType)) {
            $cleaningPrice = floatval($cleaningType['base_price']);
        }

        $totalPerUnit = $servicePrice + $basePrice + $pipePrice + $cleaningPrice;
        $grandTotal = $totalPerUnit * intval($this->qty);

        return [
            'base_price' => $basePrice,
            'price_per_meter' => $pricePerMeter,
            'pipe_price' => $pipePrice,
            'total_per_unit' => $totalPerUnit,
            'grand_total' => $grandTotal,
        ];
    }
}