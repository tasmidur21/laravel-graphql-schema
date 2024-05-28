<?php
namespace App\GraphQL\Types;
use App\Models\User;
use Rebing\GraphQL\Support\Type as GraphQLType;
use GraphQL\Type\Definition\Type;
class UserType extends GraphQLType
{
    protected $attributes = [
        "name"          => "User",
        "description"   => "The User Type",
        "model"         => User::class,
    ];
    public function fields(): array
    {
        return [
    "id"=> [
        "type"=> Type::nonNull(Type::int()),
        "description"=> "The id of the users"
    ],
    "name"=> [
        "type"=> Type::nonNull(Type::string()),
        "description"=> "The name of the users"
    ],
    "email"=> [
        "type"=> Type::nonNull(Type::string()),
        "description"=> "The email of the users"
    ],
    "email_verified_at"=> [
        "type"=> Type::string(),
        "description"=> "The email_verified_at of the users"
    ],
    "password"=> [
        "type"=> Type::nonNull(Type::string()),
        "description"=> "The password of the users"
    ],
    "remember_token"=> [
        "type"=> Type::string(),
        "description"=> "The remember_token of the users"
    ]
];
    }

}
