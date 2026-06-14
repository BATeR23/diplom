<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use App\Helpers\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'] ?? 'passenger',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        AuditLogger::log($user->id, 'auth.register', [
            'entity_type' => 'User',
            'entity_id' => $user->id,
            'meta' => ['email' => $user->email, 'role' => $user->role],
        ], $request);

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        AuditLogger::log($user->id, 'auth.login', [
            'entity_type' => 'User',
            'entity_id' => $user->id,
        ], $request);

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        if ($request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        AuditLogger::log($request->user()?->id, 'auth.logout', [
            'entity_type' => 'User',
            'entity_id' => $request->user()?->id,
        ], $request);

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        return $request->user()->load(['vehicles', 'ridesAsDriver', 'bookings']);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $data = $request->validated();

        $user = $request->user();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Обработка загрузки аватара
        if ($request->hasFile('avatar')) {
            // Удаляем старый аватар, если есть
            if ($user->avatar_url) {
                \Storage::disk('public')->delete($user->avatar_url);
            }

            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $data['avatar_url'] = $avatarPath;
        }

        $user->update($data);

        return response()->json($user->load(['vehicles', 'ridesAsDriver', 'bookings']));
    }

    public function downloadProfileStatistics(Request $request)
    {
        $currentUser = $request->user();

        if (!$currentUser) {
            return response()->json(['message' => 'Не авторизован.'], 401);
        }

        $user = $currentUser;

        // Админ/менеджер может сформировать отчет по любому пользователю
        if ($request->filled('user_id') && in_array($currentUser->role, ['admin', 'manager'], true)) {
            $userId = (int) $request->query('user_id');
            $user = User::find($userId);

            if (!$user) {
                return response()->json(['message' => 'Пользователь не найден'], 404);
            }
        }

        AuditLogger::log($currentUser->id, 'report.profile_statistics.download', [
            'entity_type' => 'User',
            'entity_id' => $user->id,
            'meta' => ['format' => $request->query('format', 'pdf')],
        ], $request);

        // Загружаем статистику пользователя
        $user->load(['vehicles', 'ridesAsDriver', 'bookings', 'reviewsReceived']);

        // Статистика по поездкам (как водитель)
        $ridesAsDriver = $user->ridesAsDriver;
        $totalRidesAsDriver = $ridesAsDriver->count();
        $completedRidesAsDriver = $ridesAsDriver->where('status', 'completed')->count();
        $publishedRidesAsDriver = $ridesAsDriver->where('status', 'published')->count();

        // Заработок водителя = сумма всех завершенных бронирований на его поездки
        $totalEarningsAsDriver = \App\Models\Booking::whereHas('ride', function($query) use ($user) {
            $query->where('driver_id', $user->id);
        })
            ->where('status', 'completed')
            ->sum('price_total') ?? 0;

        // Статистика по бронированиям (как пассажир)
        $bookingsAsPassenger = $user->bookings;
        $totalBookingsAsPassenger = $bookingsAsPassenger->count();
        $completedBookingsAsPassenger = $bookingsAsPassenger->where('status', 'completed')->count();
        $totalSpentAsPassenger = $bookingsAsPassenger->where('status', 'completed')->sum('price_total');

        // Статистика по отзывам
        $reviewsReceived = $user->reviewsReceived;
        $totalReviews = $reviewsReceived->count();
        $averageRating = $user->rating_average ?? 0;

        // Статистика по автомобилям
        $vehiclesCount = $user->vehicles->count();
        $approvedVehiclesCount = $user->vehicles->where('verification_status', 'approved')->count();

        // Дополнительная статистика (последние 30 дней)
        $last30Days = now()->subDays(30);
        $ridesLast30Days = $user->ridesAsDriver()
            ->where('created_at', '>=', $last30Days)
            ->where('status', 'completed')
            ->count();

        $earningsLast30Days = \App\Models\Booking::whereHas('ride', function($query) use ($user, $last30Days) {
            $query->where('driver_id', $user->id)
                ->where('created_at', '>=', $last30Days);
        })
            ->where('status', 'completed')
            ->sum('price_total') ?? 0;

        // Попытка использовать dompdf, если доступен
        try {
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $data = [
                    'user' => $user,
                    'totalRidesAsDriver' => $totalRidesAsDriver,
                    'completedRidesAsDriver' => $completedRidesAsDriver,
                    'publishedRidesAsDriver' => $publishedRidesAsDriver,
                    'totalEarningsAsDriver' => $totalEarningsAsDriver,
                    'totalBookingsAsPassenger' => $totalBookingsAsPassenger,
                    'completedBookingsAsPassenger' => $completedBookingsAsPassenger,
                    'totalSpentAsPassenger' => $totalSpentAsPassenger,
                    'totalReviews' => $totalReviews,
                    'averageRating' => $averageRating,
                    'vehiclesCount' => $vehiclesCount,
                    'approvedVehiclesCount' => $approvedVehiclesCount,
                    'ridesLast30Days' => $ridesLast30Days,
                    'earningsLast30Days' => $earningsLast30Days,
                    'generatedAt' => now()->format('d.m.Y H:i'),
                    'debugMode' => $request->has('user_id') ? 'Сформировано администратором/менеджером' : 'Сформировано пользователем',
                ];

                // Определяем формат вывода
                $format = $request->query('format', 'pdf');

                if ($format === 'html') {
                    // Возвращаем HTML для предпросмотра
                    $html = view('pdf.profile-statistics', $data)->render();
                    return response($html)
                        ->header('Content-Type', 'text/html; charset=utf-8')
                        ->header('Content-Disposition', 'inline; filename="profile-statistics-' . $user->id . '.html"');
                }

                // Генерируем PDF
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.profile-statistics', $data);

                // Определяем имя файла
                $filename = 'profile-statistics-' . $user->id . '-' . date('Y-m-d') . '.pdf';

                if ($request->query('preview', false)) {
                    // Предпросмотр в браузере
                    return $pdf->stream($filename);
                }

                // Скачивание файла
                return $pdf->download($filename);

            }
        } catch (\Exception $e) {
            \Log::error('Ошибка генерации PDF: ' . $e->getMessage());
            // Если dompdf недоступен, генерируем HTML
        }

        // Альтернативный вариант: возвращаем HTML, который можно сохранить как PDF
        $data = [
            'user' => $user,
            'totalRidesAsDriver' => $totalRidesAsDriver,
            'completedRidesAsDriver' => $completedRidesAsDriver,
            'publishedRidesAsDriver' => $publishedRidesAsDriver,
            'totalEarningsAsDriver' => $totalEarningsAsDriver,
            'totalBookingsAsPassenger' => $totalBookingsAsPassenger,
            'completedBookingsAsPassenger' => $completedBookingsAsPassenger,
            'totalSpentAsPassenger' => $totalSpentAsPassenger,
            'totalReviews' => $totalReviews,
            'averageRating' => $averageRating,
            'vehiclesCount' => $vehiclesCount,
            'approvedVehiclesCount' => $approvedVehiclesCount,
            'ridesLast30Days' => $ridesLast30Days,
            'earningsLast30Days' => $earningsLast30Days,
            'generatedAt' => now()->format('d.m.Y H:i'),
            'debugMode' => $request->has('user_id') ? 'Сформировано администратором/менеджером' : 'Сформировано пользователем',
        ];

        $html = view('pdf.profile-statistics', $data)->render();

        return response($html)
            ->header('Content-Type', 'text/html; charset=utf-8')
            ->header('Content-Disposition', 'inline; filename="profile-statistics-' . $user->id . '-' . date('Y-m-d') . '.html"');
    }
}

