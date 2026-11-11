## Why

The app currently shows two legacy names for the same review process — "CCR" / "Clinical Competency Review" in code and UI, and "PEPR" / "Pre-Employment Patient Review" in business language only (it does not exist anywhere in the codebase). Users see inconsistent terminology across dashboards, calendars, reports, and notifications for what is one underlying entity. This change standardizes all displayed terminology to "PRC" / "Patient Review Call" without altering workflows, permissions, or scheduling logic.

## What Changes

- Replace all user-facing and admin-facing occurrences of "CCR" → "PRC" and "Clinical Competency Review" → "Patient Review Call" in: page titles, section titles, buttons, field labels, column headers, dropdown options, tooltips, modal titles, validation/help text.
- Rename the "CCR Calendar" (the only calendar of its kind that exists in code; there is no separate "PEPR Calendar – Coverage") to "PRC Calendar".
- Rename the Recruiter Tracker status display value "Clinical Competency Review" to "Patient Review Call" for both existing and new records. **Design decision required**: whether this is done via a display-mapping layer (status text stays stored as-is internally, only rendering changes) or by changing the stored/synced value itself — see design.md.
- Update CSV export column headers (e.g. "CCR Request Date" → "PRC Request Date") and any report titles/labels using legacy terms.
- Update hardcoded email subject/body text and in-app notification titles that reference "CCR".
- Do **not** rename: route names (`ccrCalendar.*`, `ccrListing`, etc.), permission keys, the `resources/views/ccr/` folder, `CCRController` class name, `mssql_recruitertracker` table/column names, or the external MSSQL stored-procedure parameter names. These are internal/infrastructure identifiers, not displayed terminology, and are explicitly out of scope per the story ("existing workflows, permissions, business rules, scheduling logic, and navigation remain unchanged").
- Do **not** touch "PEPI"/"WEPI" — visually similar but unrelated abbreviations for a different step in the recruiting flow.

## Capabilities

### New Capabilities
- `prc-terminology`: Defines the display-terminology contract for the Patient Review Call process — what text must appear where (UI labels, calendar name, tracker status, exports, notifications) and what must NOT change (routes, permissions, schema, unrelated abbreviations).

### Modified Capabilities
(none — no existing specs in this repo yet; this is the first capability defined for this app)

## Impact

- ~68 files under `resources/views/` (primarily `ccr/`, `ccc/`, plus `account/dashboard.blade.php`, `layouts/newSidebar.blade.php`, `recruiterTracker/`, `reports/`) — text-literal changes only.
- `app/Http/Controllers/CCRController.php`, `app/Http/Controllers/Dashboard.php`, `app/Helpers/Activity.php`, `app/Console/Commands/SetConversionReportRecords.php` — the ~14 write-sites of the literal status string `'Clinical Competency Review'`.
- `app/SyncProcesses/SendDueCcrEmails.php` — email subject/body text.
- `config/permissions.php`, `config/featureflags.php` — display names/descriptions only (keys unchanged).
- No JS/Vue files are affected (this feature has no SPA layer).
- Indirect dependency: `app/Helpers/Sync.php` pushes `Candidate_Current_Status` to an external MSSQL system (`Excelss.RecruiterTrackerUpdate`/`Insert`). Whichever design option is chosen for the status value must account for this — see design.md.
