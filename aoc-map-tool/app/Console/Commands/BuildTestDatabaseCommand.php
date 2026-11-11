<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BuildTestDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:build-db
                            {--database=aoc_test : Target database to build/rebuild}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop all tables in the target database, then build it from database/schema/mysql-schema.sql '
        . '(see php artisan schema:dump), then run any migrations not yet reflected in that dump';

    public function handle(): int
    {
        $database = $this->option('database');

        $this->pointConnectionAt($database);

        $this->info("Target database: {$database}");

        // The dump only recreates tables it knows about; tables left over from an earlier
        // (partial) run would make the following migrations fail with "already exists".
        $this->dropExistingTables();

        // Laravel's own migrate --schema-path loader shells out to the `mysql` CLI binary,
        // which isn't installed in the app container — load the dump via PDO instead, then
        // let migrate() see the already-populated `migrations` table and only run whatever
        // isn't reflected in the dump yet.
        $this->loadSchemaDump();

        $this->call('migrate', ['--force' => true, '--database' => 'mysql']);

        $this->info('Done.');

        return 0;
    }

    private function pointConnectionAt(string $database): void
    {
        config(['database.connections.mysql.database' => $database]);
        DB::purge('mysql');
        DB::reconnect('mysql');
    }

    private function dropExistingTables(): void
    {
        $this->info('Dropping existing tables...');

        DB::connection('mysql')->getSchemaBuilder()->dropAllTables();
    }

    private function loadSchemaDump(): void
    {
        $path = database_path('schema/mysql-schema.sql');

        if (!file_exists($path)) {
            $this->warn("No schema dump at {$path} — run `php artisan schema:dump` against a "
                . 'populated database first (see tests/README.md).');

            return;
        }

        $this->info('Loading schema dump...');

        DB::connection('mysql')->unprepared(file_get_contents($path));
    }
}
