<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Mail\ErrorReportMail;

Route::middleware([
    // TODO: criar middleware que controla acessos à recursos somente a devs do sistema
])->group(function () {
    // Aqui colocar rotas como falso erro, log false, email fake etc
    Route::any('/ping/{message?}', function (mixed $message = null) {
        $message ??= request()->input('message', null);

        $message ??= filter_var($message, FILTER_DEFAULT, FILTER_NULL_ON_FAILURE);

        if (strtolower($message) === 'ping') {
            $message = 'Pong!';
        }

        return [
            'message' => $message ?: 'Pong!',
        ];
    })->where('message', '[a-zA-Z0-9\- ]+')->name('ping');

    /**
     * Test route to trigger a runtime error and send error email
     * GET/POST /dev/trigger-error
     */
    Route::any('/trigger-error', function () {
        throw new RuntimeException('This is a test error triggered from /dev/trigger-error route for testing error email notifications.');
    })->name('trigger-error');

    /**
     * Test route to trigger a database error and send error email
     * GET/POST /dev/trigger-db-error
     */
    Route::any('/trigger-db-error', function () {
        // This will trigger a QueryException
        Illuminate\Support\Facades\DB::statement('SELECT * FROM non_existent_table_for_testing');
    })->name('trigger-db-error');

    /**
     * Test route to send error email directly (without throwing exception)
     * GET/POST /dev/test-error-email
     */
    Route::any('/test-error-email', function () {
        try {
            $testException = new RuntimeException(
                'This is a test error email. This exception was created for testing purposes only.',
                500
            );

            $context = [
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'user_id' => auth()->id(),
                'tenant' => tenant('id') ?? 'central',
                'test_mode' => true,
            ];

            $errorEmail = config('mail.error_reporting.to', config('mail.from.address'));

            Mail::to($errorEmail)->send(new ErrorReportMail($testException, $context));

            return [
                'success' => true,
                'message' => 'Test error email sent successfully',
                'to' => $errorEmail,
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Failed to send test error email',
                'error' => $e->getMessage(),
            ];
        }
    })->name('test-error-email');
});
