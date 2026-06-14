<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:accepted,rejected,cancelled,completed'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Поле "Статус" обязательно для заполнения.',
            'status.in' => 'Неверный статус. Доступные значения: accepted, rejected, cancelled, completed.',
        ];
    }
}
