<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Boost\Install\CodeEnvironment\ClaudeCode;
use Laravel\Boost\Install\CodeEnvironment\OpenCode;
use Laravel\Boost\Boost;
use Laravel\Boost\BoostManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->boostLoadEnvironments();
    }

    public function boostLoadEnvironments(): void
    {
        $boostEnvs = app(BoostManager::class)->getCodeEnvironments();

        if (!array_key_exists('claudecode', $boostEnvs)) {
            Boost::registerCodeEnvironment('claudecode', ClaudeCode::class);
        }

        if (!array_key_exists('opencode', $boostEnvs)) {
            Boost::registerCodeEnvironment('opencode', OpenCode::class);
        }
    }
}
