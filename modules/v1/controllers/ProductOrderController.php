<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use app\helpers\Constants;
use app\core\CoreController;
use app\models\ProductOrder;
use app\models\search\ProductOrderSearch;

class ProductOrderController extends CoreController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['verbs']['actions'] = ArrayHelper::merge(
            $behaviors['verbs']['actions'],
            [
                'index' => ['get'],
            ]
        );

        return $behaviors;
    }

    public function actionData()
    {
        $params = Yii::$app->getRequest()->getBodyParams();

        $searchModel = new ProductOrderSearch();
        $dataProvider = $searchModel->search($params);

        CoreController::validateProvider($dataProvider, $searchModel);

        return CoreController::coreData($dataProvider);
    }

    public function actionList()
    {
        $params = Yii::$app->getRequest()->getBodyParams();

        $params['member_profile_id'] = 1;
        $searchModel = new ProductOrderSearch();
        $dataProvider = $searchModel->search($params);

        CoreController::validateProvider($dataProvider, $searchModel);

        return CoreController::coreData($dataProvider);
    }

    public function actionCreate()
    {
        $model = new ProductOrder();
        $params = Yii::$app->getRequest()->getBodyParams();
        $scenario = Constants::SCENARIO_CREATE;

        CoreController::unavailableParams($model, $params);

        $model->scenario = $scenario;
        $params['status'] = Constants::STATUS_ACTIVE;

        if ($model->load($params, '') && $model->validate()) {
            if ($model->save()) {
                return CoreController::coreSuccess($model);
            }
        }

        return CoreController::coreError($model);
    }

    public function actionUpdate()
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $scenario = Constants::SCENARIO_UPDATE;

        CoreController::validateParams($params, $scenario);

        $model = CoreController::coreFindModelOne(new ProductOrder(), $params);

        if ($model === null) {
            return CoreController::coreDataNotFound();
        }

        CoreController::unavailableParams($model, $params);

        $model->scenario = $scenario;

        if ($superadmin = CoreController::superadmin($params)) {
            return $superadmin;
        }

        if ($model->load($params, '') && $model->validate()) {
            CoreController::emptyParams($model, $scenario);

            if ($model->save()) {
                return CoreController::coreSuccess($model);
            }
        }

        return CoreController::coreError($model);
    }

    public function actionDelete()
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $scenario = Constants::SCENARIO_DELETE;

        CoreController::validateParams($params, $scenario);

        $model = CoreController::coreFindModelOne(new ProductOrder(), $params);

        if ($model === null) {
            return CoreController::coreDataNotFound();
        }

        $model->scenario = $scenario;
        $params['status'] = Constants::STATUS_DELETED;

        if ($superadmin = CoreController::superadmin($params)) {
            return $superadmin;
        }

        if ($model->load($params, '') && $model->validate()) {
            CoreController::emptyParams($model, $scenario);

            if ($model->save()) {
                return CoreController::coreSuccess($model);
            }
        }

        return CoreController::coreError($model);
    }
}
