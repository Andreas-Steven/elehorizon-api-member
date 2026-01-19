<?php

namespace app\models\query;

/**
 * Yii required components
 */
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * Model required components
 */
use app\helpers\Constants;

/**
 * This is the ActiveQuery class for [[\app\models\Example]].
 *
 * @see \app\models\Example
 */
class ExampleQuery extends ActiveQuery
{
    /**
     * {@inheritdoc}
     * @return \app\models\Example[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return \app\models\Example|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * Filter by active status
     * @return ExampleQuery
     */
    public function active()
    {
        return $this->andWhere(['status' => Constants::STATUS_ACTIVE]);
    }

    /**
     * Filter by inactive status
     * @return ExampleQuery
     */
    public function inactive()
    {
        return $this->andWhere(['status' => Constants::STATUS_INACTIVE]);
    }

    /**
     * Filter by draft status
     * @return ExampleQuery
     */
    public function draft()
    {
        return $this->andWhere(['status' => Constants::STATUS_DRAFT]);
    }

    /**
     * Filter by completed status
     * @return ExampleQuery
     */
    public function completed()
    {
        return $this->andWhere(['status' => Constants::STATUS_COMPLETED]);
    }

    /**
     * Filter by maintenance status
     * @return ExampleQuery
     */
    public function maintenance()
    {
        return $this->andWhere(['status' => Constants::STATUS_MAINTENANCE]);
    }

    /**
     * Filter by deleted status
     * @return ExampleQuery
     */
    public function deleted()
    {
        return $this->andWhere(['status' => Constants::STATUS_DELETED]);
    }
}