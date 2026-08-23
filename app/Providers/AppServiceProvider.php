<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

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
        Blade::directive('frDate', function ($expression) {
            return "<?php if (!empty({$expression})) { try { echo \\Illuminate\\Support\\Carbon::parse({$expression})->format('d/m/Y'); } catch (\\Throwable) { echo e({$expression}); } } else { echo '-'; } ?>";
        });

        Blade::directive('frDateTime', function ($expression) {
            return "<?php if (!empty({$expression})) { try { echo \\Illuminate\\Support\\Carbon::parse({$expression})->format('d/m/Y H:i:s'); } catch (\\Throwable) { echo e({$expression}); } } else { echo '-'; } ?>";
        });
    }
}
