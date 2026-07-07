<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Throwable;

class DatabaseSchemaExplorer
{
    public function inspect(): array
    {
        $database = DB::getDatabaseName();
        $driver = DB::getDriverName();
        $connectionName = DB::getDefaultConnection();
        $configuredConnections = array_keys(config('database.connections', []));

        if ($driver === 'mysql') {
            return $this->inspectMySql($database, $connectionName, $configuredConnections);
        }

        if ($driver === 'sqlite') {
            return $this->inspectSqlite($database, $connectionName, $configuredConnections);
        }

        return [
            'connection_name' => $connectionName,
            'driver' => $driver,
            'database' => $database,
            'configured_connections' => $configuredConnections,
            'tables' => [],
            'views' => [],
            'triggers' => [],
            'procedures' => [],
            'foreign_keys' => [],
            'relationships' => [],
            'generated_at' => now()->toIso8601String(),
            'note' => 'Schema explorer currently supports MySQL and SQLite introspection.',
        ];
    }

    protected function inspectMySql(string $database, string $connectionName, array $configuredConnections): array
    {
        $tables = DB::select(
            'SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_type = ? ORDER BY table_name',
            [$database, 'BASE TABLE']
        );

        $views = DB::select(
            'SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_type = ? ORDER BY table_name',
            [$database, 'VIEW']
        );

        $columns = DB::select(
            'SELECT table_name, column_name, ordinal_position, data_type, column_type, is_nullable, column_default, column_key, extra, column_comment
             FROM information_schema.columns
             WHERE table_schema = ?
             ORDER BY table_name, ordinal_position',
            [$database]
        );

        $indexes = DB::select(
            'SELECT table_name, index_name, non_unique, seq_in_index, column_name
             FROM information_schema.statistics
             WHERE table_schema = ?
             ORDER BY table_name, index_name, seq_in_index',
            [$database]
        );

        $foreignKeys = DB::select(
            'SELECT
                kcu.constraint_name,
                kcu.table_name,
                kcu.column_name,
                kcu.referenced_table_name,
                kcu.referenced_column_name,
                rc.update_rule,
                rc.delete_rule
             FROM information_schema.key_column_usage kcu
             LEFT JOIN information_schema.referential_constraints rc
                ON rc.constraint_schema = kcu.constraint_schema
                AND rc.constraint_name = kcu.constraint_name
             WHERE kcu.table_schema = ?
               AND kcu.referenced_table_name IS NOT NULL
             ORDER BY kcu.table_name, kcu.constraint_name, kcu.ordinal_position',
            [$database]
        );

        $triggers = DB::select(
            'SELECT trigger_name, event_manipulation, event_object_table, action_timing
             FROM information_schema.triggers
             WHERE trigger_schema = ?
             ORDER BY event_object_table, trigger_name',
            [$database]
        );

        try {
            $procedures = DB::select(
                'SELECT routine_name, routine_type, data_type
                 FROM information_schema.routines
                 WHERE routine_schema = ?
                 ORDER BY routine_type, routine_name',
                [$database]
            );
        } catch (Throwable) {
            // Some MariaDB/XAMPP setups can have stale mysql.proc metadata.
            // Keep schema export available by skipping routine introspection.
            $procedures = [];
        }

        $tableMap = [];
        foreach ($tables as $row) {
            $tableMap[$row->table_name] = [
                'name' => $row->table_name,
                'columns' => [],
                'primary_keys' => [],
                'indexes' => [],
                'foreign_keys' => [],
            ];
        }

        foreach ($columns as $col) {
            if (!isset($tableMap[$col->table_name])) {
                continue;
            }

            $tableMap[$col->table_name]['columns'][] = [
                'name' => $col->column_name,
                'position' => (int) $col->ordinal_position,
                'data_type' => $col->data_type,
                'column_type' => $col->column_type,
                'nullable' => $col->is_nullable === 'YES',
                'default' => $col->column_default,
                'key' => $col->column_key,
                'extra' => $col->extra,
                'comment' => $col->column_comment,
            ];

            if ($col->column_key === 'PRI') {
                $tableMap[$col->table_name]['primary_keys'][] = $col->column_name;
            }
        }

        foreach ($indexes as $idx) {
            if (!isset($tableMap[$idx->table_name])) {
                continue;
            }

            if (!isset($tableMap[$idx->table_name]['indexes'][$idx->index_name])) {
                $tableMap[$idx->table_name]['indexes'][$idx->index_name] = [
                    'name' => $idx->index_name,
                    'unique' => (int) $idx->non_unique === 0,
                    'columns' => [],
                ];
            }

            $tableMap[$idx->table_name]['indexes'][$idx->index_name]['columns'][] = $idx->column_name;
        }

        $relationships = [];
        foreach ($foreignKeys as $fk) {
            if (!isset($tableMap[$fk->table_name])) {
                continue;
            }

            $normalized = [
                'constraint' => $fk->constraint_name,
                'table' => $fk->table_name,
                'column' => $fk->column_name,
                'references_table' => $fk->referenced_table_name,
                'references_column' => $fk->referenced_column_name,
                'on_update' => $fk->update_rule,
                'on_delete' => $fk->delete_rule,
            ];

            $tableMap[$fk->table_name]['foreign_keys'][] = $normalized;
            $relationships[] = [
                'from' => $fk->table_name,
                'to' => $fk->referenced_table_name,
                'label' => $fk->column_name . ' -> ' . $fk->referenced_column_name,
                'constraint' => $fk->constraint_name,
            ];
        }

        foreach ($tableMap as &$table) {
            $table['indexes'] = array_values($table['indexes']);
        }

        return [
            'connection_name' => $connectionName,
            'driver' => 'mysql',
            'database' => $database,
            'configured_connections' => $configuredConnections,
            'tables' => array_values($tableMap),
            'views' => array_map(fn ($v) => ['name' => $v->table_name], $views),
            'triggers' => array_map(fn ($t) => [
                'name' => $t->trigger_name,
                'table' => $t->event_object_table,
                'event' => $t->event_manipulation,
                'timing' => $t->action_timing,
            ], $triggers),
            'procedures' => array_map(fn ($p) => [
                'name' => $p->routine_name,
                'type' => $p->routine_type,
                'return_type' => $p->data_type,
            ], $procedures),
            'foreign_keys' => array_map(fn ($fk) => [
                'constraint' => $fk->constraint_name,
                'table' => $fk->table_name,
                'column' => $fk->column_name,
                'references_table' => $fk->referenced_table_name,
                'references_column' => $fk->referenced_column_name,
                'on_update' => $fk->update_rule,
                'on_delete' => $fk->delete_rule,
            ], $foreignKeys),
            'relationships' => $relationships,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    protected function inspectSqlite(string $database, string $connectionName, array $configuredConnections): array
    {
        $tablesRaw = DB::select("SELECT name, type FROM sqlite_master WHERE type IN ('table','view') AND name NOT LIKE 'sqlite_%' ORDER BY name");

        $tables = [];
        $views = [];
        foreach ($tablesRaw as $obj) {
            if ($obj->type === 'view') {
                $views[] = ['name' => $obj->name];
                continue;
            }

            $cols = DB::select('PRAGMA table_info("' . str_replace('"', '""', $obj->name) . '")');
            $indexes = DB::select('PRAGMA index_list("' . str_replace('"', '""', $obj->name) . '")');
            $fks = DB::select('PRAGMA foreign_key_list("' . str_replace('"', '""', $obj->name) . '")');

            $tables[] = [
                'name' => $obj->name,
                'columns' => array_map(fn ($c) => [
                    'name' => $c->name,
                    'position' => (int) $c->cid + 1,
                    'data_type' => $c->type,
                    'column_type' => $c->type,
                    'nullable' => (int) $c->notnull === 0,
                    'default' => $c->dflt_value,
                    'key' => (int) $c->pk === 1 ? 'PRI' : '',
                    'extra' => '',
                    'comment' => '',
                ], $cols),
                'primary_keys' => array_values(array_map(fn ($c) => $c->name, array_filter($cols, fn ($c) => (int) $c->pk === 1))),
                'indexes' => array_map(fn ($i) => [
                    'name' => $i->name,
                    'unique' => (int) $i->unique === 1,
                    'columns' => [],
                ], $indexes),
                'foreign_keys' => array_map(fn ($f) => [
                    'constraint' => 'fk_' . $obj->name . '_' . $f->id,
                    'table' => $obj->name,
                    'column' => $f->from,
                    'references_table' => $f->table,
                    'references_column' => $f->to,
                    'on_update' => $f->on_update,
                    'on_delete' => $f->on_delete,
                ], $fks),
            ];
        }

        $relationships = [];
        foreach ($tables as $table) {
            foreach ($table['foreign_keys'] as $fk) {
                $relationships[] = [
                    'from' => $fk['table'],
                    'to' => $fk['references_table'],
                    'label' => $fk['column'] . ' -> ' . $fk['references_column'],
                    'constraint' => $fk['constraint'],
                ];
            }
        }

        return [
            'connection_name' => $connectionName,
            'driver' => 'sqlite',
            'database' => $database,
            'configured_connections' => $configuredConnections,
            'tables' => $tables,
            'views' => $views,
            'triggers' => [],
            'procedures' => [],
            'foreign_keys' => array_values(array_merge(...array_map(fn ($t) => $t['foreign_keys'], $tables))),
            'relationships' => $relationships,
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
