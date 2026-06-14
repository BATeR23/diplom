<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Регистрируем маршруты для авторизации broadcasting для веб-группы (если понадобится)
        // Основной маршрут для API с токенами добавлен в routes/api.php
        Broadcast::routes();

        require base_path('routes/channels.php');
    }
}
