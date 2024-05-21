<?php
namespace App\GraphQL\Types;
use App\Models\Post;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;
use GraphQL\Type\Definition\Type;
class PostType extends GraphQLType
{
    protected $attributes = [
        'name'          => 'Post',
        'description'   => 'The blog post type',
        'model'         => Post::class,
    ];
    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The id of the user'
            ],
            'user_id' => [
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'The  user_id of the user'
            ],
            'slug' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The  slug of the post'
            ],
            'category' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'The  category of the post'
            ],
            'tag' => [
                'type' =>Type::string(),
                'description' => 'The  tag of the post'
            ],
            'title' => [
                'type' =>Type::nonNull(Type::string()),
                'description' => 'The  title of the post'
            ],
            'body' => [
                'type' =>Type::nonNull(Type::string()),
                'description' => 'The  body of the post'
            ],
            'image' => [
                'type' =>Type::nonNull(Type::string()),
                'description' => 'The  image of the post'
            ],
            'status' => [
                'type' =>Type::nonNull(Type::int()),
                'description' => 'The  image of the post'
            ],
            'comments' => [
                'type' => Type::listOf(GraphQL::type('Comment')),
                'description' => 'The  comments of the post',
                'args'  => [
                    'commenter_id' => [
                        'type'  => Type::int()
                    ],
                ],
                'query' => function (array $args, $query) {
                    if (array_key_exists('commenter_id',$args)) {
                        return $query->where('commentable_id', '=', $args['commenter_id']);
                    }
                    return $query;
                },
            ]

        ];
    }

}
