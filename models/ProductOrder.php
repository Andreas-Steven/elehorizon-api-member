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
use app\models\reference\ProductVariant;

class ProductOrder extends ActiveRecord
{
    public $qty;
    public $product_variant_id;
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
        // return Constants::OPTIMISTIC_LOCK;
    }

    public function rules()
    {
        return ArrayHelper::merge(
            [ 
                [['note'], 'string', 'max' => 255],
                [['detail_address', 'detail_info'], 'safe'],
                [['id', 'member_profile_id', 'product_variant_id', 'qty'], 'integer'],

                [['member_profile_id', 'product_variant_id', 'qty'], 'required', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE]],
                
                [['member_profile_id', 'product_variant_id', 'qty'], 'filter', 'filter' => 'intval', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE]],
                 [['detail_address'], function ($attribute) {
                    CoreModel::validateAttributeArray($this, $attribute, $this->getAttributeLabel($attribute));
                }],

                [['product_variant_id'], 'validateItems'],
                [['detail_address'], 'validateDetailAddress'],
            ],
            CoreModel::getStatusRules($this),
            // CoreModel::getLockVersionRulesOnly(),
            // CoreModel::getSyncMdbRules(),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['member_profile_id', 'product_variant_id', 'qty', 'items', 'detail_address', 'pricing_summary', 'note', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['member_profile_id', 'product_variant_id', 'qty', 'items', 'detail_address', 'pricing_summary', 'note', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['detail_info', 'status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'member_profile_id' => 'Member Profile ID',
            'product_variant_id' => 'Product Variant ID',
            'qty' => 'Quantity',
            'items' => 'Items',
            'detail_address' => 'Detail Address',
            'pricing_summary' => 'Pricing Summary',
            'note' => 'Note',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\ProductOrderQuery(get_called_class());
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

            $pricingSummary = $this->pricing_summary;
            if (is_string($pricingSummary)) {
                $decoded = json_decode($pricingSummary, true);
                $pricingSummary = is_array($decoded) ? $decoded : [];
            }

            if (empty($pricingSummary)) {
                $dbDefault = Yii::$app->params['dbDefault'] ?? [];
                $this->pricing_summary = [
                    'currency' => $dbDefault['currency'] ?? 'IDR',
                    'subtotal' => 0,
                    'discount' => 0,
                    'shipping' => 0,
                    'grand_total' => 0,
                ];
            }

            return true;
        }

        return false;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->pricing_summary = $this->calculatePricingSummaryFromItems();

            $memberProfile = $this->memberProfile();

            $this->detail_info = [
                'member_profile' => $memberProfile ?? [],
                'change_log' => CoreModel::getChangeLog($this, $insert),
            ];

            return true;
        }

        return false;
    }

    public function calculatePricingSummaryFromItems(): array
    {
        $items = $this->items;
        if (is_string($items)) {
            $decoded = json_decode($items, true);
            $items = is_array($decoded) ? $decoded : [];
        }

        $subtotal = 0;
        if (is_array($items)) {
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $qty = isset($item['qty']) ? (int) $item['qty'] : 0;
                $price = isset($item['price']) ? (float) $item['price'] : 0;
                $subtotal += ($qty * $price);
            }
        }

        $dbDefault = Yii::$app->params['dbDefault'] ?? [];
        $discount = 0;
        $shipping = 0;

        return [
            'currency' => $dbDefault['currency'] ?? 'IDR',
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'grand_total' => ($subtotal - $discount + $shipping),
        ];
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

    public function getMemberProfile()
    {
        return $this->hasOne(MemberProfile::class, ['id' => 'member_profile_id']);
    }

    /**
     * VALIDATION FUNCTIONS
     */
    public function validateItems($attribute)
    {
        $productVariantId = $this->$attribute;
        if (!ctype_digit((string) $productVariantId)) {
            $this->addError($attribute, Yii::t('app', 'integer', ['label' => 'Product Variant ID']));
            throw new ErrorMessage($this, Yii::t('app', 'productOrderFailed'), 422);
        }

        $productVariant = ProductVariant::findOne(['id' => $productVariantId, 'status' => Constants::STATUS_ACTIVE]);
        if (!$productVariant) {
            $this->addError($attribute, Yii::t('app', 'notFound', ['label' => 'Product Variant']));
            throw new ErrorMessage($this, Yii::t('app', 'productOrderFailed'), 422);
        }

        $item[] = [
            'product_variant_id' => intval($productVariant->id),
            'name' => $productVariant->name,
            'sku' => $productVariant->sku,
            'qty' => intval($this->qty),
            'original_price' => floatval($productVariant->original_price),
            'price' => floatval($productVariant->price),
            'product' => $productVariant->detail_info['product'],
        ];
        
        $this->items = $item;
    }

    public function memberProfile(): array
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