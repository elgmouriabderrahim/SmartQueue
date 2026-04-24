<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as FoundationHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Exceptions\MethodNotAllowedHttpException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class Handler extends FoundationHandler
{
    /**
     * Register exception rendering callbacks.
     */
    public function register(): void
    {
        // Handle authentication exceptions
        $this->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::unauthorized();
            }

            return null;
        });

        // Handle authorization exceptions
        $this->renderable(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::forbidden();
            }

            return null;
        });

        // Handle validation exceptions
        $this->renderable(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::validationFailed($e->errors());
            }

            return null;
        });

        // Handle model not found
        $this->renderable(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                $modelClass = class_basename($e->getModel());
                return ApiResponse::notFound("{$modelClass} not found.");
            }

            return null;
        });

        // Handle route not found
        $this->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::notFound('Resource not found.');
            }

            return null;
        });

        // Handle method not allowed
        $this->renderable(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::methodNotAllowed();
            }

            return null;
        });

        // Handle rate limiting
        $this->renderable(function (TooManyRequestsHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::tooManyRequests();
            }

            return null;
        });

        // Handle all other exceptions in API context
        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                $message = 'Server error. Please try again later.';

                // In debug mode, include the actual error message
                if (config('app.debug')) {
                    $message = $e->getMessage() ?: 'An unexpected error occurred.';
                }

                return ApiResponse::serverError($message);
            }

            return null;
        });
    }
}