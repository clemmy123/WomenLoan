<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use App\Support\LoanWizardFieldMap;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'password.changed' => \App\Http\Middleware\EnsurePasswordIsChanged::class,
            'nida.registration' => \App\Http\Middleware\EnsureNidaRegistrationSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->user()?->hasRole('applicant')) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return null;
            }

            if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
                return null;
            }

            Log::error('Applicant request failed', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);

            $inputExcept = [
                'business_proposal_document',
                'business_registration_attachment',
                'proof_address_attachment',
                'application_letter',
                'bank_statement',
                'group_constitution',
                'group_muhtasari',
                'group_certificate',
                'guarantor_letter',
                '_token',
            ];

            if ($e instanceof QueryException && LoanWizardFieldMap::isLoanApplicationRequest($request->path())) {
                $column = null;

                if (preg_match("/Column '([^']+)' cannot be null/", $e->getMessage(), $matches)) {
                    $column = $matches[1];
                }

                if ($column !== null) {
                    $attribute = validation_attribute_label($column);
                    $fieldMessage = __('validation.required', ['attribute' => $attribute]);
                    $step = LoanWizardFieldMap::stepForField($column);
                    $url = url()->previous();
                    $separator = str_contains($url, '?') ? '&' : '?';

                    return redirect()->to($url.$separator.'wizard_step='.$step)
                        ->withInput($request->except($inputExcept))
                        ->withErrors([$column => $fieldMessage]);
                }
            }

            $message = __('messages.unexpected_error');

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 500);
            }

            return redirect()->back()
                ->withInput($request->except($inputExcept))
                ->withErrors(['error' => $message]);
        });
    })->create();
