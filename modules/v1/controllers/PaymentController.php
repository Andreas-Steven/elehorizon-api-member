<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use app\core\CoreController;
use app\helpers\Constants;
use app\models\Checkout;

class PaymentController extends CoreController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['verbs']['actions'] = ArrayHelper::merge(
            $behaviors['verbs']['actions'],
            [
                'detail' => ['get'],
                'check' => ['post'],
            ]
        );

        return $behaviors;
    }

    public function actionDetail($checkout_id = null)
    {
        $checkoutId = intval($checkout_id ?? Yii::$app->request->get('checkout_id'));
        if (empty($checkoutId)) {
            return CoreController::coreBadRequest([], Yii::t('app', 'validationFailed'));
        }

        $checkout = Checkout::find()
            ->where(['id' => $checkoutId])
            ->andWhere(Constants::STATUS_NOT_DELETED)
            ->one();

        if (!$checkout) {
            return CoreController::coreDataNotFound();
        }

        $data = [
            'checkout_id' => intval($checkout->id),
            'created_at' => $checkout->detail_info['change_log']['created_at'] ?? null,
            'expired_at' => $checkout->expired_at,
            'status' => $this->resolvePaymentStatus($checkout),
            'payment' => $checkout->payment_detail,
        ];

        return CoreController::coreCustomData($data);
    }

    public function actionCheck()
    {
        $params = Yii::$app->getRequest()->getBodyParams();

        $checkoutId = intval($params['checkout_id'] ?? 0);
        if (empty($checkoutId)) {
            return CoreController::coreBadRequest([], Yii::t('app', 'validationFailed'));
        }

        $checkout = Checkout::find()
            ->where(['id' => $checkoutId])
            ->andWhere(Constants::STATUS_NOT_DELETED)
            ->one();

        if (!$checkout) {
            return CoreController::coreDataNotFound();
        }

        $data = [
            'checkout_id' => intval($checkout->id),
            'status' => $this->resolvePaymentStatus($checkout),
            'expired_at' => $checkout->expired_at,
        ];

        return CoreController::coreCustomData($data);
    }

    private function resolvePaymentStatus(Checkout $checkout): string
    {
        $status = $checkout->payment_status ?? 'WAITING_PAYMENT';

        if ($status === 'WAITING_PAYMENT' && !empty($checkout->expired_at)) {
            $expiredAt = strtotime($checkout->expired_at . ' UTC');
            if ($expiredAt !== false && time() > $expiredAt) {
                return 'EXPIRED';
            }
        }

        return $status;
    }
}
