<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->appendToGroup('web', \App\Http\Middleware\SetLocale::class);
        $middleware->validateCsrfTokens(except: [
            'api/desktop/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $exception, Request $request) {
            if (! $request->routeIs('dashboard')) {
                return null;
            }

            $reference = strtoupper(substr(hash('sha256', uniqid('', true)), 0, 8));
            $diagnosis = 'The dashboard encountered an unexpected server error.';

            // Blade wraps the original exception one or more times. Diagnose the
            // deepest exception so production reports the actual cause.
            $rootCause = $exception;
            while ($rootCause->getPrevious() instanceof \Throwable) {
                $rootCause = $rootCause->getPrevious();
            }

            if ($rootCause instanceof RouteNotFoundException) {
                $diagnosis = 'A dashboard menu route is missing from the deployed route cache.';

                if (preg_match('/Route \[([^\]]+)\] not defined/i', $rootCause->getMessage(), $match)) {
                    $diagnosis .= ' Missing route: '.$match[1].'.';
                }
            } elseif ($rootCause instanceof \Illuminate\Database\QueryException) {
                $diagnosis = 'The Hostinger database schema does not match the deployed dashboard code.';

                if (preg_match("/(?:Unknown column|Table) '([^']+)'/i", $rootCause->getMessage(), $match)) {
                    $diagnosis .= ' Missing database object: '.$match[1].'.';
                }
            } elseif ($rootCause instanceof \TypeError) {
                $diagnosis = 'The dashboard received an incompatible value from the production database: '
                    .preg_replace('/\s+/', ' ', $rootCause->getMessage());
            } elseif ($exception instanceof \Illuminate\View\ViewException) {
                $diagnosis = 'A deployed dashboard layout or compiled Blade view could not be rendered. Root cause: '
                    .class_basename($rootCause).'.';
            }

            Log::error('Dashboard rendering failed', [
                'error_reference' => $reference,
                'user_id' => $request->user()?->getAuthIdentifier(),
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
                'root_exception_class' => $rootCause::class,
                'root_exception_message' => $rootCause->getMessage(),
                'request_url' => $request->fullUrl(),
            ]);

            return response()->view('errors.dashboard', [
                'reference' => $reference,
                'diagnosis' => $diagnosis,
            ], 500);
        });
    })->create();
