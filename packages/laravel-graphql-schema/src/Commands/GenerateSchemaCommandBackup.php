<?php

namespace Tasmidur\LaravelGraphqlSchema\Commands;

use Brick\VarExporter\VarExporter;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Pluralizer;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Tasmidur\LaravelGraphqlSchema\Contracts\SchemaRulesResolverInterface;
use Tasmidur\LaravelGraphqlSchema\Exceptions\ColumnDoesNotExistException;
use Tasmidur\LaravelGraphqlSchema\Exceptions\FailedToCreateRequestClassException;
use Tasmidur\LaravelGraphqlSchema\Exceptions\MultipleTablesSuppliedException;
use Tasmidur\LaravelGraphqlSchema\Exceptions\TableDoesNotExistException;

class GenerateSchemaCommandBackup extends Command
{
    protected $signature = 'make:graphql:schema-rules {table : The table of which you want to generate the rules}
               {--columns= : Only create rules for specific columns of the table}
               {--cf|create-file : Instead of outputting the schema rules, create graphQl Type,Query,Mutation class}
               {--f|force : If "create" was given, then the request class gets created even if it already exists}';

    protected $description = 'Generate validation rules based on your database table schema';

    protected Filesystem $filesystem;
    protected string $facadeClone = "FACADE_DOUBLE_CLONE";
    protected string $clone = "CLONE";

    /**
     * Create a new command instance.
     * @param Filesystem $files
     */
    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
    }


    /**
     * @throws BindingResolutionException
     * @throws MultipleTablesSuppliedException
     * @throws TableDoesNotExistException
     * @throws ColumnDoesNotExistException
     * @throws FailedToCreateRequestClassException
     */
    public function handle(): int
    {
        // Arguments
        $table = (string)$this->argument('table');

        // Options
        $columns = (array)array_filter(explode(',', $this->option('columns')));
        $createFile = (bool)$this->option('create-file');
        $force = (bool)$this->option('force');


        $this->checkTableAndColumns($table, $columns);
        /**
         * [
         * $fields,
         * $args,
         * $validationRules
         * ]=$rules;
         */
        $rules = app()->make(SchemaRulesResolverInterface::class, [
            'table' => $table,
            'columns' => $columns,
        ])->generate();


        $rules = $this->parsedToGraphQLType($table, $rules);
        if ($createFile) {
            $this->createRequest($table, $rules, $force);
        } else {
            $this->createOutput($table, $rules);
        }

        return CommandAlias::SUCCESS;
    }

    private function format($rules): string
    {
        return VarExporter::export($rules, VarExporter::INLINE_SCALAR_LIST);
    }

    /**
     * @throws MultipleTablesSuppliedException
     * @throws ColumnDoesNotExistException
     * @throws TableDoesNotExistException
     */
    private function checkTableAndColumns(string $table, array $columns = []): void
    {
        if (count($tables = array_filter(explode(',', $table))) > 1) {
            $msg = 'The command can only handle one table at a time - you gave: ' . implode(', ', $tables);

            throw new MultipleTablesSuppliedException($msg);
        }

        if (!Schema::hasTable($table)) {
            throw new TableDoesNotExistException("Table '$table' not found!");
        }

        if (empty($columns)) {
            return;
        }

        $missingColumns = [];
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                $missingColumns[] = $column;
            }
        }

        if (!empty($missingColumns)) {
            $msg = "The following columns do not exists on the table '$table': " . implode(', ', $missingColumns);
            throw new ColumnDoesNotExistException($msg);
        }
    }

    private function createOutput(string $table, array $rules): void
    {
        if (app()->runningInConsole()) {
            $this->info("GraphQL Schema type for table \"$table\" have been generated!");
            $this->info('Copy & paste these where ever your graphql type takes place:');
        }
        [$fields, $args, $validationRules] = $rules;
        $this->info('GraphQl Type Fields:');
        $this->line($this->format($fields));
        $this->info('GraphQl Validation Rules:');
        $this->line($this->format($validationRules));
        $this->info('GraphQl Query arguments:');
        $this->line($this->format($args));

    }

    public function getSingularClassName($name): string
    {
        return ucwords(Pluralizer::singular($name));
    }

    public function getStubContents($stubVariables, string $stubFile): array|bool|string
    {
        $contents = file_get_contents(__DIR__ . "/../../stubs/$stubFile.stubs");

        foreach ($stubVariables as $search => $replace) {
            $contents = str_replace('$' . $search . '$', $replace, $contents);
        }

        return $contents;

    }

    private function parsedToGraphQLType(string $table, array $rules): array
    {
        $definition = [];
        $definition["validation_rules"] = $rules;
        foreach ($rules as $key => $rule) {
            $isRequired = $rule[0] === 'required';
            $attributeType = $rule[1] ?? null;

            $definition["fields"][$key] = [
                "type" => $this->getGraphQlType($attributeType, $isRequired),
                "description" => "The $key of the $table"
            ];
            $definition["args"][] = [
                "name" => $key,
                "type" => $this->getGraphQlArgs($attributeType)
            ];
        }
        return [
            $definition['fields'],
            $definition['args'],
            $definition['validation_rules']
        ];
    }

    /**
     */
    private function createRequest(string $table, array $rules, bool $force = false)
    {
        [$fields, $args, $validationRules] = $rules;
        $namespacePrefix = config('graphql-schema-rules.namespace_prefix');
        $fileDirectory = config('graphql-schema-rules.graphql_base_dir');
        $className = $this->getSingularClassName($table);
        $typeClass = $fileDirectory . DIRECTORY_SEPARATOR . "Types" . DIRECTORY_SEPARATOR . $className . "Type.php";
        $queryClass = $fileDirectory . DIRECTORY_SEPARATOR . "Queries" . DIRECTORY_SEPARATOR . $className . "Query.php";
        $mutationCreateClass = $fileDirectory . DIRECTORY_SEPARATOR . "Mutations" . DIRECTORY_SEPARATOR . $className . "Create.php";
        $mutationUpdateClass = $fileDirectory . DIRECTORY_SEPARATOR . "Mutations" . DIRECTORY_SEPARATOR . $className . "Update.php";

        $fieldParsed = str_replace(['"#', '#"', '{', '}', ':', $this->facadeClone], ['', '', '[', ']', '=>', '::'], json_encode($fields, JSON_PRETTY_PRINT));
        $argsParsed = str_replace(['"#', '#"', '{', '}', ':', $this->facadeClone], ['', '', '[', ']', '=>', '::'], json_encode($args, JSON_PRETTY_PRINT));

        $validationRuleForUpdate = str_replace(['": ', '{', '}', '\n'], ['"=>', "[", "]", '          \n'], json_encode($validationRules, JSON_PRETTY_PRINT));

        $validationRuleWithOutId = array_filter($validationRules, function ($key) {
            return $key !== "id";
        }, ARRAY_FILTER_USE_KEY);

        $validationRuleForCreate = str_replace(['": ', '{', '}', '\n'], ['"=>', "[", "]", '          \n'], json_encode($validationRuleWithOutId, JSON_PRETTY_PRINT));


        $typeContent = $this->getStubContents([
            "NAMESPACE" => $namespacePrefix . "\Types",
            "MODEL" => $className,
            "FIELDS" => $fieldParsed,
        ], "type");

        $queryContent = $this->getStubContents([
            "NAMESPACE" => $namespacePrefix . "\Queries",
            "MODEL" => $className,
            "TABLE" => $table,
            "ARGS" => $argsParsed,
        ], "query");

        $createArgs = [];
        foreach ($args as $arg) {
            if ($arg['name'] !== "id") {
                $createArgs[] = [
                    ...$arg,
                    "type" => $fields[$arg['name']]["type"]
                ];
            }
        }

        $updateArgs = array_map(function ($arg) use ($fields) {
            return [
                ...$arg,
                "type" => $fields[$arg['name']]["type"]
            ];
        }, $args);

        $createArgs = str_replace(['"#', '#"', '{', '}', ':', $this->facadeClone], ['', '', '[', ']', '=>', '::'], json_encode($createArgs, JSON_PRETTY_PRINT));
        $updateArgs = str_replace(['"#', '#"', '{', '}', ':', $this->facadeClone], ['', '', '[', ']', '=>', '::'], json_encode($updateArgs, JSON_PRETTY_PRINT));


        $createMutationContent = $this->getStubContents([
            "NAMESPACE" => $namespacePrefix . "\Mutations",
            "MODEL" => $className,
            "TABLE" => $table,
            "ARGS" => $createArgs,
            "VALIDATION_RULES" => $validationRuleForCreate,
        ], "create-mutation");

        $updateMutationContent = $this->getStubContents([
            "NAMESPACE" => $namespacePrefix . "\Mutations",
            "MODEL" => $className,
            "TABLE" => $table,
            "ARGS" => $updateArgs,
            "VALIDATION_RULES" => $validationRuleForUpdate
        ], "update-mutation");


        $this->storeFile($typeClass, $typeContent, $force);
        $this->storeFile($queryClass, $queryContent, $force);
        $this->storeFile($mutationCreateClass, $createMutationContent, $force);
        $this->storeFile($mutationUpdateClass, $updateMutationContent, $force);
    }

    private function storeFile(string $path, string $contents, bool $force = false): void
    {
        if (!$this->filesystem->isDirectory(dirname($path))) {
            $this->filesystem->makeDirectory($path, 0777, true, true);
        }

        if (!$force && $this->filesystem->exists($path)) {
            $this->info("Class : {$path} already exits");
        } else {
            $this->filesystem->put($path, $contents);
            $this->info("Class : {$path} created");
        }

    }

    private function getGraphQlType(mixed $attributeType, bool $isRequired): string
    {

        return match ($attributeType) {
            "integer" => $isRequired ? "#Type" . $this->facadeClone . "nonNull(Type" . $this->facadeClone . "int())#" : "#Type" . $this->facadeClone . "int()#",
            "numeric" => $isRequired ? "#Type" . $this->facadeClone . "nonNull(Type" . $this->facadeClone . "float())#" : "#Type" . $this->facadeClone . "float()#",
            "boolean" => $isRequired ? "#Type" . $this->facadeClone . "nonNull(Type" . $this->facadeClone . "boolean())#" : "#Type" . $this->facadeClone . "boolean()#",
            default => $isRequired ? "#Type" . $this->facadeClone . "nonNull(Type" . $this->facadeClone . "string())#" : "#Type" . $this->facadeClone . "string()#"
        };
    }

    private function getGraphQlArgs(mixed $attributeType): string
    {

        return match ($attributeType) {
            "integer" => "#Type" . $this->facadeClone . "int()#",
            "numeric" => "#Type" . $this->facadeClone . "float()#",
            "boolean" => "#Type" . $this->facadeClone . "boolean()#",
            default => "#Type" . $this->facadeClone . "string()#"
        };
    }

}
