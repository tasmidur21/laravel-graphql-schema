<?php

namespace App\GraphQL\Schemas;

use App\GraphQL\Queries\PostQuery;
use App\GraphQL\Types\PostType;
use Rebing\GraphQL\Support\Contracts\ConfigConvertible;

class DefaultSchema implements ConfigConvertible
{
    public function toConfig(): array
    {
        return [
            'query' => [
                PostQuery::class
            ],

            'mutation' => [
                // ExampleMutation::class,
            ],

            'types' => [
                PostType::class
            ],
        ];
    }
}
