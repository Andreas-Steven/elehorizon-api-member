<?php

namespace app\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use app\core\CoreModel;
use app\models\InstallationOrder;
use app\helpers\Constants;

class InstallationOrderSearch extends InstallationServiceOrder
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
                [['id', 'member_profile_id', 'installation_package_id', 'cleaning_type_id', 'pipe_grade_id', 'qty'], 'integer'],
                [['client_uuid', 'service_type', 'installation_service', 'pricing', 'detail_info', 'detail_address', 'schedule', 'note', 'status', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'], 'safe'],
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

        $query = InstallationServiceOrder::find();
        $query->where(Constants::STATUS_NOT_DELETED);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'member_profile_id' => $this->member_profile_id,
            'installation_package_id' => $this->installation_package_id,
            'pipe_grade_id' => $this->pipe_grade_id,
            'qty' => $this->qty,
        ]);

        $query->andFilterWhere(['status' => $this->status ? explode(',', $this->status) : $this->status]);

        $query->andFilterWhere(CoreModel::setLikeFilter($this->client_uuid, 'client_uuid'));
        $query->andFilterWhere(CoreModel::setLikeFilter($this->service_type, 'service_type'));

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
