<?php

namespace App\GraphQL\Queries;
use App\Models\Comment;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;
class CommentQuery extends Query
{

    protected $attributes = [
        'name' => 'comments',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('Comment'))));
    }
    public function args(): array
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::string(),
            ],
            'commentable_type' => [
                'name' => 'slug',
                'type' => Type::string(),
            ],
            'comment' => [
                'name' => 'category',
                'type' => Type::string(),
            ]
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): Collection|array
    {
        $whereConditions = [];
        foreach ($args as $key => $value) {
            if(Schema::hasColumn('comments', $key)){
                $whereConditions = [
                    $key => $value
                ];
            }
        }
        $query = Comment::query();
        if (!empty($whereConditions)) {
           $query->where($whereConditions);
        }

        return $query->get();
    }
}
