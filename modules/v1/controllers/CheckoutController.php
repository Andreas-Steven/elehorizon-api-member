<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\base\DynamicModel;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use app\core\CoreController;
use app\helpers\Constants;
use app\models\MemberCheckoutQuote;

class CheckoutController extends CoreController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['verbs']['actions'] = ArrayHelper::merge(
            $behaviors['verbs']['actions'],
            [
                'preview' => ['post'],
                'view' => ['post'],
                'list' => ['post'],
            ]
        );

        $behaviors['authenticator']['except'] = ArrayHelper::merge(
            $behaviors['authenticator']['except'],
            [
                'preview',
                'view',
                'list',
            ]
        );

        return $behaviors;
    }

    public function actionView()
    {
        $params = Yii::$app->getRequest()->getBodyParams();

        $payload = [
            'id' => $params['id'] ?? null,
            'client_uuid' => $params['client_uuid'] ?? null,
        ];

        $model = DynamicModel::validateData($payload, [
            [['id'], 'required'],
            [['id'], 'integer'],
            [['client_uuid'], 'string'],
        ]);

        if ($model->hasErrors()) {
            return $this->coreBadRequest($model);
        }

        $query = MemberCheckoutQuote::find()
            ->where(['id' => (int) $model->id])
            ->andWhere(['member_id' => 1])
            ->andWhere(['<>', 'status', Constants::STATUS_DELETED]);

        if ($model->client_uuid !== null && $model->client_uuid !== '') {
            $query->andWhere(['client_uuid' => $model->client_uuid]);
        }

        $quote = $query->one();
        if ($quote === null) {
            return $this->coreDataNotFound();
        }

        return $this->coreSuccess($quote);
    }

    public function actionList()
    {
        $params = Yii::$app->getRequest()->getBodyParams();

        $payload = [
            'client_uuid' => $params['client_uuid'] ?? null,
            'status' => $params['status'] ?? null,
            'service_type' => $params['service_type'] ?? null,
            'page' => $params['page'] ?? 1,
            'pageSize' => $params['pageSize'] ?? null,
        ];

        $model = DynamicModel::validateData($payload, [
            [['client_uuid', 'service_type'], 'string'],
            [['status', 'page', 'pageSize'], 'integer'],
        ]);

        if ($model->page !== null && (int) $model->page < 1) {
            $model->addError('page', Yii::t('app', 'badRequest'));
        }

        if ($model->pageSize !== null && (int) $model->pageSize < 1) {
            $model->addError('pageSize', Yii::t('app', 'badRequest'));
        }

        if ($model->hasErrors()) {
            return $this->coreBadRequest($model);
        }

        $query = MemberCheckoutQuote::find()
            ->where(['member_id' => 1])
            ->andWhere(['<>', 'status', Constants::STATUS_DELETED])
            ->orderBy(['id' => SORT_DESC]);

        if ($model->client_uuid !== null && $model->client_uuid !== '') {
            $query->andWhere(['client_uuid' => $model->client_uuid]);
        }

        if ($model->service_type !== null && $model->service_type !== '') {
            $query->andWhere(['service_type' => $model->service_type]);
        }

        if ($model->status !== null && $model->status !== '') {
            $query->andWhere(['status' => (int) $model->status]);
        }

        $pageSize = $model->pageSize !== null ? (int) $model->pageSize : (Yii::$app->params['pagination']['pageSize'] ?? 10);
        $page = (int) $model->page;

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
                'page' => max($page - 1, 0),
            ],
        ]);

        return $this->coreData($dataProvider);
    }

    public function actionPreview()
    {
        $params = Yii::$app->getRequest()->getBodyParams();

        $payload = [
            'installation_package_id' => $params['installation_package_id'] ?? null,
            'installation_package_code' => $params['installation_package_code'] ?? null,
            'pipe_grade_id' => $params['pipe_grade_id'] ?? null,
            'length' => $params['length'] ?? null,
            'qty' => $params['qty'] ?? 1,
            'client_uuid' => $params['client_uuid'] ?? null,
        ];

        $model = DynamicModel::validateData($payload, [
            [['installation_package_id', 'pipe_grade_id', 'qty'], 'integer'],
            [['installation_package_code', 'client_uuid'], 'string'],
            [['length'], 'number'],
            [['qty'], 'default', 'value' => 1],
        ]);

        $hasPackageId = $model->installation_package_id !== null && $model->installation_package_id !== '';
        $hasPackageCode = $model->installation_package_code !== null && $model->installation_package_code !== '';

        if (!$hasPackageId && !$hasPackageCode) {
            $model->addError('installation_package_id', Yii::t('app', 'required', ['label' => 'installation_package_id']));
        }

        if ($model->qty !== null && (int) $model->qty < 1) {
            $model->addError('qty', Yii::t('app', 'badRequest'));
        }

        if ($model->hasErrors()) {
            return $this->coreBadRequest($model);
        }

        $packageQuery = Yii::$app->db->createCommand(
            'SELECT id, code, name, description, length, package_price, included_items, status FROM pipe_installation WHERE status <> :deleted',
            [':deleted' => Constants::STATUS_DELETED]
        );

        if ($hasPackageId) {
            $packageQuery = Yii::$app->db->createCommand(
                'SELECT id, code, name, description, length, package_price, included_items, status FROM pipe_installation WHERE id = :id AND status <> :deleted',
                [
                    ':id' => (int) $model->installation_package_id,
                    ':deleted' => Constants::STATUS_DELETED,
                ]
            );
        }

        if ($hasPackageCode) {
            $packageQuery = Yii::$app->db->createCommand(
                'SELECT id, code, name, description, length, package_price, included_items, status FROM pipe_installation WHERE code = :code AND status <> :deleted',
                [
                    ':code' => $model->installation_package_code,
                    ':deleted' => Constants::STATUS_DELETED,
                ]
            );
        }

        $package = $packageQuery->queryOne();
        if (!$package) {
            return $this->coreDataNotFound();
        }

        $grade = null;
        $pricePerMeter = 0.0;

        if ($model->pipe_grade_id !== null && $model->pipe_grade_id !== '') {
            $grade = Yii::$app->db->createCommand(
                'SELECT g.id, g.name, g.thickness_mm, g.price_per_meter, g.status, b.id AS brand_id, b.name AS brand_name
                 FROM pipe_grade g
                 LEFT JOIN brand b ON b.id = g.brand_id
                 WHERE g.id = :id AND g.status <> :deleted',
                [
                    ':id' => (int) $model->pipe_grade_id,
                    ':deleted' => Constants::STATUS_DELETED,
                ]
            )->queryOne();

            if (!$grade) {
                return $this->coreDataNotFound();
            }

            $pricePerMeter = (float) ($grade['price_per_meter'] ?? 0);
        }

        $length = $package['length'] ?? null;
        if ($length === null || $length === '') {
            $length = $model->length;
        }
        $length = $length !== null && $length !== '' ? (float) $length : null;

        if ($length === null) {
            $model->addError('length', Yii::t('app', 'required', ['label' => 'length']));
            return $this->coreBadRequest($model);
        }

        $includedItems = [];
        if (isset($package['included_items'])) {
            if (is_array($package['included_items'])) {
                $includedItems = $package['included_items'];
            } elseif (is_string($package['included_items'])) {
                $decoded = json_decode($package['included_items'], true);
                $includedItems = is_array($decoded) ? $decoded : [];
            }
        }

        $basePrice = (float) ($package['package_price'] ?? 0);
        $pipePrice = $pricePerMeter * $length;
        $totalPerUnit = $basePrice + $pipePrice;
        $qty = (int) $model->qty;

        $summary = [
            'package' => [
                'id' => (int) $package['id'],
                'code' => $package['code'] ?? null,
                'name' => $package['name'] ?? null,
                'description' => $package['description'] ?? null,
                'included_items' => $includedItems,
                'length' => $length,
                'base_price' => $basePrice,
            ],
            'pipe_grade' => $grade ? [
                'id' => (int) $grade['id'],
                'name' => $grade['name'] ?? null,
                'thickness_mm' => isset($grade['thickness_mm']) ? (float) $grade['thickness_mm'] : null,
                'brand' => isset($grade['brand_id']) ? [
                    'id' => $grade['brand_id'] !== null ? (int) $grade['brand_id'] : null,
                    'name' => $grade['brand_name'] ?? null,
                ] : null,
                'price_per_meter' => $pricePerMeter,
            ] : null,
            'qty' => $qty,
            'pricing' => [
                'base_price' => $basePrice,
                'price_per_meter' => $pricePerMeter,
                'pipe_price' => $pipePrice,
                'total_per_unit' => $totalPerUnit,
                'grand_total' => $totalPerUnit * $qty,
            ],
        ];

        $quote = new MemberCheckoutQuote();
        $quote->scenario = Constants::SCENARIO_CREATE;

        $quoteParams = [
            'member_id' => 1,
            'client_uuid' => $model->client_uuid,
            'service_type' => 'installation',
            'installation_package_id' => (int) $package['id'],
            'installation_package_code' => $package['code'] ?? null,
            'pipe_grade_id' => $grade ? (int) $grade['id'] : null,
            'length' => $length,
            'qty' => $qty,
            'pricing' => $summary['pricing'],
            'payload' => [
                'request' => $payload,
                'summary' => $summary,
            ],
            'status' => Constants::STATUS_ACTIVE,
        ];

        if ($quote->load($quoteParams, '') && $quote->validate()) {
            if ($quote->save()) {
                $summary['quote_id'] = (int) $quote->id;
                return $this->coreCustomData($summary);
            }
        }

        return $this->coreError($quote);
    }
}
