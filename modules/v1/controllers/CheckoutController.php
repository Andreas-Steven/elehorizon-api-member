<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use app\core\CoreController;
use app\core\CoreModel;
use app\helpers\Constants;
use app\models\Cart;
use app\models\Checkout;
use app\models\MemberProfile;
use app\models\reference\Voucher;

class CheckoutController extends CoreController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['verbs']['actions'] = ArrayHelper::merge(
            $behaviors['verbs']['actions'],
            [
                'preview' => ['post'],
                'confirm' => ['post'],
            ]
        );

        return $behaviors;
    }

    public function actionPreview()
    {
        $params = Yii::$app->getRequest()->getBodyParams();

        $memberProfileId = intval($params['member_profile_id'] ?? 0);
        if (empty($memberProfileId)) {
            return CoreController::coreBadRequest([], Yii::t('app', 'validationFailed'));
        }

        $cart = Cart::find()
            ->where([
                'member_profile_id' => $memberProfileId,
                'status' => [Constants::STATUS_ACTIVE, Constants::STATUS_DRAFT],
            ])
            ->one();

        if (!$cart) {
            return CoreController::coreDataNotFound();
        }

        $memberProfile = MemberProfile::findOne(['id' => $memberProfileId, 'status' => Constants::STATUS_ACTIVE]);
        $address = [];
        if ($memberProfile && is_array($memberProfile->detail_address) && isset($memberProfile->detail_address[0]) && is_array($memberProfile->detail_address[0])) {
            $address = $memberProfile->detail_address[0];
        }

        $items = CoreModel::ensureArray($cart->items);
        $selectedItems = $this->filterSelectedCartItems($items, $params);
        if (empty($selectedItems)) {
            return CoreController::coreBadRequest([], Yii::t('app', 'validationFailed'));
        }

        $pricing = $this->calculatePricing($selectedItems, $params['voucher_code'] ?? null);

        $paymentMethods = [
            [
                'code' => 'BANK_TRANSFER_BCA',
                'name' => 'Transfer Bank (BCA)',
            ],
        ];

        $data = [
            'address' => $address,
            'items' => $selectedItems,
            'pricing' => $pricing,
            'payment_methods' => $paymentMethods,
        ];

        return CoreController::coreCustomData($data);
    }

    public function actionConfirm()
    {
        $params = Yii::$app->getRequest()->getBodyParams();

        $memberProfileId = intval($params['member_profile_id'] ?? 0);
        if (empty($memberProfileId)) {
            return CoreController::coreBadRequest([], Yii::t('app', 'validationFailed'));
        }

        $paymentMethod = $params['payment_method'] ?? 'BANK_TRANSFER_BCA';
        if ($paymentMethod !== 'BANK_TRANSFER_BCA') {
            return CoreController::coreBadRequest([], Yii::t('app', 'validationFailed'));
        }

        $cart = Cart::find()
            ->where([
                'member_profile_id' => $memberProfileId,
                'status' => [Constants::STATUS_ACTIVE, Constants::STATUS_DRAFT],
            ])
            ->one();

        if (!$cart) {
            return CoreController::coreDataNotFound();
        }

        $items = CoreModel::ensureArray($cart->items);
        $selectedItems = $this->filterSelectedCartItems($items, $params);
        if (empty($selectedItems)) {
            return CoreController::coreBadRequest([], Yii::t('app', 'validationFailed'));
        }

        $voucherCode = $params['voucher_code'] ?? null;
        $pricing = $this->calculatePricing($selectedItems, $voucherCode);

        $bca = Yii::$app->params['payment']['bca'] ?? [];
        $expiryMinutes = intval(Yii::$app->params['checkout']['expiry_minutes'] ?? 1440);
        $expiredAt = gmdate('Y-m-d H:i:s', time() + ($expiryMinutes * 60));

        $paymentDetail = [
            'method' => 'BANK_TRANSFER_BCA',
            'bank' => $bca['bank'] ?? 'BCA',
            'account_number' => $bca['account_number'] ?? null,
            'account_name' => $bca['account_name'] ?? null,
            'amount' => intval($pricing['grand_total'] ?? 0),
        ];

        $checkout = new Checkout();
        $checkout->scenario = Constants::SCENARIO_CREATE;
        $checkout->status = Constants::STATUS_ACTIVE;
        $checkout->member_profile_id = $memberProfileId;
        $checkout->cart_snapshot = [
            'cart_id' => intval($cart->id),
            'items' => $selectedItems,
        ];
        $checkout->pricing = $pricing;
        $checkout->voucher_code = $voucherCode;
        $checkout->payment_method = 'BANK_TRANSFER_BCA';
        $checkout->payment_detail = $paymentDetail;
        $checkout->payment_status = 'WAITING_PAYMENT';
        $checkout->expired_at = $expiredAt;

        if ($checkout->validate() && $checkout->save()) {
            $data = [
                'checkout_id' => intval($checkout->id),
                'status' => $checkout->payment_status,
                'expired_at' => $checkout->expired_at,
                'payment' => $checkout->payment_detail,
            ];

            return CoreController::coreCustomData($data);
        }

        return CoreController::coreError($checkout);
    }

    private function calculatePricing(array $items, ?string $voucherCode): array
    {
        $shippingCost = intval(Yii::$app->params['checkout']['shipping_cost'] ?? 0);

        $itemsSubtotal = 0;
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $pricingSummary = $item['pricing_summary'] ?? [];
            if (is_array($pricingSummary)) {
                if (array_key_exists('grand_total', $pricingSummary)) {
                    $itemsSubtotal += intval($pricingSummary['grand_total']);
                } elseif (array_key_exists('subtotal', $pricingSummary)) {
                    $itemsSubtotal += intval($pricingSummary['subtotal']);
                }
            }
        }

        $discount = 0;
        $voucher = null;

        if (!empty($voucherCode)) {
            $voucher = Voucher::find()
                ->where(['code' => $voucherCode])
                ->andWhere(Constants::STATUS_NOT_DELETED)
                ->one();

            if ($voucher && intval($voucher->status) === Constants::STATUS_ACTIVE) {
                $now = gmdate('Y-m-d H:i:s');
                $startAt = $voucher->start_at;
                $endAt = $voucher->end_at;

                $eligible = true;
                if (!empty($startAt) && $now < $startAt) {
                    $eligible = false;
                }
                if (!empty($endAt) && $now > $endAt) {
                    $eligible = false;
                }
                if (intval($voucher->quota) > 0 && intval($voucher->used) >= intval($voucher->quota)) {
                    $eligible = false;
                }
                if ($itemsSubtotal < intval($voucher->min_purchase)) {
                    $eligible = false;
                }

                if ($eligible) {
                    if ($voucher->type === 'percent') {
                        $discount = intval(round($itemsSubtotal * (intval($voucher->value) / 100)));
                        $maxDiscount = intval($voucher->max_discount);
                        if ($maxDiscount > 0) {
                            $discount = min($discount, $maxDiscount);
                        }
                    } elseif ($voucher->type === 'fixed') {
                        $discount = intval($voucher->value);
                        $maxDiscount = intval($voucher->max_discount);
                        if ($maxDiscount > 0) {
                            $discount = min($discount, $maxDiscount);
                        }
                    }
                }
            }
        }

        $discount = max(0, min($discount, $itemsSubtotal));
        $grandTotal = $itemsSubtotal + $shippingCost - $discount;

        return [
            'items_subtotal' => $itemsSubtotal,
            'service_subtotal' => 0,
            'shipping_cost' => $shippingCost,
            'discount' => $discount,
            'grand_total' => $grandTotal,
        ];
    }

    private function filterSelectedCartItems(array $items, array $params): array
    {
        $productOrderIds = $this->parseIdList($params['product_order_ids'] ?? null);
        $installationOrderIds = $this->parseIdList($params['installation_order_ids'] ?? null);
        $cleaningOrderIds = $this->parseIdList($params['cleaning_order_ids'] ?? null);

        $hasSelection = !empty($productOrderIds) || !empty($installationOrderIds) || !empty($cleaningOrderIds);
        if (!$hasSelection) {
            return [];
        }

        $filtered = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $itemType = $item['item_type'] ?? null;
            if ($itemType === 'product') {
                $id = intval($item['product_order_id'] ?? 0);
                if ($id && in_array($id, $productOrderIds, true)) {
                    $filtered[] = $item;
                }
            } elseif ($itemType === 'installation') {
                $id = intval($item['installation_order_id'] ?? 0);
                if ($id && in_array($id, $installationOrderIds, true)) {
                    $filtered[] = $item;
                }
            } elseif ($itemType === 'cleaning') {
                $id = intval($item['cleaning_order_id'] ?? 0);
                if ($id && in_array($id, $cleaningOrderIds, true)) {
                    $filtered[] = $item;
                }
            }
        }

        return $filtered;
    }

    private function parseIdList($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $ids = [];
        if (!is_array($value)) {
            return [];
        }

        $ids = $value;

        $ids = array_values(array_unique(array_filter(array_map(function ($v) {
            if ($v === null || $v === '') {
                return null;
            }

            if (is_string($v) && !ctype_digit($v)) {
                return null;
            }

            $i = intval($v);
            return $i > 0 ? $i : null;
        }, $ids))));

        return $ids;
    }
}
