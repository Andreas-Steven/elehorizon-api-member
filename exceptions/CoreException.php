<?php

namespace app\exceptions;

use Yii;
use Exception;

class CoreException extends Exception
{
    private ?object $model;
    private array $errors = [];
    private int $statusCode;

    public function __construct($model = null, ?string $message = null, int $statusCode = 422)
    {
        $this->model = $model;
        $this->errors = is_object($model) && method_exists($model, 'getErrors')
            ? (array) $model->getErrors()
            : [];

        $this->statusCode = $statusCode;
        parent::__construct($message ?? Yii::t('app', 'badRequest'), $statusCode);
    }

    public function getModel(): ?object
    {
        return $this->model;
    }

    public function getErrors(): array
    {
        if (empty($this->errors)) {
            return [];
        }

        // Pastikan errors adalah array dua dimensi dengan format field => [messages]
        $normalizedErrors = array_filter($this->errors, 'is_array');

        return array_merge(...array_map(
            fn($field, $messages) => array_map(
                fn($msg) => ['field' => $field, 'message' => (string)$msg],
                $messages
            ),
            array_keys($normalizedErrors),
            $normalizedErrors
        ));
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function toArray(): array
    {
        Yii::$app->response->statusCode = $this->statusCode;

        return [
            'code' => $this->statusCode,
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => $this->getErrors(),
        ];
    }
}
