<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use app\helpers\Constants;
use app\core\CoreController;
use app\models\Cart;
use app\models\search\CartSearch;

class CartController extends CoreController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['verbs']['actions'] = ArrayHelper::merge(
            $behaviors['verbs']['actions'],
            [
                'index' => ['get'],
                'data' => ['post'],
                'list' => ['post'],
                'create' => ['post'],
                'delete' => ['post'],
            ]
        );

        return $behaviors;
    }

    public function actionData()
    {
        $params = Yii::$app->getRequest()->getBodyParams();

        $searchModel = new CartSearch();
        $dataProvider = $searchModel->search($params);

        CoreController::validateProvider($dataProvider, $searchModel);

        return CoreController::coreData($dataProvider);
    }

    public function actionList()
    {
        $params = Yii::$app->getRequest()->getBodyParams();

        $params['member_profile_id'] = 1;
        $searchModel = new CartSearch();
        $dataProvider = $searchModel->search($params);

        CoreController::validateProvider($dataProvider, $searchModel);

        return CoreController::coreData($dataProvider);
    }

    public function actionCreate()
    {
        $model = new Cart();
        $params = Yii::$app->getRequest()->getBodyParams();
        $scenario = Constants::SCENARIO_CREATE;

        CoreController::unavailableParams($model, $params);

        $memberProfileId = $params['member_profile_id'] ?? null;
        if (!empty($memberProfileId)) {
		    $existing = CoreController::coreFindModelOne($model, ['member_profile_id' => $memberProfileId], ['status' => [Constants::STATUS_ACTIVE, Constants::STATUS_DRAFT]]);

            if ($existing) {
                $existing->scenario = Constants::SCENARIO_UPDATE;
                
                if ($existing->load($params, '') && $existing->validate()) {
			        // CoreController::emptyParams($existing);

                    if ($existing->save()) {
                        #uncomment below code if you want to insert data to mongodb
				        // Yii::$app->mongodb->upsert($existing);
                        
                        return CoreController::coreSuccess($existing);
                    }
                }

                return CoreController::coreError($existing);
            }
        }

        $model->scenario = $scenario;
        $params['status'] = Constants::STATUS_ACTIVE;

        if ($model->load($params, '') && $model->validate()) {
            if ($model->save()) {
                return CoreController::coreSuccess($model);
            }
        }

        return CoreController::coreError($model);
    }

    public function actionDelete()
    {
        $model = new Cart();
        $params = Yii::$app->getRequest()->getBodyParams();

        CoreController::unavailableParams($model, $params);

        $memberProfileId = $params['member_profile_id'] ?? null;
        if (empty($memberProfileId)) {
            return CoreController::coreBadRequest([], Yii::t('app', 'validationFailed'));
        }

        $existing = Cart::find()
            ->where([
                'member_profile_id' => intval($memberProfileId),
                'status' => [Constants::STATUS_ACTIVE, Constants::STATUS_DRAFT],
            ])
            ->one();

        if (!$existing) {
            return CoreController::coreDataNotFound();
        }

        $existing->scenario = Constants::SCENARIO_DELETE;

        if ($existing->load($params, '') && $existing->validate()) {
            if ($existing->save()) {
                return CoreController::coreSuccess($existing);
            }
        }

        return CoreController::coreError($existing);
    }
}
