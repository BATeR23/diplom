<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRideRequest;
use App\Http\Requests\UpdateRideRequest;
use App\Models\Ride;
use App\Models\Review;
use App\Helpers\NotificationHelper;
use App\Helpers\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RideController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()
            ->ridesAsDriver()
            ->with(['vehicle', 'bookings'])
            ->orderByDesc('departure_time')
            ->paginate(10);
    }

    public function search(Request $request)
    {
        $query = Ride::with([
            'driver' => fn ($q) => $q->withCount('reviewsReceived'),
            'vehicle',
        ])
            ->where('status', 'published')
            ->where('departure_time', '>=', now());

        if ($request->filled('origin')) {
            $query->where('origin_city', 'like', '%' . $request->input('origin') . '%');
        }

        if ($request->filled('destination')) {
            $query->where('destination_city', 'like', '%' . $request->input('destination') . '%');
        }

        if ($request->filled('date')) {
            $date = Carbon::parse($request->input('date'))->startOfDay();
            $query->whereBetween('departure_time', [$date, $date->copy()->endOfDay()]);
        }

        if ($request->filled('seats')) {
            $query->where('seats_available', '>=', (int) $request->input('seats'));
        }

        return $query->orderBy('departure_time')->paginate(10);
    }

    public function store(StoreRideRequest $request)
    {
        $data = $request->validated();

        // Проверяем, что у водителя есть подтвержденное транспортное средство
        if (isset($data['vehicle_id']) && $data['vehicle_id']) {
            $vehicle = \App\Models\Vehicle::where('id', $data['vehicle_id'])
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            abort_if(
                $vehicle->verification_status !== 'approved',
                403,
                'Нельзя создать поездку с неподтвержденным транспортным средством. Пожалуйста, дождитесь подтверждения вашего автомобиля администратором.'
            );
        } else {
            // Если vehicle_id не указан, проверяем наличие хотя бы одного подтвержденного ТС
            $hasApprovedVehicle = $request->user()->vehicles()
                ->where('verification_status', 'approved')
                ->exists();

            abort_unless(
                $hasApprovedVehicle,
                403,
                'Для создания поездки необходимо иметь хотя бы одно подтвержденное транспортное средство. Пожалуйста, добавьте автомобиль и дождитесь его подтверждения администратором или менеджером.'
            );
        }

        $ride = $request->user()->ridesAsDriver()->create(array_merge($data, [
            'status' => 'published',
            'seats_available' => $data['seats_total'],
        ]));

        AuditLogger::log($request->user()->id, 'ride.create', [
            'entity_type' => 'Ride',
            'entity_id' => $ride->id,
        ], $request);

        return response()->json($ride->load(['vehicle']), 201);
    }

    public function show(Ride $ride)
    {
        $ride->load(['driver', 'vehicle', 'bookings.passenger']);

        $driverReviews = Review::where('target_id', $ride->driver_id)
            ->with(['author:id,name'])
            ->latest()
            ->limit(10)
            ->get();

        return response()->json(array_merge($ride->toArray(), [
            'driver_reviews' => $driverReviews,
        ]));
    }

    public function update(UpdateRideRequest $request, Ride $ride)
    {
        $this->authorizeRide($request, $ride);

        $data = $request->validated();

        // Проверяем подтверждение ТС, если меняется vehicle_id
        if (isset($data['vehicle_id']) && $data['vehicle_id'] !== $ride->vehicle_id) {
            $vehicle = \App\Models\Vehicle::where('id', $data['vehicle_id'])
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            abort_if(
                $vehicle->verification_status !== 'approved',
                403,
                'Нельзя использовать неподтвержденное транспортное средство. Пожалуйста, дождитесь подтверждения вашего автомобиля администратором.'
            );
        }

        if (isset($data['seats_total'])) {
            $difference = $data['seats_total'] - $ride->seats_total;
            $data['seats_available'] = max(0, $ride->seats_available + $difference);
        }

        $ride->update($data);

        AuditLogger::log($request->user()->id, 'ride.update', [
            'entity_type' => 'Ride',
            'entity_id' => $ride->id,
            'meta' => ['fields' => array_keys($data)],
        ], $request);

        return $ride->fresh()->load('vehicle');
    }

    public function destroy(Request $request, Ride $ride)
    {
        $this->authorizeRide($request, $ride);

        $ride->load('bookings.passenger');
        $ride->update(['status' => 'cancelled']);

        // Создаем уведомления для всех пассажиров
        foreach ($ride->bookings as $booking) {
            if ($booking->passenger) {
                NotificationHelper::rideCancelled($booking->passenger, $ride);
            }
        }

        AuditLogger::log($request->user()->id, 'ride.cancel', [
            'entity_type' => 'Ride',
            'entity_id' => $ride->id,
        ], $request);

        return response()->json(['message' => 'Поездка отменена.']);
    }

    public function complete(Request $request, Ride $ride)
    {
        $this->authorizeRide($request, $ride);

        abort_if($ride->status === 'completed', 400, 'Поездка уже завершена.');
        abort_if($ride->status === 'cancelled', 400, 'Нельзя завершить отмененную поездку.');

        \DB::transaction(function () use ($ride) {
            // Загружаем связанные данные
            $ride->load('bookings.passenger', 'driver');

            // Завершаем все принятые бронирования и списываем баланс
            $acceptedBookings = $ride->bookings()->where('status', 'accepted')->get();

            foreach ($acceptedBookings as $booking) {
                $booking->load('passenger', 'ride');
                $this->processBookingPayment($booking);
                $booking->update(['status' => 'completed']);
            }

            // Обновляем статус поездки
            $ride->update(['status' => 'completed']);

            // Обновляем счетчик завершенных поездок у водителя
            $ride->driver->increment('rides_completed');
        });

        AuditLogger::log($request->user()->id, 'ride.complete', [
            'entity_type' => 'Ride',
            'entity_id' => $ride->id,
        ], $request);

        return $ride->fresh()->load(['vehicle', 'bookings.passenger']);
    }

    public function getChatRides(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;

        // Получаем поездки, где пользователь является водителем
        $ridesAsDriver = Ride::where('driver_id', $userId)
            ->with(['bookings.passenger', 'vehicle', 'driver'])
            ->whereHas('bookings') // Поездки с любыми бронированиями (включая pending)
            ->get()
            ->map(function ($ride) use ($user) {
                // Для каждой поездки находим всех собеседников (пассажиров)
                $chatPartners = $ride->bookings()
                    ->with('passenger')
                    ->get()
                    ->map(function ($booking) use ($user) {
                        return [
                            'booking_id' => $booking->id,
                            'user' => $booking->passenger,
                            'status' => $booking->status,
                        ];
                    })
                    ->filter(function ($partner) {
                        return $partner['user'] !== null; // Фильтруем удаленных пользователей
                    })
                    ->values()
                    ->toArray();

                // Возвращаем только если есть хотя бы один партнер для чата
                if (empty($chatPartners)) {
                    return null;
                }

                return [
                    'ride' => $ride,
                    'chat_partners' => $chatPartners,
                    'role' => 'driver',
                ];
            })
            ->filter() // Удаляем null значения
            ->values();

        // Получаем поездки, где пользователь является пассажиром
        $ridesAsPassenger = Ride::whereHas('bookings', function ($query) use ($userId) {
                $query->where('passenger_id', $userId);
            })
            ->with(['driver', 'vehicle', 'bookings' => function ($query) use ($userId) {
                $query->where('passenger_id', $userId);
            }])
            ->get()
            ->map(function ($ride) use ($user) {
                $booking = $ride->bookings->first();
                if (!$booking || !$ride->driver) {
                    return null;
                }

                return [
                    'ride' => $ride,
                    'chat_partners' => [[
                        'booking_id' => $booking->id,
                        'user' => $ride->driver,
                        'status' => $booking->status,
                    ]],
                    'role' => 'passenger',
                ];
            })
            ->filter() // Удаляем null значения
            ->values();

        // Объединяем и сортируем по дате отправления
        $allChatRides = $ridesAsDriver->concat($ridesAsPassenger)
            ->sortByDesc(function ($item) {
                return $item['ride']->departure_time;
            })
            ->values();

        return response()->json($allChatRides);
    }

    protected function processBookingPayment($booking)
    {
        $passenger = $booking->passenger;
        $amount = $booking->price_total;

        // Проверяем достаточность баланса
        if ($passenger->balance < $amount) {
            throw new \Exception('Недостаточно средств на балансе у пассажира ' . $passenger->name . '. Пожалуйста, попросите его пополнить баланс.');
        }

        $balanceBefore = $passenger->balance;
        $balanceAfter = $balanceBefore - $amount;

        // Списываем баланс
        $passenger->update(['balance' => $balanceAfter]);

        // Создаем транзакцию
        \App\Models\BalanceTransaction::create([
            'user_id' => $passenger->id,
            'type' => 'payment',
            'amount' => -$amount, // Отрицательное значение для списания
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => "Оплата поездки: {$booking->ride->origin_city} → {$booking->ride->destination_city}",
            'ride_id' => $booking->ride_id,
            'booking_id' => $booking->id,
        ]);
    }

    protected function authorizeRide(Request $request, Ride $ride): void
    {
        abort_unless($ride->driver_id === $request->user()->id, 403, 'Вы можете управлять только своими поездками.');
    }
}










