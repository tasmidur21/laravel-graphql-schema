<?php
namespace App\GraphQL\Types;
use App\Models\Comment;
use Rebing\GraphQL\Support\Type as GraphQLType;
use GraphQL\Type\Definition\Type;
class CommentType extends GraphQLType
{
    protected $attributes = [
        'name'          => 'Comment',
        'description'   => 'The comment type',
        'model'         => Comment::class,
    ];
    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The id of the user'
            ],
            'commentable_id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'The  user_id of the user'
            ],
            'commentable_type' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The  slug of the post'
            ],
            "comment" => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The  slug of the post'
            ]
        ];
    }

}
