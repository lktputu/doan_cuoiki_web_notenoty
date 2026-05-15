<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $compiledViewPath = config('view.compiled');
        if ($compiledViewPath && !is_dir($compiledViewPath)) {
            mkdir($compiledViewPath, 0777, true);
        }

        Schema::defaultStringLength(191);

        if (env('APP_ENV') !== 'local') {
            URL::forceScheme('https');
        }
    }

    public function register()
    {
        //
    }
}
