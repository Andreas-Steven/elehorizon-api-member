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
use app\models\Example;
use app\core\CoreMongodb;

/**
 * ExampleSearch represents the model behind the search form of `app\models\Example`.
 */
class ExampleSearch extends Example
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
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge(
            [
                [['id', 'status'], 'integer'],
                [['name'], 'safe'],
            ], 
            CoreModel::getPaginationRules($this)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $this->load([CoreModel::getModelClassName($this) => $params]);

        if ($unavailableParams = Yii::$app->coreAPI::unavailableParams($this, $params)) {
            return $unavailableParams;
        }

        $query = Example::find();
        $query->where(Constants::STATUS_NOT_DELETED);

        /**
         * Membuat query untuk mengambil data dari tabel `example`
         * yang di-join dengan relasi `other_table`.
         *
         * - Alias tabel `example` adalah `ex`
         * - Alias tabel `other_table` adalah `ot`
         * - Kolom yang diambil: ex.id, ex.name, ot.id, ot.name
         * - Filter: hanya data dengan ot.id = 1
         *
         * @var \yii\db\ActiveQuery $query
         */
        // $query = Example::find()
        //     ->alias('ex')
        //     ->joinWith('other_table AS ot') // join langsung ke tabel relasi
        //     ->select([
        //         'ex.id', 'ex.name', 'ot.id', 'ot.name'])
        //     ->where([
        //         'ot.id' => 1,
        //     ]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // add conditions that should always apply here
        // grid filtering conditions
        $query->andFilterWhere(CoreModel::setLikeFilter($this->name, 'name'));

        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(CoreModel::setChangelogFilters($this));

        $dataProvider->setPagination(
            CoreModel::setPagination($params, $dataProvider)
        );

        $dataProvider->setSort(
            CoreModel::setSort($params)
        );

        return $dataProvider;
    }

    public function mongodbSearch($params) 
    {
        $this->load([CoreMongodb::getModelClassName($this) => $params]);

        if ($unavailableParams = Yii::$app->coreAPI::unavailableParams($this, $params)) {
			return $unavailableParams;
		}

        $where = [];
        $orWhere = [];

        CoreMongodb::mdbNumberEqual('id', $this->id, $where);
		CoreMongodb::mdbStatus('status', $this->status, $where);

		CoreMongodb::mdbStringLike('name', $this->name, $orWhere, 'or');

        return Yii::$app->mongodb->search($this, [
            'where' => $where,
            'orWhere' => $orWhere,
        ]);
    }
}