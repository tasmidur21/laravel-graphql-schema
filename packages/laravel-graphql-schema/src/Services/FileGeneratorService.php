<?php

namespace Tasmidur\LaravelGraphqlSchema\Services;

use Illuminate\Filesystem\Filesystem;
use Tasmidur\LaravelGraphqlSchema\Helpers\GraphQLHelper;
use Illuminate\Console\Concerns\InteractsWithIO;
use Symfony\Component\Console\Output\ConsoleOutput;

class FileGeneratorService
{
    use InteractsWithIO;
    protected Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->output = new ConsoleOutput();
    }

    public function createRequest(string $table, array $rules, bool $force = false): void
    {
        [$fields, $args, $validationRules] = $rules;
        $namespacePrefix = config('graphql-schema-rules.namespace_prefix');
        $fileDirectory = config('graphql-schema-rules.graphql_base_dir');
        $className = GraphQLHelper::getSingularClassName($table);

        $paths = $this->getFilePaths($fileDirectory, $className);
        $parsedData = $this->parseRulesAndArgs($fields, $args, $validationRules);

        $this->generateFiles($namespacePrefix, $className, $table, $parsedData, $paths, $force);
    }

    private function getFilePaths(string $fileDirectory, string $className): array
    {
        return [
            'type' => $fileDirectory . DIRECTORY_SEPARATOR . "Types" . DIRECTORY_SEPARATOR . $className . "Type.php",
            'query' => $fileDirectory . DIRECTORY_SEPARATOR . "Queries" . DIRECTORY_SEPARATOR . $className . "Query.php",
            'mutationCreate' => $fileDirectory . DIRECTORY_SEPARATOR . "Mutations" . DIRECTORY_SEPARATOR . $className . "Create.php",
            'mutationUpdate' => $fileDirectory . DIRECTORY_SEPARATOR . "Mutations" . DIRECTORY_SEPARATOR . $className . "Update.php",
        ];
    }

    private function parseRulesAndArgs(array $fields, array $args, array $validationRules): array
    {
        return [
            'fields' => GraphQLHelper::formatForFile($fields),
            'args' => GraphQLHelper::formatForFile($args),
            'validationRulesForCreate' => GraphQLHelper::formatForFile(array_filter($validationRules, fn($key) => $key !== "id", ARRAY_FILTER_USE_KEY)),
            'validationRulesForUpdate' => GraphQLHelper::formatForFile($validationRules)
        ];
    }

    private function generateFiles(string $namespacePrefix, string $className, string $table, array $parsedData, array $paths, bool $force): void
    {
        $this->storeFile(
            $paths['type'],
            GraphQLHelper::getStubContents(["NAMESPACE" => "$namespacePrefix\\Types", "MODEL" => $className, "FIELDS" => $parsedData['fields']], "type"),
            $force
        );

        $this->storeFile(
            $paths['query'],
            GraphQLHelper::getStubContents(["NAMESPACE" => "$namespacePrefix\\Queries", "MODEL" => $className, "TABLE" => $table, "ARGS" => $parsedData['args']], "query"),
            $force
        );

        $this->storeFile(
            $paths['mutationCreate'],
            GraphQLHelper::getStubContents([
                "NAMESPACE" => "$namespacePrefix\\Mutations",
                "MODEL" => $className,
                "TABLE" => $table,
                "ARGS" => $parsedData['args'],
                "VALIDATION_RULES" => $parsedData['validationRulesForCreate']
            ], "create-mutation"),
            $force
        );

        $this->storeFile(
            $paths['mutationUpdate'],
            GraphQLHelper::getStubContents([
                "NAMESPACE" => "$namespacePrefix\\Mutations",
                "MODEL" => $className,
                "TABLE" => $table,
                "ARGS" => $parsedData['args'],
                "VALIDATION_RULES" => $parsedData['validationRulesForUpdate']
            ], "update-mutation"),
            $force
        );
    }

    private function storeFile(string $path, string $contents, bool $force): void
    {
        if (!$this->filesystem->isDirectory(dirname($path))) {
            $this->filesystem->makeDirectory(dirname($path), 0777, true, true);
        }

        if (!$force && $this->filesystem->exists($path)) {
            $this->info("Class : {$path} already exists");
        } else {
            $this->filesystem->put($path, $contents);
            $this->info("Class : {$path} created");
        }
    }
}
