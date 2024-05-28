<?php

namespace App\GraphQL\Queries;

use App\Models\Post;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class PostQuery extends Query
{

    protected $attributes = [
        'name' => 'posts',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('Post'))));
    }

    public function args(): array
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::string(),
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

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $fields = $getSelectFields();
        $whereConditions = [];
        foreach ($args as $key => $value) {
            if (Schema::hasColumn('posts', $key)) {
                $whereConditions = [
                    $key => $value
                ];
            }
        }
        $query = Post::query();
        $query->select();
        if (!empty($whereConditions)) {
            $query->where($whereConditions);
        }
        return $query->get();
    }
}
