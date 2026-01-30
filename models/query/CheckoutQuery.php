<?php

namespace app\models\query;

use yii\db\ActiveQuery;
use app\helpers\Constants;

class CheckoutQuery extends ActiveQuery
{
    public function all($db = null)
    {
        return parent::all($db);
    }

    public function one($db = null)
    {
        return parent::one($db);
    }

    public function active()
    {
        return $this->andWhere(['status' => Constants::STATUS_ACTIVE]);
    }

    public function draft()
    {
        return $this->andWhere(['status' => Constants::STATUS_DRAFT]);
    }

    public function completed()
    {
        return $this->andWhere(['status' => Constants::STATUS_COMPLETED]);
    }

    public function deleted()
    {
        return $this->andWhere(['status' => Constants::STATUS_DELETED]);
    }
}
