<?php

namespace app\models\query;

use yii\db\ActiveQuery;
use app\helpers\Constants;

class InstallationOrderQuery extends ActiveQuery
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

    public function inactive()
    {
        return $this->andWhere(['status' => Constants::STATUS_INACTIVE]);
    }

    public function draft()
    {
        return $this->andWhere(['status' => Constants::STATUS_DRAFT]);
    }

    public function completed()
    {
        return $this->andWhere(['status' => Constants::STATUS_COMPLETED]);
    }

    public function maintenance()
    {
        return $this->andWhere(['status' => Constants::STATUS_MAINTENANCE]);
    }

    public function deleted()
    {
        return $this->andWhere(['status' => Constants::STATUS_DELETED]);
    }
}
