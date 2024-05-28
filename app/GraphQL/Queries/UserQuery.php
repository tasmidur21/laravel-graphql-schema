<?php

namespace App\GraphQL\Queries;

use App\Models\User;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class UserQuery extends Query
{

    protected $attributes = [
        'name' => 'users',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('User'))));
    }

    public function args(): array
    {
        return [
    [
        "name"=> "id",
        "type"=> Type::int()
    ],
    [
        "name"=> "name",
        "type"=> Type::string()
    ],
    [
        "name"=> "email",
        "type"=> Type::string()
    ],
    [
        "name"=> "email_verified_at",
        "type"=> Type::string()
    ],
    [
        "name"=> "password",
        "type"=> Type::string()
    ],
    [
        "name"=> "remember_token",
        "type"=> Type::string()
    ]
];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): Collection|array
    {
        $conditions = [];

        foreach ($args as $key => $value) {
            if ($value && Schema::hasColumn('users', $key)) {
                $conditions = [
                    $key => $value
                ];
            }
        }

        $queryBuilder = User::query();
        if (!empty($conditions)) {
            $queryBuilder->where($conditions);
        }
        return $queryBuilder->get();
    }
}
