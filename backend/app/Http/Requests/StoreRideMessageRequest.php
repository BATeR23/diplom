<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRideMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_id' => ['required', 'exists:bookings,id'],
            'body' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_id.required' => 'Поле "Бронирование" обязательно для заполнения.',
            'booking_id.exists' => 'Указанное бронирование не существует.',
            'body.required' => 'Поле "Сообщение" обязательно для заполнения.',
            'body.string' => 'Поле "Сообщение" должно быть строкой.',
            'body.max' => 'Сообщение не должно превышать 2000 символов.',
        ];
    }
}
