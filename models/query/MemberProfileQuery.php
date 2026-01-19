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
 * This is the ActiveQuery class for [[\app\models\MemberProfile]].
 *
 * @see \app\models\MemberProfile
 */
class MemberProfileQuery extends ActiveQuery
{
    /**
     * {@inheritdoc}
     * @return \app\models\MemberProfile[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return \app\models\MemberProfile|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * Filter by active status
     * @return MemberProfileQuery
     */
    public function active()
    {
        return $this->andWhere(['status' => Constants::STATUS_ACTIVE]);
    }

    /**
     * Filter by inactive status
     * @return MemberProfileQuery
     */
    public function inactive()
    {
        return $this->andWhere(['status' => Constants::STATUS_INACTIVE]);
    }

    /**
     * Filter by draft status
     * @return MemberProfileQuery
     */
    public function draft()
    {
        return $this->andWhere(['status' => Constants::STATUS_DRAFT]);
    }

    /**
     * Filter by completed status
     * @return MemberProfileQuery
     */
    public function completed()
    {
        return $this->andWhere(['status' => Constants::STATUS_COMPLETED]);
    }

    /**
     * Filter by maintenance status
     * @return MemberProfileQuery
     */
    public function maintenance()
    {
        return $this->andWhere(['status' => Constants::STATUS_MAINTENANCE]);
    }

    /**
     * Filter by deleted status
     * @return MemberProfileQuery
     */
    public function deleted()
    {
        return $this->andWhere(['status' => Constants::STATUS_DELETED]);
    }
}