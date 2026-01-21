<?php

namespace app\modules\v1\controllers;

/**
 * Yii required components
 */
use Yii;
use yii\web\Response;
use yii\rest\Controller;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use app\helpers\Constants;
use app\core\CoreController;

/**
 * Model required components
 */
use app\models\ServiceOrder;
use app\models\search\ServiceOrderSearch;

class ServiceOrderController extends CoreController
{
	public function behaviors()
    {
		$behaviors = parent::behaviors();

		#add your action here
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

		$searchModel = new ServiceOrderSearch();
		$dataProvider = $searchModel->search($params);

		CoreController::validateProvider($dataProvider, $searchModel);

		return CoreController::coreData($dataProvider);
	}

    public function actionList()
	{
		$params = Yii::$app->getRequest()->getBodyParams();
        $params['member_id'] = 1;

		$searchModel = new ServiceOrderSearch();
		$dataProvider = $searchModel->search($params);

		CoreController::validateProvider($dataProvider, $searchModel);

		return CoreController::coreData($dataProvider);
	}

    public function actionCreate()
	{
		$model = new ServiceOrder();
		$params = Yii::$app->getRequest()->getBodyParams();
		$scenario = Constants::SCENARIO_CREATE;

        CoreController::unavailableParams($model, $params);

		$model->scenario = $scenario;
		$params['status'] = Constants::STATUS_ACTIVE;

		if ($model->load($params, '') && $model->validate()) {
			if ($model->save()) {
				#uncomment below code if you want to insert data to mongodb
				// Yii::$app->mongodb->upsert($model);

				return CoreController::coreSuccess($model);
			}
		}

		return CoreController::coreError($model);
	}
}
