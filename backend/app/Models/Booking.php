<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'ride_id',
        'passenger_id',
        'seats_requested',
        'price_total',
        'status',
        'notes',
    ];

    protected $casts = [
        'price_total' => 'decimal:2',
    ];

    public function ride()
    {
        return $this->belongsTo(Ride::class);
    }

    public function passenger()
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    public function messages()
    {
        return $this->hasMany(RideMessage::class);
    }
}











