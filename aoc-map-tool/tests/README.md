# Test database (`aoc_test`)

> Looking for how to actually write a test, not set up the database? See
> `tests/CONTRIBUTING.md`.

`aoc_test` is a schema clone of the dev database (`aoc-database`), **not** built by running
migrations from scratch — the migration history is incomplete (some tables were created
outside of any migration file that still exists in the repo, and migrations sometimes ALTER
tables that no migration ever created). Running `php artisan migrate` on a truly empty
database fails partway through.

## How it actually works

`database/schema/mysql-schema.sql` is a full schema dump (all 253 tables + the current
`migrations` table content, via `php artisan schema:dump`) taken from the real dev database.
Laravel's own `migrate` command already knows how to bootstrap an empty database from a schema
dump like this — **except** its loader shells out to the `mysql` CLI binary, which isn't
installed in the app container. `php artisan test:build-db` works around that: it loads the
same dump via PDO (`DB::unprepared()`), then calls `migrate` normally — which sees the
`migrations` table already has rows and only applies whatever isn't reflected in the dump yet
(normally nothing, unless new migrations were added after the last dump).

## Building/rebuilding `aoc_test`

The `aoc_test` database itself (empty, with grants for `aoc_user`) needs to exist first —
that's a one-time, root-level step (see "First-time setup" below). Once it exists:

```
php artisan test:build-db
```

Drops every table in the target database (including ones the dump doesn't know about, e.g.
left over from an earlier partial run), recreates them from the dump, then migrates. Safe to re-run any time you want
a clean slate — it only touches `aoc_test` (or whatever `--database=` you pass), never the dev
database.

## Refreshing the dump

When schema changes accumulate and the dump goes stale, regenerate it from the dev database
(inside the app container isn't enough — it lacks `mysqldump`/`mysql`; run these from the
`mariadb` container instead, which has them):

```
docker exec aoc-map-tool-db sh -c \
  'mysqldump -uroot -proot --no-tablespaces --skip-add-locks --skip-comments --skip-set-charset --tz-utc "aoc-database" --routines --no-data' \
  > database/schema/mysql-schema.sql

# Laravel's schema:dump also strips AUTO_INCREMENT=N state — keep dumps deterministic:
perl -0pi -e 's/\s+AUTO_INCREMENT=[0-9]+//gi' database/schema/mysql-schema.sql

docker exec aoc-map-tool-db sh -c \
  'mysqldump -uroot -proot "aoc-database" migrations --no-create-info --skip-extended-insert --skip-routines --compact' \
  >> database/schema/mysql-schema.sql
```

This is exactly what `php artisan schema:dump` itself does (see
`Illuminate\Database\Schema\MySqlSchemaState::dump()`) — we can't call it directly because it
needs `mysqldump` on the machine actually running artisan (the app container), which doesn't
have it.

**If the dump ever includes stored routines** (functions/procedures — there's currently one,
`format_dates`, used by `app/Helpers/ClientCareCoordinatorCoverage.php` and
`ClientCareCoordinatorException.php`): mysqldump wraps routine bodies in `DELIMITER ;;` / `DELIMITER ;`
so the `mysql` CLI can tell the routine's internal semicolons apart from the statement
terminator. `DELIMITER` isn't real SQL — PDO chokes on it. Since we load via PDO
(`DB::unprepared()`), strip those two `DELIMITER` lines and change the routine's closing
`END ;;` back to `END ;`. The server-side parser handles the routine body correctly on its own
once the CLI-only delimiter juggling is removed. Also drop the `DEFINER=`user`@`host`` clause
if present — the loading DB user usually lacks the privilege to set an arbitrary definer.

## First-time setup (new machine / fresh Docker volume)

The `aoc_test` database + grants for `aoc_user` don't exist until created once as root — this
isn't scripted into the Docker image yet (`.docker/` here is generated from
`local-deployer-engine`; a `CREATE DATABASE IF NOT EXISTS aoc_test` + grant would need to be
added to the init SQL there to make this automatic on a brand new volume):

```
docker exec aoc-map-tool-db mysql -uroot -proot -e \
  "CREATE DATABASE IF NOT EXISTS aoc_test; GRANT ALL PRIVILEGES ON aoc_test.* TO 'aoc_user'@'%'; FLUSH PRIVILEGES;"
```

Then `php artisan test:build-db` as above.

## CI

`.github/workflows/phpunit.yml` runs the full suite on every pull request (open, reopen, and
every new commit). It follows the exact same two steps as a local first-time setup: the
MariaDB service container creates an empty `aoc_test` (via `MARIADB_DATABASE`), then
`php artisan test:build-db` loads the schema dump and migrates. No live dev database is
required — everything needed lives in this repo (`database/schema/mysql-schema.sql` +
migration files). Consequence: **when the dump goes stale enough that a migration can't apply
on top of it, CI breaks too** — refresh the dump as described above and commit it.
