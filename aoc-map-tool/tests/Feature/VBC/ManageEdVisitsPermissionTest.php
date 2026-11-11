<?php

namespace Tests\Feature\VBC;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Contract tests for the "Manage ED Visits" permission entry (PD-3969) in config/permissions.php — the
 * single gate for all ED Visits functionality (both Triage buckets, the ED Visit tracker modal and its
 * tabs); it replaced "Triage ED Visits". No database.
 */
class ManageEdVisitsPermissionTest extends TestCase
{
    public function test_it_is_keyed_by_the_route_name_the_dashboard_checks(): void
    {
        $this->assertSame('ed-visits.manage', $this->permission()['route']);
    }

    public function test_every_related_route_is_an_existing_named_route(): void
    {
        foreach ($this->permission()['related'] as $routeName) {
            $this->assertTrue(Route::has($routeName), "Related route '{$routeName}' does not exist");
        }
    }

    public function test_it_grants_every_care_coordination_endpoint_the_ed_flow_calls(): void
    {
        $related = $this->permission()['related'];

        foreach ([
            'care-coordination.index',
            'care-coordination.clients.index',
            'care-coordination.types.index',
            'care-coordination.store',
            'care-coordination.show',
            'care-coordination.update',
            'hospitalization.assignees.index',
        ] as $required) {
            $this->assertContains($required, $related);
        }
    }

    public function test_it_grants_what_the_ed_visits_bucket_and_tracker_need(): void
    {
        $related = $this->permission()['related'];

        foreach ([
            'hospitalization.ed-visits.data',
            'hospitalization.ed-visits.trackers.store',
            'hospitalization.clients.index',
            'hospitalization.clients.show',
            'hospitalization.trackers.show',
            'hospitalization.trackers.update',
            'hospitalization.trackers.outcome.update',
            'hospitalization.trackers.notes.index',
            'hospitalization.trackers.notes.store',
            'hospitalization.trackers.contacts.store',
            'hospitalization.trackers.educations.store',
            'hospitalization.diagnosis-codes.search',
            'hospitalization.trackers.diagnoses.store',
            'hospitalization.trackers.diagnoses.update',
            'hospitalization.trackers.diagnosis-toggle.update',
            'follow-up.store',
        ] as $required) {
            $this->assertContains($required, $related);
        }
    }

    public function test_it_does_not_grant_the_icm_only_endpoints(): void
    {
        $related = $this->permission()['related'];

        foreach ([
            'hospitalization.index',
            'hospitalization.data',
            'hospitalization.all-data',
            'hospitalization.all-data.export',
            'follow-up.index',
        ] as $route) {
            $this->assertNotContains($route, $related);
        }
    }

    public function test_the_triage_ed_visits_permission_is_gone(): void
    {
        $this->assertArrayNotHasKey('Triage ED Visits', config('permissions.Dashboard'));
        $this->assertArrayNotHasKey('Triage ED Visits', config('permissions.Triage'));
    }

    public function test_the_triage_and_icm_permissions_are_left_as_they_were(): void
    {
        $this->assertSame('triage.getTriageData', config('permissions.Dashboard.Triage.route'));
        $this->assertSame('hospitalization.index', config('permissions.Dashboard.Intensive Case Management.route'));
        $this->assertNotContains(
            'hospitalization.ed-visits.data',
            config('permissions.Dashboard.Intensive Case Management.related')
        );
    }

    private function permission(): array
    {
        $permission = config('permissions.Triage')['Manage ED Visits'] ?? null;
        $this->assertIsArray($permission, "'Manage ED Visits' permission is missing from config/permissions.php");

        return $permission;
    }
}
