<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        // Обрабатываем исключения аутентификации до того, как базовый класс попытается вызвать route('login')
        if ($exception instanceof AuthenticationException) {
            // Для всех API запросов или если запрос ожидает JSON, всегда возвращаем JSON ответ
            // Это предотвращает попытку вызвать route('login'), которого может не быть
            if ($request->expectsJson() ||
                $request->is('api/*') ||
                str_starts_with($request->path(), 'api/') ||
                str_contains($request->path(), 'api/')) {
                return response()->json([
                    'message' => 'Не авторизован. Пожалуйста, войдите в систему.',
                ], 401);
            }

            // Для веб-запросов проверяем, существует ли маршрут login, перед попыткой редиректа
            try {
                // Используем Route::has() для проверки существования маршрута
                if (Route::has('login')) {
                    return redirect()->guest(route('login'));
                }
            } catch (\Exception $e) {
                // Если произошла ошибка, просто возвращаем JSON
            }

            // Если маршрут login не существует или произошла ошибка, возвращаем JSON
            return response()->json([
                'message' => 'Не авторизован. Пожалуйста, войдите в систему.',
            ], 401);
        }

        return parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // Для всех API запросов возвращаем JSON ответ вместо редиректа
        if ($request->expectsJson() ||
            $request->is('api/*') ||
            str_starts_with($request->path(), 'api/') ||
            str_contains($request->path(), 'api/')) {
            return response()->json([
                'message' => 'Не авторизован. Пожалуйста, войдите в систему.',
            ], 401);
        }

        // Для веб-запросов проверяем, существует ли маршрут login, перед попыткой редиректа
        try {
            if (Route::has('login')) {
                return redirect()->guest(route('login'));
            }
        } catch (\Exception $e) {
            // Если произошла ошибка, возвращаем JSON
        }

        // Если маршрут login не существует или произошла ошибка, возвращаем JSON
        return response()->json([
            'message' => 'Не авторизован. Пожалуйста, войдите в систему.',
        ], 401);
    }
}
