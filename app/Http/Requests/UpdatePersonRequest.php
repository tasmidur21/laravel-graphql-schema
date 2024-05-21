<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePersonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'validation_rules' => [
                'user_id' => ['required', 'integer', 'min:0', 'max:18446744073709551615'],
                'slug' => ['required', 'string', 'min:1', 'max:255'],
                'category' => ['required', 'string', 'min:1', 'max:255'],
                'tag' => ['nullable', 'string', 'min:1', 'max:255'],
                'title' => ['required', 'string', 'min:1', 'max:255'],
                'body' => ['required', 'string', 'min:1'],
                'image' => ['nullable', 'string', 'min:1', 'max:255'],
                'status' => ['required', 'integer', 'min:0', 'max:255']
            ],
            'fields' => [
                'user_id' => [
                    'type' => 'Type::nonNull(Type::int())',
                    'description' => 'The user_id of the posts'
                ],
                'slug' => [
                    'type' => 'Type::nonNull(Type::string())',
                    'description' => 'The slug of the posts'
                ],
                'category' => [
                    'type' => 'Type::nonNull(Type::string())',
                    'description' => 'The category of the posts'
                ],
                'tag' => [
                    'type' => 'Type::string()',
                    'description' => 'The tag of the posts'
                ],
                'title' => [
                    'type' => 'Type::nonNull(Type::string())',
                    'description' => 'The title of the posts'
                ],
                'body' => [
                    'type' => 'Type::nonNull(Type::string())',
                    'description' => 'The body of the posts'
                ],
                'image' => [
                    'type' => 'Type::string()',
                    'description' => 'The image of the posts'
                ],
                'status' => [
                    'type' => 'Type::nonNull(Type::int())',
                    'description' => 'The status of the posts'
                ]
            ],
            'args' => [
                [
                    'name' => 'user_id',
                    'type' => 'Type::nonNull(Type::int())'
                ],
                [
                    'name' => 'slug',
                    'type' => 'Type::nonNull(Type::string())'
                ],
                [
                    'name' => 'category',
                    'type' => 'Type::nonNull(Type::string())'
                ],
                [
                    'name' => 'tag',
                    'type' => 'Type::string()'
                ],
                [
                    'name' => 'title',
                    'type' => 'Type::nonNull(Type::string())'
                ],
                [
                    'name' => 'body',
                    'type' => 'Type::nonNull(Type::string())'
                ],
                [
                    'name' => 'image',
                    'type' => 'Type::string()'
                ],
                [
                    'name' => 'status',
                    'type' => 'Type::nonNull(Type::int())'
                ]
            ]
        ];
    }
}
