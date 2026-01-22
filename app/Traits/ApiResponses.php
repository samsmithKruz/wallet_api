<?php

namespace App\Traits;

use App\Exceptions\ApiException;
use Illuminate\Http\JsonResponse;

trait ApiResponses
{
    /**
     * Return a successful JSON response.
     */
    protected function successResponse($data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status_code' => $statusCode,
            'message' => $message,
            'data' => $data,
            'errors' => null
        ], $statusCode);
    }

    /**
     * Return an error JSON response.
     */
    protected function errorResponse(string $message = 'Error', int $statusCode = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'status_code' => $statusCode,
            'message' => $message,
            'data' => null,
            'errors' => !empty($errors) ? $errors : [$message]
        ], $statusCode);
    }

    /**
     * Throw a bad request exception.
     */
    protected function throwBadRequestException(string $message = 'Bad request', array $errors = []): void
    {
        throw new ApiException($message, 400, $errors);
    }

    /**
     * Throw an unauthorized exception.
     */
    protected function throwUnauthorizedException(string $message = 'Unauthorized', array $errors = []): void
    {
        throw new ApiException($message, 401, $errors);
    }

    /**
     * Throw a forbidden exception.
     */
    protected function throwForbiddenException(string $message = 'Forbidden', array $errors = []): void
    {
        throw new ApiException($message, 403, $errors);
    }

    /**
     * Throw a not found exception.
     */
    protected function throwNotFoundException(string $message = 'Resource not found', array $errors = []): void
    {
        throw new ApiException($message, 404, $errors);
    }

    /**
     * Throw a validation exception.
     */
    protected function throwValidationException(string $message = 'Validation failed', array $errors = []): void
    {
        throw new ApiException($message, 422, $errors);
    }

    /**
     * Throw a conflict exception.
     */
    protected function throwConflictException(string $message = 'Conflict', array $errors = []): void
    {
        throw new ApiException($message, 409, $errors);
    }

    /**
     * Throw an unprocessable entity exception.
     */
    protected function throwUnprocessableEntityException(string $message = 'Unprocessable entity', array $errors = []): void
    {
        throw new ApiException($message, 422, $errors);
    }
}
