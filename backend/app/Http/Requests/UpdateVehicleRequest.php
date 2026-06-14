<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'make' => ['sometimes', 'string', 'max:120'],
            'model' => ['sometimes', 'string', 'max:120'],
            'year' => ['nullable', 'integer', 'between:1980,' . (date('Y') + 1)],
            'seats' => ['sometimes', 'integer', 'between:1,8'],
            'color' => ['nullable', 'string', 'max:60'],
            'plate_number' => ['nullable', 'string', 'max:20'],
            'comfort_class' => ['sometimes', 'in:standard,comfort,premium'],
            'features' => ['nullable', 'array'],
            'allows_pets' => ['boolean'],
            'allows_smoking' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'make.string' => 'Поле "Марка" должно быть строкой.',
            'make.max' => 'Поле "Марка" не должно превышать 120 символов.',
            'model.string' => 'Поле "Модель" должно быть строкой.',
            'model.max' => 'Поле "Модель" не должно превышать 120 символов.',
            'year.integer' => 'Поле "Год" должно быть числом.',
            'year.between' => 'Поле "Год" должно быть между 1980 и ' . (date('Y') + 1) . '.',
            'seats.integer' => 'Количество мест должно быть числом.',
            'seats.between' => 'Количество мест должно быть от 1 до 8.',
            'color.string' => 'Поле "Цвет" должно быть строкой.',
            'color.max' => 'Поле "Цвет" не должно превышать 60 символов.',
            'plate_number.string' => 'Поле "Номер" должно быть строкой.',
            'plate_number.max' => 'Поле "Номер" не должно превышать 20 символов.',
            'comfort_class.in' => 'Неверный класс комфорта. Доступные значения: standard, comfort, premium.',
            'allows_pets.boolean' => 'Поле "Разрешены животные" должно быть логическим значением.',
            'allows_smoking.boolean' => 'Поле "Разрешено курение" должно быть логическим значением.',
        ];
    }
}
