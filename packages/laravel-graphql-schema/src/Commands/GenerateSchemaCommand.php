<?php

namespace Tasmidur\LaravelGraphqlSchema\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Tasmidur\LaravelGraphqlSchema\Contracts\SchemaRulesResolverInterface;
use Tasmidur\LaravelGraphqlSchema\Services\FileGeneratorService;
use Tasmidur\LaravelGraphqlSchema\Services\GraphQLTypeParser;
use Tasmidur\LaravelGraphqlSchema\Services\SchemaValidator;
use Tasmidur\LaravelGraphqlSchema\Helpers\GraphQLHelper;

class GenerateSchemaCommand extends Command
{
    protected $signature = 'make:graphql:schema-rules {table : The table of which you want to generate the rules}
               {--columns= : Only create rules for specific columns of the table}
               {--cf|create-file : Instead of outputting the schema rules, create GraphQL Type, Query, Mutation class}
               {--f|force : If "create" was given, then the request class gets created even if it already exists}';

    protected $description = 'Generate validation rules based on your database table schema';

    protected Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();
        $this->filesystem = $filesystem;
    }

    public function handle(): int
    {
        try {
            $table = (string)$this->argument('table');
            $columns = $this->parseColumns($this->option('columns'));
            $createFile = (bool)$this->option('create-file');
            $force = (bool)$this->option('force');

            $schemaValidator = new SchemaValidator();
            $schemaValidator->checkTableAndColumns($table, $columns);

            $rules = $this->generateSchemaRules($table, $columns);

            $graphQLTypeParser = new GraphQLTypeParser();
            $parsedRules = $graphQLTypeParser->parsedToGraphQLType($table, $rules);

            if ($createFile) {
                $fileGenerator = new FileGeneratorService($this->filesystem);
                $fileGenerator->createRequest($table, $parsedRules, $force);
            } else {
                $this->createOutput($table, $parsedRules);
            }

            return CommandAlias::SUCCESS;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return CommandAlias::FAILURE;
        }
    }

    private function parseColumns(?string $columnsOption): array
    {
        return $columnsOption ? array_filter(explode(',', $columnsOption)) : [];
    }

    private function generateSchemaRules(string $table, array $columns)
    {
        return app()->make(SchemaRulesResolverInterface::class, [
            'table' => $table,
            'columns' => $columns,
        ])->generate();
    }

    private function createOutput(string $table, array $rules): void
    {
        if (app()->runningInConsole()) {
            $this->info("GraphQL Schema type for table \"$table\" has been generated!");
            $this->info('Copy & paste these wherever your GraphQL type is defined:');
        }

        [$fields, $args, $validationRules] = $rules;
        $this->info('GraphQL Type Fields:');
        $this->line(GraphQLHelper::format($fields));
        $this->info('GraphQL Validation Rules:');
        $this->line(GraphQLHelper::format($validationRules));
        $this->info('GraphQL Query Arguments:');
        $this->line(GraphQLHelper::format($args));
    }
}
