<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_id' => ['required', 'exists:users,id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'target_id.required' => 'Поле "Получатель отзыва" обязательно для заполнения.',
            'target_id.exists' => 'Указанный пользователь не существует.',
            'rating.required' => 'Поле "Оценка" обязательно для заполнения.',
            'rating.integer' => 'Оценка должна быть числом.',
            'rating.between' => 'Оценка должна быть от 1 до 5.',
            'comment.string' => 'Поле "Комментарий" должно быть строкой.',
            'comment.max' => 'Комментарий не должен превышать 2000 символов.',
        ];
    }
}
