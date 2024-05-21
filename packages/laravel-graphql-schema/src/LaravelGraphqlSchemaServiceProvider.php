<?php

namespace Tasmidur\LaravelGraphqlSchema;

use Illuminate\Support\ServiceProvider;
use Tasmidur\LaravelGraphqlSchema\Commands\GenerateSchemaCommand;
use Tasmidur\LaravelGraphqlSchema\Contracts\SchemaRulesResolverInterface;
use Tasmidur\LaravelGraphqlSchema\Exceptions\UnsupportedDbDriverException;
use Tasmidur\LaravelGraphqlSchema\Resolvers\SchemaRulesResolverMySql;
use Tasmidur\LaravelGraphqlSchema\Resolvers\SchemaRulesResolverPgSql;
use Tasmidur\LaravelGraphqlSchema\Resolvers\SchemaRulesResolverSqlite;

class LaravelGraphqlSchemaServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            __DIR__.'/../config/graphql-schema-rules.php', 'graphql-schema-rules'
        );
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateSchemaCommand::class,
            ]);
        }

        $this->app->bind(SchemaRulesResolverInterface::class, function ($app, $parameters) {
            $connection = config('database.default');
            $driver = config("database.connections.{$connection}.driver");

            $class = match ($driver) {
                'sqlite' => SchemaRulesResolverSqlite::class,
                'mysql' => SchemaRulesResolverMySql::class,
                'pgsql' => SchemaRulesResolverPgSql::class,
                default => throw new UnsupportedDbDriverException('This db driver is not supported: ' . $driver),
            };

            return new $class(...array_values($parameters));
        });
    }
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        //php artisan vendor:publish --tag=courier-config
        $this->publishes([
            __DIR__.'/../config/graphql-schema-rules.php' => config_path('graphql-schema-rules.php'),
        ],"graphql-schema-config");

    }


}
