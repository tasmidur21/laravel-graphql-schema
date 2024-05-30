<?php

namespace Tasmidur\LaravelGraphqlSchema\Services;

use Illuminate\Support\Facades\Schema;
use Tasmidur\LaravelGraphqlSchema\Exceptions\ColumnDoesNotExistException;
use Tasmidur\LaravelGraphqlSchema\Exceptions\MultipleTablesSuppliedException;
use Tasmidur\LaravelGraphqlSchema\Exceptions\TableDoesNotExistException;

class SchemaValidator
{
    public function checkTableAndColumns(string $table, array $columns = []): void
    {
        $this->checkMultipleTables($table);
        $this->checkTableExists($table);
        $this->checkColumnsExist($table, $columns);
    }

    private function checkMultipleTables(string $table): void
    {
        if (count($tables = array_filter(explode(',', $table))) > 1) {
            throw new MultipleTablesSuppliedException('The command can only handle one table at a time - you gave: ' . implode(', ', $tables));
        }
    }

    private function checkTableExists(string $table): void
    {
        if (!Schema::hasTable($table)) {
            throw new TableDoesNotExistException("Table '$table' not found!");
        }
    }

    private function checkColumnsExist(string $table, array $columns): void
    {
        if (empty($columns)) {
            return;
        }

        $missingColumns = array_filter($columns, fn($column) => !Schema::hasColumn($table, $column));
        if (!empty($missingColumns)) {
            throw new ColumnDoesNotExistException("The following columns do not exist on the table '$table': " . implode(', ', $missingColumns));
        }
    }
}
