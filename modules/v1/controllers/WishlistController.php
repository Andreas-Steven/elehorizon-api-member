<?php

namespace app\modules\v1\controllers;

/**
 * Yii required components
 */
use Yii;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use app\helpers\Constants;
use app\core\CoreController;

/**
 * Model required components
 */
use app\models\Wishlist;
use app\models\search\WishlistSearch;

class WishlistController extends CoreController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['verbs']['actions'] = ArrayHelper::merge(
            $behaviors['verbs']['actions'],
            [
                'index' => ['get'],
                'list' => ['post'],
                'toggle' => ['post'],
            ]
        );

        return $behaviors;
    }

    public function actionData()
    {
        $params = Yii::$app->getRequest()->getBodyParams();

        $searchModel = new WishlistSearch();
        $dataProvider = $searchModel->search($params);

        CoreController::validateProvider($dataProvider, $searchModel);

        return CoreController::coreData($dataProvider);
    }

    public function actionList()
    {
        $params = Yii::$app->getRequest()->getBodyParams();

        $params['member_profile_id'] = 1;
        $searchModel = new WishlistSearch();
        $dataProvider = $searchModel->search($params);

        CoreController::validateProvider($dataProvider, $searchModel);

        return CoreController::coreData($dataProvider);
    }

    public function actionToggle()
    {
        $model = new Wishlist();
        $params = Yii::$app->getRequest()->getBodyParams();
        $scenario = Constants::SCENARIO_CREATE;

        CoreController::unavailableParams($model, $params);

        $productVariantId = $params['product_variant_id'] ?? null;
        $memberProfileId = $params['member_profile_id'] ?? null;

        if (!empty($productVariantId) && !empty($memberProfileId)) {
		    $existing = CoreController::coreFindModelOne($model, $params, ['status' => [Constants::STATUS_ACTIVE, Constants::STATUS_DRAFT]]);

            if ($existing) {
                $existing->scenario = Constants::SCENARIO_DELETE;
                $existing->status = Constants::STATUS_DELETED;
                
                if ($model->load($params, '') && $model->validate()) {
			        CoreController::emptyParams($model);

                    if ($existing->save()) {
                        #uncomment below code if you want to insert data to mongodb
				        // Yii::$app->mongodb->upsert($model);
                        
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
}
