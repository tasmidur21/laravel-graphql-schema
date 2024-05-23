<?php

namespace Tasmidur\LaravelGraphqlSchema\Commands;

use Brick\VarExporter\VarExporter;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Tasmidur\LaravelGraphqlSchema\Contracts\SchemaRulesResolverInterface;
use Tasmidur\LaravelGraphqlSchema\Exceptions\ColumnDoesNotExistException;
use Tasmidur\LaravelGraphqlSchema\Exceptions\FailedToCreateRequestClassException;
use Tasmidur\LaravelGraphqlSchema\Exceptions\MultipleTablesSuppliedException;
use Tasmidur\LaravelGraphqlSchema\Exceptions\TableDoesNotExistException;

class GenerateSchemaCommand extends Command
{
    protected $signature = 'schema:generate-rules {table : The table of which you want to generate the rules}
               {--columns= : Only create rules for specific columns of the table}
               {--c|create-request : Instead of outputting the rules, create a form request class}
               {--f|force : If "create" was given, then the request class gets created even if it already exists}
               {--file= : specify the file path where to create the request class}';

    protected $description = 'Generate validation rules based on your database table schema';

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
        $create = (bool)$this->option('create-request');
        $force = (bool)$this->option('force');
        $file = (string)$this->option('file');

        $this->checkTableAndColumns($table, $columns);

        $rules = app()->make(SchemaRulesResolverInterface::class, [
            'table' => $table,
            'columns' => $columns,
        ])->generate();

        if ($create) {
            $this->createRequest($table, json_decode(json_encode($this->parsedToGraphQLType($table, $rules)), true), $force, $file);
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
        //$this->line($this->format($this->parsedToGraphQLType($table, $rules)));
        $types = $this->parsedToGraphQLType($table, $rules);
        $contents = $this->getStubContents([
            "MODEL" => $this->getSingularClassName($table),
            "FIELDS" =>  str_replace(['"#', '#"','{', '}',':'], ['', '','[', ']','=>'],json_encode($types['fields'], JSON_PRETTY_PRINT))
        ]);
        $this->line($this->format($contents));
    }
    public function getSingularClassName($name)
    {
        return ucwords(Pluralizer::singular($name));
    }

    public function getStubContents($stubVariables = [])
    {
        $contents = file_get_contents(__DIR__ . "/../../stubs/type.stubs");

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
                "type" => $this->getGraphQlType($attributeType, $isRequired)
            ];
        }
        return $definition;
    }

    /**
     * @throws FailedToCreateRequestClassException
     */
    private function createRequest(string $table, array $rules, bool $force = false, string $file = '')
    {
        // As a default, we create a store request based on the table name.
        if (empty($file)) {
            $file = 'Store' . Str::of($table)->singular()->ucfirst()->__toString() . 'Request';
        }

        Artisan::call('make:request', [
            'name' => $file,
            '--force' => $force,
        ]);

        $output = trim(Artisan::output());

        preg_match('/\[(.*?)\]/', $output, $matches);

        // The original $file we passed to the command may have changed on creation validation inside the command.
        // We take the actual path which was used to create the file!
        $actuaFile = $matches[1] ?? null;

        if ($actuaFile) {
            try {
                $fileContent = File::get($actuaFile);
                // Add spaces to indent the array in the request class file.
                $rulesFormatted = str_replace("\n", "\n        ", $this->format($rules));
                $pattern = '/(public function rules\(\): array\n\s*{\n\s*return )\[.*\](;)/s';
                $replaceContent = preg_replace($pattern, '$1' . $rulesFormatted . '$2', $fileContent);
                File::put($actuaFile, $replaceContent);
            } catch (Exception $exception) {
                throw new FailedToCreateRequestClassException($exception->getMessage());
            }
        }

        if (Str::startsWith($output, 'INFO')) {
            $this->info($output);
        } else {
            $this->error($output);
        }
    }

    private function getGraphQlType(mixed $attributeType, bool $isRequired): string
    {
        return match ($attributeType) {
            "integer" => $isRequired ? "#Type::nonNull(Type::int())#" : "#Type::int()#",
            "numeric" => $isRequired ? "#Type::nonNull(Type::float())#" : "#Type::float()#",
            "boolean" => $isRequired ? "#Type::nonNull(Type::boolean())#" : "#Type::boolean()#",
            default => $isRequired ? "#Type::nonNull(Type::string())#" : "#Type::string()#"
        };
    }

    private function extractContentFromHashes($input): ?string
    {
        // Use a regular expression to extract the string between the hash symbols
        preg_match('/#(.*?)#/', $input, $matches);

        // Return the extracted string or null if no match is found
        return $matches[1];
    }

}
