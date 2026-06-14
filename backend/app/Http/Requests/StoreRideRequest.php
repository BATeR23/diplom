<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'origin_city' => ['required', 'string', 'max:120'],
            'origin_address' => ['required', 'string', 'max:255'],
            'origin_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'origin_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'destination_city' => ['required', 'string', 'max:120'],
            'destination_address' => ['required', 'string', 'max:255'],
            'destination_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'destination_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'departure_time' => ['required', 'date', 'after:now'],
            'arrival_time' => ['nullable', 'date', 'after:departure_time'],
            'price' => ['required', 'numeric', 'min:0'],
            'seats_total' => ['required', 'integer', 'between:1,8'],
            'luggage_size' => ['required', 'in:small,medium,large'],
            'pets_allowed' => ['boolean'],
            'smoking_allowed' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'origin_city.required' => 'Поле "Город отправления" обязательно для заполнения.',
            'origin_city.string' => 'Поле "Город отправления" должно быть строкой.',
            'origin_city.max' => 'Поле "Город отправления" не должно превышать 120 символов.',
            'origin_address.required' => 'Поле "Адрес отправления" обязательно для заполнения.',
            'origin_address.string' => 'Поле "Адрес отправления" должно быть строкой.',
            'origin_address.max' => 'Поле "Адрес отправления" не должно превышать 255 символов.',
            'origin_lat.numeric' => 'Широта места отправления должна быть числом.',
            'origin_lat.between' => 'Широта должна быть между -90 и 90.',
            'origin_lng.numeric' => 'Долгота места отправления должна быть числом.',
            'origin_lng.between' => 'Долгота должна быть между -180 и 180.',
            'destination_city.required' => 'Поле "Город прибытия" обязательно для заполнения.',
            'destination_city.string' => 'Поле "Город прибытия" должно быть строкой.',
            'destination_city.max' => 'Поле "Город прибытия" не должно превышать 120 символов.',
            'destination_address.required' => 'Поле "Адрес прибытия" обязательно для заполнения.',
            'destination_address.string' => 'Поле "Адрес прибытия" должно быть строкой.',
            'destination_address.max' => 'Поле "Адрес прибытия" не должно превышать 255 символов.',
            'destination_lat.numeric' => 'Широта места прибытия должна быть числом.',
            'destination_lat.between' => 'Широта должна быть между -90 и 90.',
            'destination_lng.numeric' => 'Долгота места прибытия должна быть числом.',
            'destination_lng.between' => 'Долгота должна быть между -180 и 180.',
            'departure_time.required' => 'Поле "Время отправления" обязательно для заполнения.',
            'departure_time.date' => 'Поле "Время отправления" должно быть корректной датой и временем.',
            'departure_time.after' => 'Время отправления должно быть в будущем.',
            'arrival_time.date' => 'Поле "Время прибытия" должно быть корректной датой и временем.',
            'arrival_time.after' => 'Время прибытия должно быть после времени отправления.',
            'price.required' => 'Поле "Цена" обязательно для заполнения.',
            'price.numeric' => 'Поле "Цена" должно быть числом.',
            'price.min' => 'Цена не может быть отрицательной.',
            'seats_total.required' => 'Поле "Количество мест" обязательно для заполнения.',
            'seats_total.integer' => 'Количество мест должно быть целым числом.',
            'seats_total.between' => 'Количество мест должно быть от 1 до 8.',
            'luggage_size.required' => 'Поле "Размер багажа" обязательно для заполнения.',
            'luggage_size.in' => 'Неверный размер багажа. Доступные значения: small, medium, large.',
            'pets_allowed.boolean' => 'Поле "Разрешены животные" должно быть логическим значением.',
            'smoking_allowed.boolean' => 'Поле "Разрешено курение" должно быть логическим значением.',
            'notes.string' => 'Поле "Примечания" должно быть строкой.',
        ];
    }
}
