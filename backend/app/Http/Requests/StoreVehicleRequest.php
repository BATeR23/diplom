<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'make' => ['required', 'string', 'max:120'],
            'model' => ['required', 'string', 'max:120'],
            'year' => ['nullable', 'integer', 'between:1980,' . (date('Y') + 1)],
            'seats' => ['required', 'integer', 'between:1,8'],
            'color' => ['nullable', 'string', 'max:60'],
            'plate_number' => ['nullable', 'string', 'max:20'],
            'comfort_class' => ['required', 'in:standard,comfort,premium'],
            'features' => ['nullable', 'array'],
            'allows_pets' => ['nullable'],
            'allows_smoking' => ['nullable'],
            'ownership_document' => ['required', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:10240'],
            'license_document' => ['required', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'make.required' => 'Поле "Марка" обязательно для заполнения.',
            'make.string' => 'Поле "Марка" должно быть строкой.',
            'make.max' => 'Поле "Марка" не должно превышать 120 символов.',
            'model.required' => 'Поле "Модель" обязательно для заполнения.',
            'model.string' => 'Поле "Модель" должно быть строкой.',
            'model.max' => 'Поле "Модель" не должно превышать 120 символов.',
            'year.integer' => 'Поле "Год" должно быть числом.',
            'year.between' => 'Поле "Год" должно быть между 1980 и ' . (date('Y') + 1) . '.',
            'seats.required' => 'Поле "Количество мест" обязательно для заполнения.',
            'seats.integer' => 'Количество мест должно быть числом.',
            'seats.between' => 'Количество мест должно быть от 1 до 8.',
            'color.string' => 'Поле "Цвет" должно быть строкой.',
            'color.max' => 'Поле "Цвет" не должно превышать 60 символов.',
            'plate_number.string' => 'Поле "Номер" должно быть строкой.',
            'plate_number.max' => 'Поле "Номер" не должно превышать 20 символов.',
            'comfort_class.required' => 'Поле "Класс комфорта" обязательно для заполнения.',
            'comfort_class.in' => 'Неверный класс комфорта. Доступные значения: standard, comfort, premium.',
            'ownership_document.required' => 'Необходимо загрузить документ о владении ТС.',
            'ownership_document.file' => 'Документ о владении ТС должен быть файлом.',
            'ownership_document.mimes' => 'Документ о владении ТС должен быть в формате JPG, PNG или PDF.',
            'ownership_document.max' => 'Размер файла документа о владении ТС не должен превышать 10 МБ.',
            'license_document.required' => 'Необходимо загрузить водительские права.',
            'license_document.file' => 'Водительские права должны быть файлом.',
            'license_document.mimes' => 'Водительские права должны быть в формате JPG, PNG или PDF.',
            'license_document.max' => 'Размер файла водительских прав не должен превышать 10 МБ.',
        ];
    }
}
