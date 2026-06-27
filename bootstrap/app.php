<?php

use App\Http\Middleware\CDN\AddSecurityHeaders;
use App\Http\Middleware\CDN\AuthProjectMiddleware;
use App\Http\Middleware\CDN\LogApiRequests;
use App\Http\Middleware\Dashboard\ForcePasswordChange;
use App\Http\Middleware\Dashboard\HandleAppearance;
use App\Http\Middleware\Dashboard\HandleInertiaRequests;
use App\Http\Middleware\Dashboard\ProjectAccessibilityMiddleware;
use App\Http\Middleware\Dashboard\SecureHeaders;
use App\Http\Middleware\Installation\EnsureApplicationInstalled;
use App\Http\Middleware\LogContextMiddleware;
use App\Support\ErrorReporting\RemoteExceptionReporter;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->trustProxies(at: '*');

        $middleware->append(EnsureApplicationInstalled::class);
        $middleware->append(LogContextMiddleware::class);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SecureHeaders::class,
            AuthenticateSession::class,
            ForcePasswordChange::class,
        ]);

        $middleware->redirectTo('/login', '/projects');
        $middleware->alias([
            'project-access' => ProjectAccessibilityMiddleware::class,
            'project-auth' => AuthProjectMiddleware::class,
            'api-security' => AddSecurityHeaders::class,
            'log-api' => LogApiRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontReport(NotFoundHttpException::class);
        $exceptions->report(fn (Throwable $exception) => app(RemoteExceptionReporter::class)->report($exception));

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($e instanceof AuthenticationException) {
                return null;
            }

            $status = 500;

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
            } elseif (property_exists($e, 'status')) {
                $status = $e->status ?? 500;
            }

            if (! in_array($status, [404, 403, 500])) {
                return null;
            }

            if ($status === 403) {
                Log::warning($e->getMessage(), ['url' => $request->fullUrl()]);
            }

            return Inertia::render('Error', ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })
    ->create();
