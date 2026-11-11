## Module Overview
`app/VBC` is a self-contained Value-Based Care module inside a Laravel 9 (PHP 8.2+) monolith.
It exposes a **pure REST API** consumed by a Vue 3 (Composition API) frontend — no Blade views, no jQuery.
Only backend development is in scope here.
Development principles: **SOLID, DRY, KISS**.

## Commands
# Static analysis — run inside the app container (level/scope come from phpstan.neon):
./vendor/bin/phpstan analyse app/VBC
# Expected when scoped to app/VBC: one "Ignored error pattern #PHPDoc type array of property# was not
# matched" — a project-wide ignore in phpstan.neon targets a property outside app/VBC. It is NOT a VBC
# error; VBC code itself analyses clean.

### Testing
All rules live in `tests/CLAUDE.md` (read it before writing a VBC test) — this section only adds what is
specific to the module.
- Run: in the app container `php vendor/bin/phpunit tests/Feature/VBC tests/Integration/VBC tests/Unit/VBC`.
- **Test DB is `aoc_test`**, a schema-only clone pinned by `phpunit.xml` — it holds no rows at all (no users,
  patients, facilities, lookup tables). `DatabaseTransactions` only, never `RefreshDatabase`.
- **Data comes from `tests/Support` only**: `UserFixture`, `MssqlPatientsFixture` / `MssqlFacilitiesFixture` (or
  `VbcPatientScenario` for a patient with its own facility and region), one Fixture per lookup table
  (`ContactTypeFixture`, `CareCoordinationTypeFixture`, `SickPlanCatalogFixture`, ...) and
  `HospitalizationTrackerPropertyDefinitionsScenario` for the tracker EAV definitions the write path requires. No
  `factory()`, no raw inserts, no `firstOrFail()` on pre-existing rows. A missing table gets a new Fixture.
- **Pin public contracts to literals** — assert `ALL_SORT_COLUMNS`, `Enum::list()`, response field keys and date
  formats as hardcoded strings. Never derive the expected value from the same constant the code uses (tautological —
  a rename would pass unnoticed). Values the test itself created are read back from the Fixture row, not retyped.
- Layers: repository behavioural tests (filters / search / sort / serialization), controller (Feature) tests that
  also cover request validation, enum contract tests. A Request class gets no dedicated test (the existing
  `Get*RequestTest` files predate this rule; do not add new ones).

### Module Structure
```
app/VBC/
├── Http/
│   ├── routes.php              # Registered by VBCServiceProvider; all routes under /vbc/* (no /api prefix)
│   ├── Controllers/            # Thin controllers — delegate to services/repositories
│   ├── Middleware/             # EnsurePermission (`vbc.permission`, see Auth)
│   ├── Requests/               # Form request validation (extend CoreRequest)
│   └── Resources/              # JSON API response transformers (JsonResource / ResourceCollection)
├── Models/                     # Eloquent models, one subdirectory per entity
├── Enum/                       # PHP 8.1 backed enums, one subdirectory per entity
├── Repositories/               # Read/Write repository pairs per entity
├── Rules/                      # Custom validation rules (named; conditions delegated to repositories)
├── Services/                   # Business logic
├── Jobs/                       # Queued jobs (e.g. CdcVaccine/ — see "CDC vaccine reference data")
├── DTO/                        # Data Transfer Objects
└── Common/
    └── Providers/VBCServiceProvider.php
```
Not everything the module uses lives under `app/VBC`: Artisan commands stay in `app/Console/Commands`
(e.g. `SyncCdcVaccines`, `vbc:cdc-vaccines-sync`) and mailables in `app/Mail/VBC`.
Code that doesn't belong to any single entity gets a `Common/` subfolder inside the relevant namespace
instead — `Http/Controllers/Common`, `Http/Requests/Common`, `Services/Common`, `DTO/Common`,
`Enum/Common` (see "Shared filter options" below). This is distinct from the top-level `Common/` above,
which only holds `VBCServiceProvider`.

### Key Patterns

**Enums** — Every enum that backs a user-facing set of values implements `label()`, `options()` (array of
`[value, label]`), and `list()` (key-value map). Enums that are pure metadata (e.g. field → storage column /
validation rules / casts, like `HospitalizationTrackerFieldEnum`) implement only what is used — such maps belong in
`Enum/`, not as a stateless "registry" class in `Services/`.
Eloquent models cast enum fields via `$casts`. In JSON Resources enum fields are returned as the label only:
`$model->status->label()`.

**Repositories** — Split into `ReadRepository` / `WriteRepository` per entity. Controllers and services never query
models directly. Inject the Eloquent model via constructor DI and call `->newQuery()` — never use static
`Model::query()`.

**Form Requests** — Extend `CoreRequest`. Validation in `rules()`. Expose `toDto()` returning a DTO — and nothing
else: controllers read data off the DTO, never off per-field accessor methods on the request. Use `sometimes`
(not `nullable`) for optional filter parameters. Map values with `$this->filled('key') ? $this->input('key') : default`.
Dependencies go in the **constructor** (followed by `parent::__construct()`) — never `app()` inside the request; see
`app/Http/Requests/Candidates/UpdateCandidateRequest.php` and `app/Http/Requests/PayerRates/StorePayerRateRequest.php`.
A field-specific condition belongs in a named `Rules/` class, not inline in the rules array.

**DTOs** — `final readonly class`. All sort column names as `SORT_*` named constants. `ALL_SORT_COLUMNS` is the single
source of truth used in `Rule::in()` for `sort_by` validation.

**JSON Resources** — `JsonResource` subclasses transform models for API responses. List endpoints use a paired
`ResourceCollection`.

**Module Bootstrap** — `VBCServiceProvider` loads `Http/routes.php` with `api` middleware and is registered in
`config/app.php`.

### Shared filter options (`Common/`)
`GET /vbc/filters` (route `filters.index`) → `Http/Controllers/Common/FiltersController.php` →
`Services/Common/FiltersService.php` — the single shared filter-options payload (states/branches/
regions/hospitalizationStatuses/followupStatuses/level/assignees/facility_names/export_types) consumed
by every ICM dashboard bucket (Hospitalization/Care Coordination/Follow-up), the All Hospitalizations page
and the All Immunizations page. No entity owns this data, so it lives under `Common/`, not any one entity's
controller.

`getOptions(?FilterBucketEnum $bucket = null)` takes an optional bucket
(`Enum/Common/FilterBucketEnum`: `HOSPITALIZATION` | `CARE_COORDINATION` | `FOLLOW_UP` | `IMMUNIZATION` | `IMMUNIZATION_SCHEDULE` | `ED_VISITS`,
validated by `Http/Requests/Common/GetFiltersRequest` → `DTO/Common/GetFiltersDTO`). It is a single `match`:
`IMMUNIZATION` gets its own slim payload (states/branches/vaccines/vaccineDoseStatuses/payers), `IMMUNIZATION_SCHEDULE` the options of the Add/Edit Immunization Schedule modal and of the page's filters (states/vaccineCategories/vaccines/recommendedFor/statuses/frequencies/doseAgeUnits; vaccines and categories are limited to CDC-`Active` vaccines, `CdcVaccineStatusEnum`), `HOSPITALIZATION`
gets the shared payload with `hospitalizationStatuses` narrowed to `HospitalizationTrackerStatusEnum::bucketCases()`
(the same list the bucket query uses), `ED_VISITS` the same with `HospitalizationTrackerStatusEnum::edVisitsBucketCases()`, every other bucket (and no bucket) gets the shared payload unchanged. A page that needs a different set
of options adds an arm and a private `xxxOptions()` method — the queries behind options a bucket doesn't use
must not run, so don't build the full payload first and trim it afterwards.

The Vue side caches this endpoint per bucket (`resources/js/vue/modules/vbc/services/filters.service.js`,
`getOptions(bucket)`): callers passing no bucket share one in-flight/resolved promise so multiple buckets
mounting at once cost one request, and each named bucket gets its own slot. A new bucket whose response
shape differs must be requested with `?bucket=` for that reason, or it would get back whichever shape was
cached first.

### Sorting pattern (ReadRepositories)
- All sort columns defined as `SORT_*` constants on the DTO; `ALL_SORT_COLUMNS` lists them all.
- `applySorting` uses a single `match ($sortBy)` — no `in_array` pre-check, no separate `DIRECT_SORT_COLUMNS`.
- Direct columns: comma-separated arms → `$query->orderBy("table.{$sortBy}", $direction)`
- Related columns: each arm joins via aliased joins (`sort_enc`, `sort_pat`, `sort_usr`, `sort_fac`, `sort_hosp`) then
  calls `orderBy`.
- Default arm covers unknown/fallback sort.
- When a sort arm adds a join, qualify the paginate select and any direct `where` on a column that also exists in a
  joined table with the base table: `paginate($perPage, [$model->getTable().'.*'])` and
  `whereIn($model->getTable().'.status', ...)`. `encounters` / `users` share `status`, `id`, `created_at`, so an
  unqualified `SELECT *` / `where` collides.

### Auth
Use `auth()->user()->id` or `auth()->id()` helper — do not inject `Illuminate\Contracts\Auth\Guard` into constructors.

**Permissions are enforced here, and only here.** The `vbc.permission` middleware
(`Http/Middleware/EnsurePermission.php`), applied to the whole `Http/routes.php` group in
`VBCServiceProvider`, checks the current route name against `config/permissions.php` — that is the
entire permission system for this module. Every new route must have its name added to the relevant
entry's `related` array or it 403s for everyone who isn't also granted the entry's own `route`. There is
no Gate/Policy layer and no permission check on the Vue side (see `resources/js/vue/CLAUDE.md`) — do not
add one there; a UI element being visible does not mean the backend will allow the action.

### Databases

- **MySQL** — primary application database
- **MSSQL** — external healthcare data (patients, facilities); connection key `mssql`
- **BigQuery** — analytics

### CDC vaccine reference data (`vbc:cdc-vaccines-sync`)
`cdc_vaccine_groups` and `cdc_vaccines` (category / vaccine options of the Immunization pages, e.g. the Add/Edit
Immunization Schedule modal) are filled from CDC XML files, not by migrations or seeders — an empty or
seeder-only table means the sync was never run.

```sh
php artisan vbc:cdc-vaccines-sync        # only queues SyncCdcVaccineGroupsJob
php artisan queue:work --stop-when-empty # runs it; processes both chained jobs, then exits
```
- Command `App\Console\Commands\SyncCdcVaccines` → `SyncCdcVaccineGroupsJob` (`CdcVaccineSyncService::syncGroups()`),
  which on success dispatches `SyncCdcVaccinesJob` (`syncVaccines()`). A failed groups job (3 tries, 60 s backoff)
  stops the run, so vaccines are not loaded.
- Source: URLs in `config/cdc_vaccines.php` (`CDC_VACCINE_GROUPS_URL`, `CDC_VACCINE_VACCINES_URL`). Writes go through
  `CdcVaccineGroupWriteRepository` / `CdcVaccineWriteRepository` (`upsertMany`) on the default MySQL connection; no
  dedicated DB connection.
- `QUEUE_CONNECTION=database` uses the `jobs_manager` table (not `jobs`); failures land in `failed_jobs`. Without a
  running worker the command appears to do nothing — jobs just sit in the queue.
- A vaccine is stored with `cvx_group_code` only when its group already exists; otherwise it is stored without a group
  and is not selectable in the schedule modal. Groups without an `aoc_name` trigger a notification email to
  `CDC_VACCINE_NOTIFY_EMAIL` (empty = no email); labels fall back to `cdc_name`.
- Scheduled monthly (`0 3 1 * *`, `CDC_VACCINE_SCHEDULE_ENABLED`) in `app/Console/Kernel.php`.
- `ImmunizationScheduleSeeder` sets `cvx_group_code` and names on some CVX codes; re-running it after a sync overwrites
  them.

### Feature Flags (LaunchDarkly)

```php
use App\Helpers\LD;

if (LD::checkFeatureFlag('flag-key')) { ... }
```

Flag keys are defined in `config/featureflags.php`.

## Code Standards
PSR-12 via PHP-CS-Fixer. PHPStan level 4 with Larastan. Run `composer run sniff` before committing.

Always use `use` imports instead of full class paths — in code, PHPDoc, and annotations. Never write
`\App\Some\ClassName` inline; import it at the top and use the short name.
