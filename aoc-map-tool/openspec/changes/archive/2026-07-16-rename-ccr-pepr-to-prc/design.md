## Context

The app displays the same underlying process under two legacy names: "CCR"/"Clinical Competency Review" (the only form that actually exists in code) and "PEPR"/"Pre-Employment Patient Review" (business language only, absent from the codebase). Both the Recruiting Dashboard and the Clinical Care Dashboard render this via the same `resources/views/ccr/` templates, `CCRController`, and `mssql_recruitertracker` table — one entity, two dashboard presentations.

Most occurrences are free-text UI strings (blade templates, hardcoded email/notification text, CSV headers) with no data-layer involvement. One occurrence is different in kind: the Recruiter Tracker status `Candidate_Current_Status = 'Clinical Competency Review'` is:
1. Set as a literal string in ~14 write-sites (`Dashboard.php`, `Activity.php`, `CCRController.php`, `SetConversionReportRecords.php`).
2. Rendered with no translation layer — `resources/views/recruiterTracker/nextstep.blade.php:307` does `{{ nl2br($recruiterDetails->Candidate_Current_Status) }}` directly.
3. Synced to a live external Microsoft SQL Server system ("Excelss") via `app/Helpers/Sync.php:598-620`, which calls `EXEC [Excelss].[RecruiterTrackerUpdate/Insert] ... @Candidate_Current_Status = ?` with this exact string value.

This makes the status-value rename a genuine data decision, not a pure copy edit — it is the AC16 "technical discovery" the story asks for, and this design resolves it.

The distinct values actually present in `Candidate_Current_Status` (confirmed against the database) are: `Added to Potential Nurse List`, `Bonus Paid`, `Can't Match Pay`, `Client Match`, `Clinical Competency Review`, `Current Nurse Placed`, `Deleted`, `Do Not Use`, `First Shift Confirmed`, `Hired`, `Hired - PRN`, `Inactive`, `Ineligible for Hire`, `Ineligible for Hire/Rehire`, `IPEA - Cancelled`, `IPEA - NC/NS`, `IPEA - Pending Orientation`, `IPEA - Rescheduled`, `IPEA - Scheduled`, `Need to be Scheduled by RA`, `Need to Review New Clients`, `Need to schedule IPEA`, `New Nurse Placed`, `Not a Match`, `Pending Start`, `PEPI - Cancelled`, `PEPI - Completed`, `PEPI - NC/NS`, `PEPI - Scheduled`, `Potential Candidate`, `PRN`, `Prospect`, `Ready for Active Coverage`, `Rehire`, `Standby`, `Training Shift Scheduled`, `WEPI - Cancelled`, `WEPI - Scheduled` (plus empty string). This is a large, free-text-driven status set with only one value overlapping this rename (`Clinical Competency Review`) and no `PEPR`-named value anywhere in it — confirming the PEPI/WEPI family is a distinct, unrelated set of statuses that must not be touched.

## Goals / Non-Goals

**Goals:**
- All screens/exports/notifications display "PRC"/"Patient Review Call" instead of "CCR"/"Clinical Competency Review" or "PEPR"/"Pre-Employment Patient Review".
- The Recruiter Tracker status displays "Patient Review Call" for both existing and newly created records (AC5-7), without breaking the sync to the external Excelss system.
- Preserve all existing workflow behavior, permissions, business rules, scheduling logic, and navigation (AC15).

**Non-Goals:**
- Renaming route names, permission keys, the `ccr/` view folder, `CCRController`, `mssql_recruitertracker` table/column names, or the external Excelss stored-procedure parameter names (`@Candidate_Current_Status`, `@Clinical_Competency_Review`). These are internal identifiers, not displayed terminology, and changing them would require external coordination and carries regression risk disproportionate to a terminology-only story.
- Touching "PEPI"/"WEPI" — a visually similar but unrelated abbreviation for a different recruiting step.
- Building a second "PEPR Calendar – Coverage" — only one calendar exists in code (`CCR Calendar`); it is renamed once.

## Decisions

### Decision 1: Recruiter Tracker status text — display-mapping layer, not a stored-value change

**Confirmed by requester**: reviewing the actual distinct values in the `Candidate_Current_Status` column (see list above), the requester agrees the stored value should not be touched — this decision is confirmed, not just recommended.

**Chosen approach**: Introduce a single small helper (e.g. `RecruiterTrackerStatusLabel::display(string $status): string`, or a method on an existing helper such as `ConstantHelper`) that maps the legacy stored value `'Clinical Competency Review'` to the display string `'Patient Review Call'` at render time. Update the one raw-echo site (`recruiterTracker/nextstep.blade.php:307`) and any other place the raw column is displayed to call this helper instead of echoing the column directly. Leave `Candidate_Current_Status` stored as `'Clinical Competency Review'` in the database and in the payload sent to Excelss — do not touch the ~14 write-sites' literal value, and do not touch any of the other ~35 distinct status values in this column (they are unrelated to this rename).

**Why**: We do not have confirmation of what (if anything) on the Excelss side depends on the exact string `'Clinical Competency Review'` arriving via `@Candidate_Current_Status`. Changing the stored/synced value is irreversible in practice (external system, no rollback visibility from this codebase) and the blast radius if something downstream matches on that string is unknown and hard to detect until it silently breaks. A display-mapping helper fully satisfies AC5-7 (existing and new records show "Patient Review Call") with zero risk to the external sync, at the cost of the raw DB value staying "Clinical Competency Review" internally forever — which is invisible to end users and acceptable since this story is scoped to displayed terminology only.

**Alternative considered**: Change the literal string at all ~14 write-sites and backfill existing rows. Rejected for now because it changes the external sync payload without a confirmed-safe downstream consumer; can be revisited if/when someone confirms nothing on the Excelss side depends on the literal value.

**Follow-up not in this change**: Confirming with whoever owns the Excelss system whether the string value is consumed as free text or matched/parsed. If confirmed safe, a future change could migrate the stored value directly and remove the mapping helper.

### Decision 2: CCR Calendar rename — single calendar, no split

Rename the sidebar link label (`resources/views/layouts/newSidebar.blade.php`) and calendar page title/headings from "CCR Calendar" to "PRC Calendar" — this is what end users actually see. Do not introduce a second, differently-named coverage calendar — AC8 and AC9 both map to this one existing calendar (confirmed with the requester during exploration).

**Reverted sub-decision**: `config/permissions.php:284`'s `'CCR Calendar'` group key was initially renamed to `'PRC Calendar'` (reasoning at the time: `ProcessPermissions.php` matches DB rows by `route_name`, not group name, so it looked like a safe display-only rename). The requester reverted this: `process:permissions` inserts a new DB row whenever it doesn't find an existing match, and the risk that changing the group key causes it to create a new permission group (rather than cleanly relabeling the existing one) was judged not worth it for what's fundamentally a config/DB-facing identifier, not pure UI text. `config/permissions.php` keeps `'CCR Calendar'` as-is. Net effect: the Roles/Permissions admin screen (which renders this group key as a heading) still shows "CCR Calendar", while the sidebar navigation link end users click shows "PRC Calendar" — an intentional, accepted inconsistency between an admin/config screen and the actual end-user-facing navigation.

### Decision 3: Scope boundary — displayed strings only, no identifier renames

Route names, permission config keys, class/file/table/column names keep their current "ccr"-based identifiers. Only the values shown to users (blade text, config display names/descriptions surfaced in UI, CSV headers, email/notification text) are changed. This keeps the diff mechanical and reviewable, matches the story's explicit "terminology update only" framing, and avoids collateral risk to routing/permissions/data sync.

## Risks / Trade-offs

- **[Risk]** A future maintainer might not notice the display-mapping helper and add a new raw echo of `Candidate_Current_Status` elsewhere, showing the legacy text again. → **Mitigation**: keep the helper's name and doc-comment explicit about why it exists (mirrors the "one canonical formatter" pattern); tasks.md includes a repo-wide grep for other raw echoes of this column, not just the one currently known.
- **[Risk]** Given ~68 files and mixed patterns (blade text, PHP string literals, config descriptions), a manual pass could miss occurrences or over-replace inside identifiers (e.g. `ccrListing`, `ccr_id`). → **Mitigation**: tasks.md scopes replacement per-file with explicit "do not touch" identifier lists; verify with a final `rg -i "CCR|PEPR|Clinical Competency|Pre-Employment Patient"` pass restricted to display strings.
- **[Trade-off]** The stored DB value and external sync payload keep saying "Clinical Competency Review" indefinitely under Decision 1. Anyone querying the database or the Excelss table directly (outside this app's UI) still sees the old term. Acceptable per story scope (display-layer only); flagged for future revisit once the Excelss dependency is clarified.

## Migration Plan

- No schema migration required (Decision 1 avoids changing stored data).
- Deploy as a standard code change (blade/PHP text edits + one new helper method); no data backfill, no external system coordination needed for this change.
- Rollback: revert the commit(s) — no data migration to unwind.

## Open Questions

- Whether the Excelss system depends on the literal string value of `Candidate_Current_Status` remains unconfirmed. Decision 1 sidesteps this for now; resolving it is a prerequisite for ever changing the stored/synced value directly (out of scope for this change, tracked as a follow-up).
- Discovered during implementation: `schedule_types.type = 'CCR'` (the interview-type field used on Recruiter Tracker next-step forms) has the same value-is-also-a-functional-key entanglement as `Candidate_Current_Status` — it's displayed in a dropdown and also compared directly in JS (`$(this).val() == 'CCR'`) and PHP (`whereNotIn('type', ['CCR', ...])`, bucket-outcome lookups keyed on it). This change leaves that value untouched for the same reason as Decision 1, and only updates the plain surrounding help/validation text. A follow-up change (with the same display-mapping treatment, or a confirmed-safe value migration) would be needed to fully rename this occurrence too.
- The Decision 1 treatment extends beyond `Candidate_Current_Status` itself: inside `CCRController::saveCCRScheduleNext`, the "Third and final CCR attempt was made for..." notification title and matching `notes.notes` activity-note text were initially changed to "PRC" and then reverted by the requester, because other code in that same function does exact-string matching against this stored text. As with `Candidate_Current_Status`, the stored/matched string stays "CCR"; the display-time parser (`ConstantHelper::toPrcDisplayText()`, section 10 of tasks.md) is what shows "PRC" to end users wherever this note text is later rendered (e.g. `trackerhistory.blade.php`). This is the general pattern for this change now: never assume a "CCR"-containing string is pure display text without checking for exact-match consumers first.
