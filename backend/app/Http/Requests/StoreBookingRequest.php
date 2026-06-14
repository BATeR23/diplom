<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'seats_requested' => ['required', 'integer', 'min:1', 'max:8'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'seats_requested.required' => 'Поле "Количество мест" обязательно для заполнения.',
            'seats_requested.integer' => 'Количество мест должно быть целым числом.',
            'seats_requested.min' => 'Минимальное количество мест: 1.',
            'seats_requested.max' => 'Максимальное количество мест: 8.',
            'notes.string' => 'Поле "Примечания" должно быть строкой.',
            'notes.max' => 'Поле "Примечания" не должно превышать 500 символов.',
        ];
    }
}
