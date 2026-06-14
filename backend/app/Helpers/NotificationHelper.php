<?php

namespace App\Helpers;

use App\Models\Notification;
use App\Models\User;

class NotificationHelper
{
    public static function create(User $user, string $type, string $title, string $message, array $data = []): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'read' => false,
        ]);
    }

    public static function balanceRechargeApproved(User $user, float $amount): Notification
    {
        return self::create(
            $user,
            'balance_recharge',
            'Пополнение баланса одобрено',
            "Ваш баланс пополнен на {$amount} ₽. Теперь вы можете бронировать поездки.",
            ['amount' => $amount]
        );
    }

    public static function balanceRechargeRejected(User $user, string $reason = null): Notification
    {
        $message = 'Ваш запрос на пополнение баланса был отклонен.';
        if ($reason) {
            $message .= " Причина: {$reason}";
        }
        
        return self::create(
            $user,
            'balance_recharge',
            'Пополнение баланса отклонено',
            $message
        );
    }

    public static function bookingCreated(User $driver, $booking): Notification
    {
        return self::create(
            $driver,
            'booking_created',
            'Новая заявка на поездку',
            "Пассажир {$booking->passenger->name} подал заявку на поездку {$booking->ride->origin_city} → {$booking->ride->destination_city}.",
            ['ride_id' => $booking->ride_id, 'booking_id' => $booking->id]
        );
    }

    public static function bookingAccepted(User $passenger, $booking): Notification
    {
        return self::create(
            $passenger,
            'booking_accepted',
            'Заявка принята',
            "Водитель принял вашу заявку на поездку {$booking->ride->origin_city} → {$booking->ride->destination_city}.",
            ['ride_id' => $booking->ride_id, 'booking_id' => $booking->id]
        );
    }

    public static function bookingRejected(User $passenger, $booking): Notification
    {
        return self::create(
            $passenger,
            'booking_rejected',
            'Заявка отклонена',
            "Водитель отклонил вашу заявку на поездку {$booking->ride->origin_city} → {$booking->ride->destination_city}.",
            ['ride_id' => $booking->ride_id, 'booking_id' => $booking->id]
        );
    }

    public static function bookingCancelled(User $user, $booking, bool $isDriver): Notification
    {
        $message = $isDriver
            ? "Пассажир отменил заявку на поездку {$booking->ride->origin_city} → {$booking->ride->destination_city}."
            : "Водитель отменил вашу заявку на поездку {$booking->ride->origin_city} → {$booking->ride->destination_city}.";

        return self::create(
            $user,
            'booking_cancelled',
            'Заявка отменена',
            $message,
            ['ride_id' => $booking->ride_id, 'booking_id' => $booking->id]
        );
    }

    public static function messageReceived(User $user, $message): Notification
    {
        return self::create(
            $user,
            'message_received',
            'Новое сообщение',
            "Вы получили новое сообщение от {$message->sender->name}.",
            ['ride_id' => $message->ride_id, 'booking_id' => $message->booking_id]
        );
    }

    public static function rideCancelled(User $user, $ride): Notification
    {
        return self::create(
            $user,
            'ride_cancelled',
            'Поездка отменена',
            "Поездка {$ride->origin_city} → {$ride->destination_city} была отменена.",
            ['ride_id' => $ride->id]
        );
    }

    public static function vehicleApproved(User $user, $vehicle): Notification
    {
        return self::create(
            $user,
            'vehicle_approved',
            'Автомобиль подтвержден',
            "Ваш автомобиль {$vehicle->make} {$vehicle->model} был подтвержден администратором. Теперь вы можете создавать поездки.",
            ['vehicle_id' => $vehicle->id]
        );
    }

    public static function vehicleRejected(User $user, $vehicle, string $reason = null): Notification
    {
        $message = "Ваш автомобиль {$vehicle->make} {$vehicle->model} был отклонен администратором.";
        if ($reason) {
            $message .= " Причина: {$reason}";
        }

        return self::create(
            $user,
            'vehicle_rejected',
            'Автомобиль отклонен',
            $message,
            ['vehicle_id' => $vehicle->id]
        );
    }
}
