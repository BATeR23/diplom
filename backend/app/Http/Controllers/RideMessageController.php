<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Http\Requests\StoreRideMessageRequest;
use App\Models\Booking;
use App\Models\Ride;
use App\Helpers\NotificationHelper;
use Illuminate\Http\Request;

class RideMessageController extends Controller
{
    public function index(Request $request, Ride $ride)
    {
        $this->authorizeRideParticipant($ride, $request->user()->id);

        $messages = $ride->messages()
            ->with(['sender', 'recipient'])
            ->latest()
            ->paginate(20);

        return $messages;
    }

    public function store(StoreRideMessageRequest $request, Ride $ride)
    {
        try {
            $data = $request->validated();

            $booking = Booking::where('ride_id', $ride->id)
                ->where('id', $data['booking_id'])
                ->first();

            if (!$booking) {
                return response()->json([
                    'message' => 'Бронирование не найдено или не принадлежит этой поездке.'
                ], 404);
            }

            $this->authorizeRideParticipant($ride, $request->user()->id, $booking);

            // Определяем получателя сообщения
            $recipientId = null;
            if ($request->user()->id === $ride->driver_id) {
                // Если отправитель - водитель, получатель - пассажир
                $recipientId = $booking->passenger_id;
            } else {
                // Если отправитель - пассажир, получатель - водитель
                $recipientId = $ride->driver_id;
            }

            if (!$recipientId) {
                return response()->json([
                    'message' => 'Не удалось определить получателя сообщения.'
                ], 400);
            }

            $message = $ride->messages()->create([
                'booking_id' => $booking->id,
                'sender_id' => $request->user()->id,
                'recipient_id' => $recipientId,
                'body' => $data['body'],
            ]);

            $message->load(['sender', 'recipient', 'ride']);

            // Создаем уведомление для получателя
            $recipient = \App\Models\User::find($recipientId);
            if ($recipient) {
                NotificationHelper::messageReceived($recipient, $message);
            }

            // Отправляем событие для real-time обновления
            MessageSent::dispatch($message);

            return response()->json($message, 201);
        } catch (\Exception $e) {
            \Log::error('Ошибка отправки сообщения: ' . $e->getMessage(), [
                'ride_id' => $ride->id,
                'user_id' => $request->user()->id,
                'booking_id' => $request->input('booking_id'),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка отправки сообщения: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function authorizeRideParticipant(Ride $ride, int $userId, ?Booking $booking = null): void
    {
        $isDriver = $ride->driver_id === $userId;
        $isPassenger = $booking
            ? $booking->passenger_id === $userId
            : $ride->bookings()->where('passenger_id', $userId)->exists();

        abort_unless($isDriver || $isPassenger, 403, 'Вы не участвуете в этой поездке.');
    }
}










