<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ride extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'vehicle_id',
        'origin_city',
        'origin_address',
        'origin_lat',
        'origin_lng',
        'destination_city',
        'destination_address',
        'destination_lat',
        'destination_lng',
        'departure_time',
        'arrival_time',
        'price',
        'seats_total',
        'seats_available',
        'luggage_size',
        'pets_allowed',
        'smoking_allowed',
        'notes',
        'status',
    ];

    protected $casts = [
        'departure_time' => 'datetime',
        'arrival_time' => 'datetime',
        'price' => 'decimal:2',
        'pets_allowed' => 'boolean',
        'smoking_allowed' => 'boolean',
    ];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function messages()
    {
        return $this->hasMany(RideMessage::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}











