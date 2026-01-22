<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ApiException extends Exception
{
    protected $statusCode;
    protected $errors;

    public function __construct(string $message = 'An error occurred', int $statusCode = 500, array $errors = [])
    {
        parent::__construct($message, $statusCode);

        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'status_code' => $this->statusCode,
            'message' => $this->getMessage(),
            'data' => null,
            'errors' => !empty($this->errors) ? $this->errors : [$this->getMessage()]
        ], $this->statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
