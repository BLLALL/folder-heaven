<?php

namespace App\Exceptions;

use App\Traits\ApiResponses;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;


class Handler
{
    use ApiResponses;

    public function render(Request $request, $exception)
    {
        if (! $request->is('api/*')) {
            return false;
        }
        
        return match (true) {
            $exception instanceof ValidationException => $this->error($exception->errors(), 422),

            $exception instanceof AuthenticationException => $this->error('Unauthenticated', 401),

            $exception instanceof AuthorizationException => $this->error('Unauthorized', 403),

            $exception instanceof NotFoundHttpException, 
            $exception instanceof ModelNotFoundException => $this->error('Resource not found', 404),
                        
            $exception instanceof HttpException => $this->error($exception->getMessage(), 403),
            
            $exception instanceof RouteNotFoundException => $this->error('Route not found', 404),

            $exception instanceof MethodNotAllowedHttpException => $this->error(
                sprintf(
                    'The %s method is not supported for this route. Supported methods: %s',
                    $request->method(),
                    implode(', ', [$exception->getHeaders()['Allow']] ?? [])
                ),
                405
            ),

            default => $this->handleServerError($exception)

        };
    }

    private function handleQueryException(QueryException $exception): mixed
    {
        Log::error($exception->getMessage());

        return $this->error('Database error occurred', 500);
    }

    private function handleServerError(Exception $exception) {
        if (config('app.debug') === false) {
            return $this->error('Server Error', 500);
        }

        $trace = $exception->getTrace();
        $rootAppFile = collect($trace)->first(function ($frame) {
            return isset($frame['file']) && !str_contains($frame['file'], '/vendor/');
        });

        return response()->json([
            'message' =>  $exception->getMessage(),
            'file' => $rootAppFile['file'] ?? $exception->getFile(),
            'line' => $rootAppFile['line'] ?? $exception->getLine(),
        ], 500);

    }
}