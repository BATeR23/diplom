<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingStatusRequest;
use App\Models\BalanceTransaction;
use App\Models\Booking;
use App\Models\Ride;
use App\Helpers\NotificationHelper;
use App\Helpers\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->query('role', 'passenger');
        $user = $request->user();

        if ($role === 'driver') {
            $bookings = Booking::with(['ride', 'passenger'])
                ->whereHas('ride', fn ($query) => $query->where('driver_id', $user->id))
                ->latest()
                ->paginate(10);
        } else {
            $bookings = $user->bookings()->with('ride.driver')->latest()->paginate(10);
        }

        return $bookings;
    }

    public function store(StoreBookingRequest $request, Ride $ride)
    {
        $data = $request->validated();

        abort_if($ride->driver_id === $request->user()->id, 400, 'Нельзя бронировать собственную поездку.');
        abort_if($ride->status !== 'published', 400, 'Поездка недоступна для бронирования.');
        abort_if($data['seats_requested'] > $ride->seats_total, 400, 'Нельзя запросить больше мест, чем доступно.');

        $booking = Booking::create([
            'ride_id' => $ride->id,
            'passenger_id' => $request->user()->id,
            'seats_requested' => $data['seats_requested'],
            'price_total' => $ride->price * $data['seats_requested'],
            'notes' => $data['notes'] ?? null,
        ]);

        $booking->load(['ride.driver', 'passenger']);

        // Создаем уведомление для водителя
        NotificationHelper::bookingCreated($booking->ride->driver, $booking);

        AuditLogger::log($request->user()->id, 'booking.create', [
            'entity_type' => 'Booking',
            'entity_id' => $booking->id,
            'meta' => ['ride_id' => $ride->id],
        ], $request);

        return response()->json($booking, 201);
    }

    public function updateStatus(UpdateBookingStatusRequest $request, Booking $booking)
    {
        $data = $request->validated();

        $user = $request->user();
        $oldStatus = $booking->status;

        if ($user->id === $booking->ride->driver_id) {
            $this->handleDriverStatusChange($booking, $data['status']);
        } elseif ($user->id === $booking->passenger_id && $data['status'] === 'cancelled') {
            $this->handlePassengerCancellation($booking);
        } else {
            abort(403, 'Вы не можете изменить статус этого бронирования.');
        }

        AuditLogger::log($request->user()->id, 'booking.status_change', [
            'entity_type' => 'Booking',
            'entity_id' => $booking->id,
            'meta' => ['from' => $oldStatus, 'to' => $data['status']],
        ], $request);

        return $booking->fresh()->load(['ride', 'passenger']);
    }

    protected function handleDriverStatusChange(Booking $booking, string $status): void
    {
        $booking->load(['ride', 'passenger']);

        if ($status === 'accepted') {
            abort_if(
                $booking->ride->seats_available < $booking->seats_requested,
                400,
                'Недостаточно свободных мест.'
            );

            $booking->ride->decrement('seats_available', $booking->seats_requested);
            
            // Создаем уведомление для пассажира
            NotificationHelper::bookingAccepted($booking->passenger, $booking);
        }

        if ($status === 'rejected') {
            // Создаем уведомление для пассажира
            NotificationHelper::bookingRejected($booking->passenger, $booking);
        }

        if (in_array($status, ['rejected', 'cancelled'], true) && $booking->status === 'accepted') {
            $booking->ride->increment('seats_available', $booking->seats_requested);
        }

        // При завершении поездки списываем баланс пассажира
        if ($status === 'completed' && $booking->status !== 'completed') {
            $this->processPayment($booking);
        }

        $booking->update(['status' => $status]);
    }

    protected function processPayment(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $passenger = $booking->passenger;
            $amount = $booking->price_total;

            // Проверяем достаточность баланса
            if ($passenger->balance < $amount) {
                abort(400, 'Недостаточно средств на балансе. Пожалуйста, пополните баланс.');
            }

            $balanceBefore = $passenger->balance;
            $balanceAfter = $balanceBefore - $amount;

            // Списываем баланс
            $passenger->update(['balance' => $balanceAfter]);

            // Создаем транзакцию
            BalanceTransaction::create([
                'user_id' => $passenger->id,
                'type' => 'payment',
                'amount' => -$amount, // Отрицательное значение для списания
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => "Оплата поездки: {$booking->ride->origin_city} → {$booking->ride->destination_city}",
                'ride_id' => $booking->ride_id,
                'booking_id' => $booking->id,
            ]);
        });
    }

    protected function handlePassengerCancellation(Booking $booking): void
    {
        $booking->load(['ride.driver', 'passenger']);

        if ($booking->status === 'accepted') {
            $booking->ride->increment('seats_available', $booking->seats_requested);
        }

        $booking->update(['status' => 'cancelled']);

        // Создаем уведомление для водителя
        NotificationHelper::bookingCancelled($booking->ride->driver, $booking, false);
    }
}

