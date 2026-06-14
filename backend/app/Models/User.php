<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'avatar_url',
        'bio',
        'rating_average',
        'rides_completed',
        'preferences',
        'balance',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'preferences' => 'array',
        'rating_average' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function ridesAsDriver()
    {
        return $this->hasMany(Ride::class, 'driver_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'passenger_id');
    }

    public function reviewsAuthored()
    {
        return $this->hasMany(Review::class, 'author_id');
    }

    public function reviewsReceived()
    {
        return $this->hasMany(Review::class, 'target_id');
    }

    public function balanceTransactions()
    {
        return $this->hasMany(BalanceTransaction::class);
    }

    public function rechargeRequests()
    {
        return $this->hasMany(BalanceRechargeRequest::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
