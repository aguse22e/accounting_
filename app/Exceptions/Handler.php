<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        // #region agent log
        $path = (string) $request->path();
        if (str_contains($path, 'jurnal') || str_contains($path, 'transaksi')) {
            $route = $request->route();
            $payload = [
                'sessionId' => 'ddb0e0',
                'runId' => 'pre-fix',
                'hypothesisId' => $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 'A' : 'D',
                'location' => 'Handler.php:render',
                'message' => 'exception on transaksi/jurnal path',
                'data' => [
                    'path' => $path,
                    'method' => $request->method(),
                    'routeName' => $route ? $route->getName() : null,
                    'routeAction' => $route ? $route->getActionName() : null,
                    'routeParams' => $route ? $route->parameters() : null,
                    'exception' => get_class($e),
                    'model' => $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? $e->getModel() : null,
                    'ids' => $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? $e->getIds() : null,
                    'status' => method_exists($e, 'getStatusCode') ? $e->getStatusCode() : null,
                    'msg' => $e->getMessage(),
                ],
                'timestamp' => (int) (microtime(true) * 1000),
            ];
            @file_put_contents(base_path('debug-ddb0e0.log'), json_encode($payload) . "\n", FILE_APPEND);
        }
        // #endregion

        return parent::render($request, $e);
    }
}
