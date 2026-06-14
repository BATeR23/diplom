<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', 'unique:users,email,' . $this->user()->id],
            'password' => ['sometimes', 'string', 'min:8'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'avatar' => ['sometimes', 'file', 'mimes:jpeg,jpg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Поле "Имя" обязательно для заполнения.',
            'name.string' => 'Поле "Имя" должно быть строкой.',
            'name.max' => 'Поле "Имя" не должно превышать 255 символов.',
            'email.required' => 'Поле "Email" обязательно для заполнения.',
            'email.email' => 'Поле "Email" должно быть корректным email адресом.',
            'email.max' => 'Поле "Email" не должно превышать 255 символов.',
            'email.unique' => 'Пользователь с таким email уже зарегистрирован.',
            'password.string' => 'Поле "Пароль" должно быть строкой.',
            'password.min' => 'Пароль должен содержать минимум 8 символов.',
            'bio.string' => 'Поле "О себе" должно быть строкой.',
            'bio.max' => 'Поле "О себе" не должно превышать 1000 символов.',
            'avatar.file' => 'Аватар должен быть файлом.',
            'avatar.mimes' => 'Аватар должен быть в формате JPG или PNG.',
            'avatar.max' => 'Размер файла аватара не должен превышать 5 МБ.',
        ];
    }
}
