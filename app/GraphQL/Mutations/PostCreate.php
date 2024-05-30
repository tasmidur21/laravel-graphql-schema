<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\Post;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class PostCreate extends Mutation
{
    protected $attributes = [
        'name' => 'postCreate',
        'description' => 'A mutation'
    ];

    public function type(): Type
    {
        return GraphQL::type('Post');
    }

    public function args(): array
    {
        return [
            'user_id' => [
                'name' => 'user_id',
                'type' => Type::int(),
            ],
            'slug' => [
                'name' => 'slug',
                'type' => Type::string(),
            ],
            'category' => [
                'name' => 'category',
                'type' => Type::string(),
            ],
            'tag' => [
                'name' => 'tag',
                'type' => Type::string(),
            ],
            'title' => [
                'name' => 'title',
                'type' => Type::string(),
            ],
            'body' => [
                'name' => 'body',
                'type' => Type::string(),
            ],
            'image' => [
                'name' => 'image',
                'type' => Type::string(),
            ],
            'status' => [
                'name' => 'status',
                'type' => Type::string(),
            ],
            'commenter_id' => [
                'type' => Type::int(),
                'description' => 'The  comments of the post'
            ]
        ];
    }

    protected function rules(array $args = []): array
    {
        return [
            'user_id' => ['required', 'integer', 'min:0', 'max:18446744073709551615'],
            'slug' => ['required', 'string', 'min:1', 'max:255'],
            'category' => ['required', 'string', 'min:1', 'max:255'],
            'tag' => ['nullable', 'string', 'min:1', 'max:255'],
            'title' => ['required', 'string', 'min:1', 'max:255'],
            'body' => ['required', 'string', 'min:1'],
            'image' => ['nullable', 'string', 'min:1', 'max:255'],
            'status' => ['required', 'integer', 'min:0', 'max:255']
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): Post
    {
        $post = new Post();
        $post->fill($args);
        $post->save();
        return $post;
    }
}
