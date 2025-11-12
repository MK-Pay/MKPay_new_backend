<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class HealthCheckController extends Controller
{
    public function basicCheck(Request $request): JsonResponse
    {
        $returnData = static::appStatus();

        $success = count(array_filter($returnData)) > 0;

        return response()->json(array_merge(['success' => $success], $returnData), $success ? 200 : 500);
    }

    public function dbCheck(Request $request): JsonResponse
    {
        try {
            $returnData['dbCheck'] = static::dbStatus();

            $success = count(array_filter($returnData)) > 0;

            return response()->json(array_merge(['success' => $success], $returnData), $success ? 200 : 500);
        } catch (\Throwable $th) {
            if (config('app.debug', false)) {
                throw $th;
            }

            return response()->json(['success' => false], 500);
        }
    }

    public function allCheck(Request $request): JsonResponse
    {
        try {
            $returnData = static::appStatus();
            $returnData['dbCheck'] = static::dbStatus();

            $success = count(array_filter($returnData)) > 0;

            return response()->json(array_merge(['success' => $success], $returnData), $success ? 200 : 500);
        } catch (\Throwable $th) {
            if (config('app.debug', false)) {
                throw $th;
            }

            return response()->json(['success' => false], 500);
        }
    }

    public function basicCheckBool(Request $request): JsonResponse
    {
        $success = count(array_filter(static::appStatus())) > 0;

        return response()->json(['success' => $success], $success ? 200 : 500);
    }

    public function dbCheckBool(Request $request): JsonResponse
    {
        $success = count(array_filter(static::dbStatus())) > 0;

        return response()->json(['success' => $success], $success ? 200 : 500);
    }

    public function allCheckBool(Request $request): JsonResponse
    {
        $returnData = static::appStatus();
        $returnData['dbCheck'] = static::dbStatus();
        $success = count(array_filter($returnData)) > 0;

        return response()->json(['success' => $success], $success ? 200 : 500);
    }

    protected static function appStatus(): array
    {
        try {
            $returnData = [
                'laravel' => app()->version(),
                'env' => config('app.env', 'UNKNOWN'),
                'debug' => config('app.debug'),
                'canExecuteCommands' => static::canExecuteCommands(),
                'database_default' => config('database.default'),
                'git.branch' => null,
                'git.commit.short_id' => null,
                'git.commit.long_id' => null,
            ];

            return $returnData;
        } catch (\Throwable $th) {
            if (config('app.debug', false)) {
                throw $th;
            }

            return [];
        }
    }

    protected static function dbStatus(): array
    {
        try {
            $tables = [
                'public.users',
                'public.tenants',
            ];

            foreach ($tables as $key => $table) {
                unset($tables[$key]);
                $tables[$table] = DB::table($table)->count();
            }

            $returnData['tables'] = $tables;

            return $returnData;
        } catch (\Throwable $th) {
            if (config('app.debug', false)) {
                throw $th;
            }

            return [];
        }
    }

    protected static function canExecuteCommands(): bool
    {
        Artisan::call('help', ['--version']);

        $output = Artisan::output();

        return Str::contains($output, app()->version());
    }

    public static function routes()
    {
        Route::prefix('health')
            ->name('health.')
            ->group(function () {
                Route::any('/', [static::class, 'basicCheckBool'])->name('basicCheckBool');
                Route::any('/info', [static::class, 'basicCheck'])->name('basicCheck');

                Route::any('/db', [static::class, 'dbCheckBool'])->name('dbCheckBool');
                Route::any('/db/info', [static::class, 'dbCheck'])->name('dbCheck');

                Route::any('/all', [static::class, 'allCheckBool'])->name('allCheckBool');
                Route::any('/all/info', [static::class, 'allCheck'])->name('allCheck');
            });
    }
}
