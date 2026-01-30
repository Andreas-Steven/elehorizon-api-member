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
use app\core\CoreController;
use app\helpers\Constants;
use app\exceptions\ErrorMessage;
use app\models\ProductOrder;
use app\models\CleaningOrder;
use app\models\InstallationOrder;

class Cart extends ActiveRecord
{
    public $memberData;
    public $product_order_id;
    public $cleaning_order_id;
    public $installation_order_id;
    public static $connection = 'db';

    public static function tableName()
    {
        return 'cart';
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
                [['items', 'note', 'detail_info'], 'safe'],
                [['product_order_id', 'installation_order_id', 'cleaning_order_id'], 'safe', 'on' => [Constants::SCENARIO_DELETE]],
                [['member_profile_id'], 'integer'],
                [['product_order_id', 'installation_order_id', 'cleaning_order_id'], 'integer', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE]],

                [['member_profile_id'], 'required', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE]],

                [['member_profile_id'], 'validateMember'],
            ],
            CoreModel::getStatusRules($this),
            // CoreModel::getLockVersionRulesOnly(),
            // CoreModel::getSyncMdbRules(),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['member_profile_id', 'product_order_id', 'installation_order_id', 'cleaning_order_id', 'items', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['member_profile_id', 'product_order_id', 'installation_order_id', 'cleaning_order_id', 'items', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['member_profile_id', 'product_order_id', 'installation_order_id', 'cleaning_order_id', 'items', 'status', 'detail_info'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'member_profile_id' => 'Member Profile ID',
            'product_order_id' => 'Product Order ID',
            'installation_order_id' => 'Installation Order ID',
            'cleaning_order_id' => 'Cleaning Order ID',
            'items' => 'Items',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\CartQuery(get_called_class());
    }

    public function fields()
    {
        $fields = parent::fields();

        return $fields;
    }

    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            if (empty($this->product_order_id) && empty($this->installation_order_id) && empty($this->cleaning_order_id)) {
                $this->addError('Cart', Yii::t('app', 'cartFailed'));
                return false;
            }

            return true;
        }

        return false;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->scenario === Constants::SCENARIO_DELETE) {
                $items = CoreModel::ensureArray($this->items);

                $productIds = is_array($this->product_order_id)
                    ? array_map('intval', $this->product_order_id)
                    : (!empty($this->product_order_id) ? [intval($this->product_order_id)] : []);

                $installationIds = is_array($this->installation_order_id)
                    ? array_map('intval', $this->installation_order_id)
                    : (!empty($this->installation_order_id) ? [intval($this->installation_order_id)] : []);

                $cleaningIds = is_array($this->cleaning_order_id)
                    ? array_map('intval', $this->cleaning_order_id)
                    : (!empty($this->cleaning_order_id) ? [intval($this->cleaning_order_id)] : []);

                $productIds = array_values(array_unique(array_filter($productIds)));
                $installationIds = array_values(array_unique(array_filter($installationIds)));
                $cleaningIds = array_values(array_unique(array_filter($cleaningIds)));

                $productMap = !empty($productIds) ? array_flip($productIds) : [];
                $installationMap = !empty($installationIds) ? array_flip($installationIds) : [];
                $cleaningMap = !empty($cleaningIds) ? array_flip($cleaningIds) : [];

                $this->items = array_values(array_filter($items, function ($item) use ($productMap, $installationMap, $cleaningMap) {
                    if (!is_array($item)) {
                        return true;
                    }

                    if (!empty($cleaningMap) && array_key_exists('cleaning_order_id', $item) && array_key_exists(intval($item['cleaning_order_id']), $cleaningMap)) {
                        return false;
                    }
                    if (!empty($installationMap) && array_key_exists('installation_order_id', $item) && array_key_exists(intval($item['installation_order_id']), $installationMap)) {
                        return false;
                    }
                    if (!empty($productMap) && array_key_exists('product_order_id', $item) && array_key_exists(intval($item['product_order_id']), $productMap)) {
                        return false;
                    }

                    return true;
                }));

                $this->detail_info = [
                    'member_profile' => $this->memberData ?? [],
                    'change_log' => CoreModel::getChangeLog($this, $insert),
                ];

                return true;
            }

            if ($this->scenario === Constants::SCENARIO_CREATE || $this->scenario === Constants::SCENARIO_UPDATE) {
                if (!empty($this->cleaning_order_id)) {
                    $this->items = $this->removeItemsByKeyValue(CoreModel::ensureArray($this->items), 'cleaning_order_id', intval($this->cleaning_order_id));
                    $this->validateCleaning('cleaning_order_id');
                }
    
                if (!empty($this->installation_order_id)) {
                    $this->items = $this->removeItemsByKeyValue(CoreModel::ensureArray($this->items), 'installation_order_id', intval($this->installation_order_id));
                    $this->validateInstallation('installation_order_id');
                }
    
                if (!empty($this->product_order_id)) {
                    $this->items = $this->removeItemsByKeyValue(CoreModel::ensureArray($this->items), 'product_order_id', intval($this->product_order_id));
                    $this->validateProduct('product_order_id');
                }
            }
            
            $this->detail_info = [
                'member_profile' => $this->memberData ?? [],
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

    public function validateMember($attribute)
    {
        if (is_string($this->$attribute) && !ctype_digit($this->$attribute))
        {
            $this->addError($attribute, Yii::t('app', 'integer', ['label' => 'Member Profile ID']));
            return false;
        }
        
        $memberProfileId = intval($this->$attribute);
        $memberProfile = MemberProfile::findOne(['id' => $memberProfileId, 'status' => Constants::STATUS_ACTIVE]);
        if (!$memberProfile) {
            $this->addError($attribute, Yii::t('app', 'fieldDataNotFound', ['label' => 'Member Profile']));
            return false;
        }

        $this->$attribute = $memberProfileId;
        $this->memberData = [
            'id' => intval($memberProfile->id),
            'name' => $memberProfile->name,
        ];
    }

    public function validateProduct($attribute)
    {
        $productOrder = ProductOrder::findOne(['id' => intval($this->$attribute), 'member_profile_id' => intval($this->member_profile_id), 'status' => Constants::STATUS_ACTIVE]);
        if (!$productOrder) {
            $this->addError($attribute, Yii::t('app', 'fieldDataNotFound', ['label' => 'Product Order']));
            return false;
        }

        $productOrderItem = null;
        if (is_array($productOrder->items) && isset($productOrder->items[0]) && is_array($productOrder->items[0])) {
            $productOrderItem = $productOrder->items[0];
        }

        if ($productOrderItem === null) {
            $this->addError($attribute, Yii::t('app', 'fieldDataNotFound', ['label' => 'Product Order Item']));
            return false;
        }

        $item = [];
        $item['item_type'] = 'product';
        $item['product_order_id'] = intval($productOrder->id);
        $item['product_order'] = [
            'id' => intval($productOrder->id),
            'product_variant_id' => intval($productOrderItem['product_variant_id']),
            'name' => $productOrderItem['name'],
            'sku' => $productOrderItem['sku'],
        ];
        $item['qty'] = intval($productOrderItem['qty']);
        $item['pricing_summary'] = [
            'subtotal' => intval($productOrder->pricing_summary['subtotal']),
            'grand_total' => intval($productOrder->pricing_summary['grand_total']),
        ];
        $item['note'] = $productOrder->note;

        $items = CoreModel::ensureArray($this->items);
        $items[] = $item;
        $this->items = $items;
    }

    public function validateCleaning($attribute)
    {
        $cleaningOrder = CleaningOrder::findOne(['id' => intval($this->$attribute), 'member_profile_id' => intval($this->member_profile_id), 'status' => Constants::STATUS_ACTIVE]);
        if (!$cleaningOrder) {
            $this->addError($attribute, Yii::t('app', 'fieldDataNotFound', ['label' => 'Cleaning Order']));
            return false;
        }

        $schedule = $cleaningOrder->schedule;
        $category = $cleaningOrder->detail_info['category'];
        $serviceType = $cleaningOrder->detail_info['service_type'];
        $cleaningType = $cleaningOrder->detail_info['cleaning_type'];
        $timeKey = array_key_exists('time_slot', $schedule) ? 'time_slot' : (array_key_exists('time', $schedule) ? 'time' : null);

        $subtotal = null;
        if (is_array($cleaningOrder->pricing)) {
            if (array_key_exists('total_per_unit', $cleaningOrder->pricing)) {
                $subtotal = intval($cleaningOrder->pricing['total_per_unit']);
            } elseif (array_key_exists('grand_total', $cleaningOrder->pricing)) {
                $subtotal = intval($cleaningOrder->pricing['grand_total']);
            }
        }

        $grandTotal = is_array($cleaningOrder->pricing) && array_key_exists('grand_total', $cleaningOrder->pricing)
            ? intval($cleaningOrder->pricing['grand_total'])
            : $subtotal;

        $item = [];
        $item['item_type'] = 'cleaning';
        $item['cleaning_order_id'] = intval($cleaningOrder->id);
        $item['service_type'] = [
            'id' => intval($serviceType['id']),
            'name' => $serviceType['name'],
        ];
        $item['category'] = [
            'id' => intval($category['id']),
            'name' => $category['name'],
        ];
        $item['cleaning_type'] = [
            'id' => intval($cleaningType['id']),
            'name' => $cleaningType['name'],
        ];
        $item['schedule'] = [
            'date' => $schedule['date'],
            'time_slot' => $schedule[$timeKey],
        ];
        $item['detail_address'] = $cleaningOrder->detail_address;
        $item['pricing_summary'] = [
            'subtotal' => $subtotal,
            'grand_total' => $grandTotal,
        ];
        $item['qty'] = intval($cleaningOrder->qty);
        $item['note'] = $cleaningOrder->note;

        $items = CoreModel::ensureArray($this->items);
        $items[] = $item;
        $this->items = $items;
    }

    public function validateInstallation($attribute)
    {
        $installationOrder = InstallationOrder::findOne(['id' => intval($this->$attribute), 'member_profile_id' => intval($this->member_profile_id), 'status' => Constants::STATUS_ACTIVE]);
        if (!$installationOrder) {
            $this->addError($attribute, Yii::t('app', 'fieldDataNotFound', ['label' => 'Installation Order']));
            return false;
        }

        $schedule = $installationOrder->schedule;
        $pipeGrade = $installationOrder->detail_info['pipe_grade'];
        $serviceType = $installationOrder->detail_info['service_type'];
        $installationPackage = $installationOrder->detail_info['installation_package'];
        $timeKey = array_key_exists('time_slot', $schedule) ? 'time_slot' : (array_key_exists('time', $schedule) ? 'time' : null);

        $subtotal = null;
        if (is_array($installationOrder->pricing)) {
            if (array_key_exists('total_per_unit', $installationOrder->pricing)) {
                $subtotal = intval($installationOrder->pricing['total_per_unit']);
            } elseif (array_key_exists('grand_total', $installationOrder->pricing)) {
                $subtotal = intval($installationOrder->pricing['grand_total']);
            }
        }

        $grandTotal = is_array($installationOrder->pricing) && array_key_exists('grand_total', $installationOrder->pricing)
            ? intval($installationOrder->pricing['grand_total'])
            : $subtotal;

        $item = [];
        $item['item_type'] = 'installation';
        $item['installation_order_id'] = intval($installationOrder->id);
        $item['service_type'] = [
            'id' => intval($serviceType['id']),
            'name' => $serviceType['name'],
        ];
        $item['installation_package'] = [
            'id' => intval($installationPackage['id']),
            'code' => $installationPackage['code'],
            'name' => $installationPackage['name'],
        ];
        $item['pipe_grade'] = [
            'id' => intval($pipeGrade['id']),
            'name' => $pipeGrade['name'],
        ];
        $item['schedule'] = [
            'date' => $schedule['date'],
            'time_slot' => $schedule[$timeKey],
        ];
        $item['detail_address'] = $installationOrder->detail_address;
        $item['pricing_summary'] = [
            'subtotal' => $subtotal,
            'grand_total' => $grandTotal,
        ];
        $item['qty'] = intval($installationOrder->qty);
        $item['note'] = $installationOrder->note;

        $items = CoreModel::ensureArray($this->items);
        $items[] = $item;
        $this->items = $items;
    }

    public function removeItemsByKeyValue(array $items, string $key, int $id): array
    {
        $filtered = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                $filtered[] = $item;
                continue;
            }

            if (!array_key_exists($key, $item) || intval($item[$key]) !== $id) {
                $filtered[] = $item;
            }
        }

        return $filtered;
    }
}