<?php

namespace app\models\search;

/**
 * Yii required components
 */
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;

/**
 * Model required components
 */
use app\helpers\Constants;
use app\core\CoreModel;
use app\core\CoreMongodb;
use app\models\MemberProfile;

class MemberProfileSearch extends MemberProfile
{
    public $page;
    public $page_size;
    public $sort_dir;
    public $sort_by;
    public $created_at;
    public $created_by;
    public $updated_at;
    public $updated_by;
    public $deleted_at;
    public $deleted_by;

    public function rules()
    {
        return ArrayHelper::merge(
            [
                [['id'], 'integer'],
                [['name', 'email', 'phone', 'avatar', 'gender', 'birth_date', 'detail_address', 'status', 'detail_info', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by',], 'safe'],
            ],
            CoreModel::getPaginationRules($this)
        );
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $this->load([CoreModel::getModelClassName($this) => $params]);

        if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($this, $params)) {
            return $unavailableParams;
        }

        $query = MemberProfile::find();
        $query->where(Constants::STATUS_NOT_DELETED);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(CoreModel::setLikeFilter($this->name, 'name'));
        $query->andFilterWhere(CoreModel::setLikeFilter($this->email, 'email'));

        $query->andFilterWhere([
            'id' => $this->id,
        ]);

        $query->andFilterWhere(['status' => $this->status ? explode(',', $this->status) : $this->status]);

        $query->andFilterWhere(CoreModel::setChangelogFilters($this));

        $dataProvider->setPagination(
            CoreModel::setPagination($params, $dataProvider)
        );

        $dataProvider->setSort(
            CoreModel::setSort($params)
        );

        return $dataProvider;
    }
}
