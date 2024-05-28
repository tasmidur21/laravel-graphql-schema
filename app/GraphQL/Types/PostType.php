<?php
namespace App\GraphQL\Types;
use App\Models\Post;
use Rebing\GraphQL\Support\Type as GraphQLType;
use GraphQL\Type\Definition\Type;
class PostType extends GraphQLType
{
    protected $attributes = [
        "name"          => "Post",
        "description"   => "The Post Type",
        "model"         => Post::class,
    ];
    public function fields(): array
    {
        return [
    "id"=> [
        "type"=> Type::nonNull(Type::int()),
        "description"=> "The id of the posts"
    ],
    "user_id"=> [
        "type"=> Type::nonNull(Type::int()),
        "description"=> "The user_id of the posts"
    ],
    "slug"=> [
        "type"=> Type::nonNull(Type::string()),
        "description"=> "The slug of the posts"
    ],
    "category"=> [
        "type"=> Type::nonNull(Type::string()),
        "description"=> "The category of the posts"
    ],
    "tag"=> [
        "type"=> Type::string(),
        "description"=> "The tag of the posts"
    ],
    "title"=> [
        "type"=> Type::nonNull(Type::string()),
        "description"=> "The title of the posts"
    ],
    "body"=> [
        "type"=> Type::nonNull(Type::string()),
        "description"=> "The body of the posts"
    ],
    "image"=> [
        "type"=> Type::string(),
        "description"=> "The image of the posts"
    ],
    "status"=> [
        "type"=> Type::nonNull(Type::int()),
        "description"=> "The status of the posts"
    ]
];
    }

}
