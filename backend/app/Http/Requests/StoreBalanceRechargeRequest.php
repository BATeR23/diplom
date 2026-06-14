<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBalanceRechargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1', 'max:10000'],
            'receipt' => ['required', 'file', 'mimes:pdf', 'max:10240'], // Максимум 10MB
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Поле "Сумма" обязательно для заполнения.',
            'amount.numeric' => 'Поле "Сумма" должно быть числом.',
            'amount.min' => 'Минимальная сумма пополнения составляет 1 рубль.',
            'amount.max' => 'Максимальная сумма пополнения составляет 10000 рублей.',
            'receipt.required' => 'Необходимо загрузить чек (PDF файл).',
            'receipt.file' => 'Чек должен быть файлом.',
            'receipt.mimes' => 'Чек должен быть в формате PDF.',
            'receipt.max' => 'Размер файла чека не должен превышать 10 МБ.',
        ];
    }
}
