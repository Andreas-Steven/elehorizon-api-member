<?php

namespace app\components;

use Yii;
use yii\db\Exception;
use yii\data\ActiveDataProvider;
use app\helpers\Constants;

/**
 * MongoDbManager is a Yii2-friendly wrapper for MongoDB operations.
 *
 * This class provides simplified MongoDB interactions for Yii2 projects, including:
 * - Upserting single Yii2 models or bulk arrays of records
 * - Searching documents with filters, projections, sorting, and pagination
 *
 * Features:
 * - Automatic detection of single model vs bulk data in upsert
 * - Custom unique keys for bulk upsert
 * - Pagination and sorting built-in for search
 * - Optional logging for failed MongoDB operations
 *
 * Example usage:
 * ```php
 * $model = new MyModel();
 * $model->id = 1;
 * $model->name = 'John';
 * 
 * // Single model upsert
 * Yii::$app->mongodbManager->upsert($model, $model::tableName());
 *
 * // Bulk upsert
 * $records = [
 *     ['id' => 1, 'name' => 'John'],
 *     ['id' => 2, 'name' => 'Jane'],
 * ];
 * Yii::$app->mongodbManager->upsert($records, 'my_collection', ['id']);
 *
 * // Search with filters, sorting, and projection
 * $filters = [
 *     'where' => ['status' => 'active'],
 *     'orWhere' => [['type' => 'admin'], ['type' => 'user']],
 * ];
 * $dataProvider = Yii::$app->mongodbManager->search($model, $filters, ['id', 'name', 'status']);
 * ```
 *
 * @package app\components
 * @version 1.0.0
 * @since 2025-11-04
 */
class MongoDbManager extends \MongoDB\Client
{
    /**
     * @var string MongoDB connection DSN
     */
    public string $dsn;

    /**
     * @var string MongoDB database name
     */
    public string $database;

    /**
     * @var \MongoDB\Client|null Cached MongoDB connection instance
     */
    private $connection = null;

    /**
     * Returns the MongoDB connection instance (singleton style)
     *
     * @return \MongoDB\Client
     */
    protected function getConnection()
    {
        return $this->connection ??= new \MongoDB\Client($this->dsn, [
            'maxPoolSize' => 50,
            'waitQueueTimeoutMS' => 5000,
        ]);
    }

    /**
     * Upserts data into MongoDB.
     *
     * Handles both single Yii2 model and bulk array of records.
     * Automatically determines whether $data is a single model or array.
     *
     * @param Model|array $data Single Yii2 model or array of records
     * @param string $tableName MongoDB collection name
     * @param array $filters Array of fields to identify unique documents (used for bulk upsert), default ['id']
     * @param bool $logFailure Whether to log failed sync attempts using coreAPI, default true
     * @return void
     */
    public function upsert($data, string $tableName, array $filters = ['id'], bool $logFailure = true): void
    {
        try {
            $collection = $this->getConnection()->selectCollection($this->database, $tableName);

            // Single Yii2 model
            if ($data instanceof Model) {
                $collection->updateOne(
                    ['id' => $data->id],
                    ['$set' => $data->toArray()],
                    ['upsert' => true]
                );
                return;
            }

            // Bulk array
            if (is_array($data) && !empty($data)) {
                $bulk = array_map(function ($item) use ($filters) {
                    $criteria = [];
                    foreach ($filters as $f) {
                        $criteria[$f] = $item[$f] ?? null;
                    }
                    return [
                        'updateOne' => [
                            $criteria,
                            ['$set' => $item],
                            ['upsert' => true],
                        ],
                    ];
                }, $data);

                $collection->bulkWrite($bulk);
            }
        } catch (\Exception $e) {
            if ($logFailure) {
                Yii::$app->coreAPI::setMongodbSyncFailed($data instanceof Model ? $data : null);
            }
        }
    }

    /**
     * Searches MongoDB documents with optional filters, projection, sorting, and pagination.
     *
     * @param object $model Yii2 model instance used to determine table name and pagination parameters
     * @param array $filters Array containing 'where' and 'orWhere' conditions, default []
     * @param array $projection Fields to return, default empty (all fields except _id)
     * @return ActiveDataProvider Provides models and pagination info compatible with Yii2
     */
    public function search($model, array $filters = [], array $projection = []): ActiveDataProvider
    {
        try {
            $collection = $this->getConnection()->selectCollection($this->database, $model::tableName());

            // Build filter
            $queryFilter = [];
            if (!empty($filters['where'])) {
                $queryFilter['$and'][] = $filters['where'];
            }
            if (!empty($filters['orWhere'])) {
                $queryFilter['$and'][] = ['$or' => $filters['orWhere']];
            }
            if (empty($queryFilter)) {
                $queryFilter = [];
            }

            // Sorting
            $sortBy = $model->sort_by ?? 'id';
            $sortOrder = ($model->sort_dir ?? 'desc') === 'asc' ? 1 : -1;

            // Pagination
            $pageSize = (int) ($model->page_size ?? Yii::$app->params['pagination']['pageSize']);
            $page = max(0, ($model->page ?? 1) - 1);
            $totalCount = $collection->countDocuments($queryFilter);
            $pageSize = min($totalCount, $pageSize);
            $pagination = new \yii\data\Pagination(['totalCount' => $totalCount, 'pageSize' => $pageSize, 'page' => $page]);

            // Query options
            $options = [
                'limit' => $pagination->limit,
                'skip' => $pagination->offset,
                'sort' => [$sortBy => $sortOrder],
                'projection' => array_merge(['_id' => false], $projection),
                'maxTimeMS' => 5000,
            ];

            $documents = iterator_to_array($collection->find($queryFilter, $options));

            return new ActiveDataProvider([
                'models' => $documents,
                'pagination' => $pagination,
                'totalCount' => $totalCount,
            ]);
        } catch (\Exception $e) {
            Yii::error($e->getMessage(), __METHOD__);
            return new ActiveDataProvider([
                'models' => [],
                'pagination' => new \yii\data\Pagination(),
            ]);
        }
    }
}