<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    /**
     * Return a success response.
     */
    public static function success(mixed $data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        return self::make(true, $message, $data, $status);
    }

    /**
     * Return an error response.
     */
    public static function error(string $message, int $status = 400, mixed $data = null): JsonResponse
    {
        return self::make(false, $message, $data, $status);
    }

    /**
     * Return a not found response.
     */
    public static function notFound(string $message = 'Resource not found.'): JsonResponse
    {
        return self::error($message, 404);
    }

    /**
     * Return an unauthorized response.
     */
    public static function unauthorized(string $message = 'Unauthenticated.'): JsonResponse
    {
        return self::error($message, 401);
    }

    /**
     * Return a forbidden response.
     */
    public static function forbidden(string $message = 'Forbidden.'): JsonResponse
    {
        return self::error($message, 403);
    }

    /**
     * Return a validation error response.
     */
    public static function validationFailed(array $errors): JsonResponse
    {
        return self::error('Validation failed.', 422, ['errors' => $errors]);
    }

    /**
     * Return a rate limit exceeded response.
     */
    public static function tooManyRequests(string $message = 'Too many requests. Please try again later.'): JsonResponse
    {
        return self::error($message, 429);
    }

    /**
     * Return a method not allowed response.
     */
    public static function methodNotAllowed(string $message = 'Method not allowed.'): JsonResponse
    {
        return self::error($message, 405);
    }

    /**
     * Return a server error response.
     */
    public static function serverError(string $message = 'Server error. Please try again later.'): JsonResponse
    {
        return self::error($message, 500);
    }

    /**
     * Build a JSON response with the given parameters.
     */
    public static function make(bool $success, string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
