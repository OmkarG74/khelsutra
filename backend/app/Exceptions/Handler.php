<?php

namespace App\Exceptions;

use Throwable;
use App\Helpers\ApiResponse;

class Handler
{
    /**
     * Map caught exception to standardized API error response.
     *
     * @param Throwable $e
     * @return array
     */
    public static function render(Throwable $e): array
    {
        $code = 500;
        $message = 'Internal Server Error';
        $errors = [];

        $className = get_class($e);

        if ($e instanceof \InvalidArgumentException || str_contains($className, 'BadRequest')) {
            $code = 400;
            $message = $e->getMessage() ?: 'Bad Request';
        } elseif (str_contains($className, 'AuthenticationException') || str_contains($className, 'Unauthorized')) {
            $code = 401;
            $message = 'Unauthenticated or token expired.';
        } elseif (str_contains($className, 'AuthorizationException') || str_contains($className, 'AccessDenied')) {
            $code = 403;
            $message = $e->getMessage() ?: 'You do not have permission to perform this action.';
        } elseif (str_contains($className, 'ModelNotFoundException') || str_contains($className, 'NotFoundHttpException')) {
            $code = 404;
            $message = 'Requested resource was not found.';
        } elseif (str_contains($className, 'Conflict') || $e->getCode() === 409) {
            $code = 409;
            $message = $e->getMessage() ?: 'Resource conflict or overlapping schedule detected.';
        } elseif (str_contains($className, 'ValidationException')) {
            $code = 422;
            $message = 'The given data was invalid.';
            if (method_exists($e, 'errors')) {
                $errors = $e->errors();
            }
        } else {
            // General 500: Protect against leaking raw SQL / sensitive details
            $code = 500;
            $message = 'An unexpected server error occurred. Please contact support.';
            // Log actual exception internally
            error_log('[KhelSutra Error] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        }

        return ApiResponse::error($message, $errors, $code);
    }
}
