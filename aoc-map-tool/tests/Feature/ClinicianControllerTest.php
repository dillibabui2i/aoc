<?php

namespace Tests\Feature;

use App\MssqlProviders;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Support\Fixtures\AocDocumentsFixture;
use Tests\Support\Fixtures\MapsNthAlertsFixture;
use Tests\Support\Fixtures\MssqlEmploymentStatusFixture;
use Tests\Support\Fixtures\MssqlFacilitiesFixture;
use Tests\Support\Fixtures\MssqlProvidersFixture;
use Tests\Support\Fixtures\NotificationsFixture;
use Tests\Support\Fixtures\UserFixture;
use Tests\TestCase;

/**
 * @coversDefaultClass \App\Http\Controllers\ClinicianController
 */
class ClinicianControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->findOrFail((new UserFixture())->create(['name' => 'Test Super Admin'])->id);
        $this->actingAs($this->admin);

        // The views call mix() and the legacy layout needs compiled assets (public/css, mix-manifest.json),
        // none of which exist in CI. These tests cover the controller, not the layout, so a stub replaces it.
        $this->withoutMix();
        view()->getFinder()->prependLocation(base_path('tests/Support/Views'));
    }

    // -----------------------------------------------------------------
    // Authorization
    // -----------------------------------------------------------------

    /**
     * @test
     * @covers ::listing
     * @covers ::getDetails
     * @covers ::statusChangeRequest
     * @covers ::getCandidateName
     * @covers ::statusChangeRequestSave
     * @covers ::saveClinicianCustomCity
     */
    public function it_should_redirect_guests_to_login_for_every_clinician_route(): void
    {
        auth()->logout();

        $this->get(route('clinicianListing'))->assertRedirect(route('login'));
        $this->get(route('clinician.getDetails'))->assertRedirect(route('login'));
        $this->get(route('clinician.statusChangeRequest'))->assertRedirect(route('login'));
        $this->get(route('clinician.getCandidateName'))->assertRedirect(route('login'));
        $this->post(route('clinician.statusChangeRequestSave'))->assertRedirect(route('login'));
        $this->post(route('clinician.saveClinicianCustomCity'))->assertRedirect(route('login'));
    }

    /**
     * @test
     * @covers ::listing
     */
    public function it_should_let_an_authenticated_super_admin_pass_the_admin_role_middleware(): void
    {
        $this->get(route('clinicianListing'))->assertOk();
    }

    // -----------------------------------------------------------------
    // listing()
    // -----------------------------------------------------------------

    /**
     * @test
     * @covers ::listing
     */
    public function it_should_render_the_clinician_grid_view_for_a_non_ajax_request(): void
    {
        $this->get(route('clinicianListing'))
            ->assertOk()
            ->assertViewIs('clinician.grid')
            ->assertViewHas('columns');
    }

    /**
     * @test
     * @covers ::listing
     */
    public function it_should_return_an_empty_datatable_payload_when_there_are_no_providers(): void
    {
        $response = $this->getJson(route('clinicianListing', $this->datatableParams()), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk()->assertJson([
            'draw' => 1,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
        ]);
    }

    /**
     * @test
     * @covers ::listing
     */
    public function it_should_list_non_deleted_providers_and_filter_them_by_search_term(): void
    {
        $alpha = (new MssqlProvidersFixture())->create(['ProviderName' => 'Alpha One', 'ClinicianID' => 'ALPHA-1']);
        (new MssqlProvidersFixture())->create(['ProviderName' => 'Beta Two', 'ClinicianID' => 'BETA-2']);
        (new MssqlProvidersFixture())->create(['ProviderName' => 'Deleted Provider', 'ClinicianID' => 'DEL-3', 'is_deleted' => 1]);

        $all = $this->getJson(route('clinicianListing', $this->datatableParams()), ['X-Requested-With' => 'XMLHttpRequest']);
        $all->assertOk();
        $this->assertSame(2, $all->json('recordsTotal'));

        $filtered = $this->getJson(
            route('clinicianListing', $this->datatableParams() + ['search' => ['value' => 'Alpha']]),
            ['X-Requested-With' => 'XMLHttpRequest']
        );
        $filtered->assertOk();
        $this->assertSame(1, $filtered->json('recordsFiltered'));
        $this->assertSame($alpha->ProviderName, $filtered->json('data.0.ProviderName'));
    }

    // -----------------------------------------------------------------
    // getDetails()
    // -----------------------------------------------------------------

    /**
     * @test
     * @covers ::getDetails
     */
    public function it_should_return_a_bad_request_when_no_id_is_given(): void
    {
        $this->get(route('clinician.getDetails'))->assertBadRequest();
    }

    /**
     * @test
     * @covers ::getDetails
     */
    public function it_should_show_a_clinician_profile_with_care_team_and_notes(): void
    {
        $coordinator = (new UserFixture())->create(['name' => 'Ivy Coordinator']);
        $provider = (new MssqlProvidersFixture())->create(['ic_id' => $coordinator->id]);

        (new NotificationsFixture())->create([
            'related_item' => $provider->id,
            'title' => 'A note',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->get(route('clinician.getDetails', ['id' => $provider->id]));

        $response->assertOk()
            ->assertViewIs('clinician.details')
            ->assertViewHas('clinicianDetails', fn ($details) => $details->id === $provider->id)
            ->assertViewHas('careTeam', function ($careTeam) use ($coordinator) {
                return collect($careTeam)->contains(
                    fn ($member) => $member['name'] === $coordinator->name && $member['designation'] === 'Intake Coordinator'
                );
            })
            ->assertViewHas('notes', fn ($notes) => $notes->count() === 1);
    }

    // -----------------------------------------------------------------
    // statusChangeRequest()
    // -----------------------------------------------------------------

    /**
     * @test
     * @covers ::statusChangeRequest
     */
    public function it_should_render_the_status_change_request_modal_view(): void
    {
        $this->get(route('clinician.statusChangeRequest'))
            ->assertOk()
            ->assertViewIs('clinician.statusChangeRequest');
    }

    // -----------------------------------------------------------------
    // getCandidateName()
    // -----------------------------------------------------------------

    /**
     * @test
     * @covers ::getCandidateName
     */
    public function it_should_find_active_eligible_providers_by_name_or_clinician_id(): void
    {
        $facility = (new MssqlFacilitiesFixture())->create(['FacilityID' => 5001, 'FacilityName' => 'Downtown Branch']);
        $status = (new MssqlEmploymentStatusFixture())->create(['EmploymentStatusID' => 200, 'EmploymentStatus' => 'Active']);

        $provider = (new MssqlProvidersFixture())->create([
            'FirstName' => 'Ada',
            'LastName' => 'Lovelace',
            'ClinicianID' => 'ADA-1',
            'Search_Name' => 'Ada Lovelace',
            'FacilityID' => $facility->FacilityID,
            'EmploymentStatusID' => $status->EmploymentStatusID,
        ]);

        $response = $this->getJson(route('clinician.getCandidateName', ['term' => $provider->FirstName]));

        $response->assertOk();
        $this->assertCount(1, $response->json());
        $this->assertSame($provider->Search_Name, $response->json('0.value'));
        $this->assertSame($facility->FacilityName, $response->json('0.FacilityName'));
    }

    /**
     * @test
     * @covers ::getCandidateName
     */
    public function it_should_return_an_empty_array_when_nothing_matches(): void
    {
        (new MssqlEmploymentStatusFixture())->create(['EmploymentStatusID' => 201, 'EmploymentStatus' => 'Active']);

        $response = $this->getJson(route('clinician.getCandidateName', ['term' => 'Nobody Here']));

        $response->assertOk()->assertExactJson([]);
    }

    // -----------------------------------------------------------------
    // statusChangeRequestSave()
    // -----------------------------------------------------------------

    /**
     * @test
     * @covers ::statusChangeRequestSave
     */
    public function it_should_fail_status_change_request_validation_when_required_fields_are_missing(): void
    {
        $this->post(route('clinician.statusChangeRequestSave'), [])
            ->assertRedirect()
            ->assertSessionHasErrors(['src_candidate_name', 'src_requested_status', 'src_reason', 'src_date_requested']);
    }

    /**
     * @test
     * @covers ::statusChangeRequestSave
     */
    public function it_should_reject_a_status_change_request_when_an_open_one_already_exists(): void
    {
        $provider = (new MssqlProvidersFixture())->create(['request_outcome' => 'Requested']);

        $response = $this->postJson(route('clinician.statusChangeRequestSave'), [
            'src_candidate_name' => $provider->fullName,
            'src_provider_id' => $provider->id,
            'src_requested_status' => 999,
            'src_reason' => 'Relocation',
            'src_date_requested' => now()->format('m/d/Y'),
        ]);

        $response->assertOk()->assertJson([
            'status' => 0,
            'msg' => 'An open status change request already exists',
        ]);
    }

    /**
     * @test
     * @covers ::statusChangeRequestSave
     */
    public function it_should_save_a_new_status_change_request_end_to_end(): void
    {
        $current = (new MssqlEmploymentStatusFixture())->create(['EmploymentStatusID' => 100, 'EmploymentStatus' => 'Active']);
        $requested = (new MssqlEmploymentStatusFixture())->create(['EmploymentStatusID' => 200, 'EmploymentStatus' => 'On Leave']);

        $provider = (new MssqlProvidersFixture())->create([
            'EmploymentStatusID' => $current->EmploymentStatusID,
            'request_outcome' => null,
        ]);

        $response = $this->postJson(route('clinician.statusChangeRequestSave'), [
            'src_candidate_name' => $provider->fullName,
            'src_provider_id' => $provider->id,
            'src_branch_id' => 1,
            'src_requested_status' => $requested->EmploymentStatusID,
            'src_reason' => 'Relocation',
            'src_date_requested' => now()->format('m/d/Y'),
        ]);

        $response->assertOk()->assertJson([
            'status' => 1,
            'msg' => 'Status change request saved successfully.',
        ]);

        $saved = MssqlProviders::query()->findOrFail($provider->id);
        $this->assertSame($requested->EmploymentStatusID, (int) $saved->requested_status);
        $this->assertSame('Requested', $saved->request_outcome);
        $this->assertSame('Relocation', $saved->request_reason);
        $this->assertSame($this->admin->id, (int) $saved->request_by);
        $this->assertSame($current->EmploymentStatusID, (int) $saved->scr_original_status_id);
    }

    // -----------------------------------------------------------------
    // saveClinicianCustomCity()
    // -----------------------------------------------------------------

    /**
     * @test
     * @covers ::saveClinicianCustomCity
     */
    public function it_should_save_city_and_state_and_mirror_the_preference_flag(): void
    {
        $provider = (new MssqlProvidersFixture())->create(['ClinicianID' => 'CITY-1']);

        (new MapsNthAlertsFixture())->create([
            'ClinicianID' => $provider->ClinicianID,
            'ProviderName' => $provider->fullName,
        ]);

        $response = $this->postJson(route('clinician.saveClinicianCustomCity'), [
            'ClinicianID' => $provider->ClinicianID,
            'city' => 'Austin, TX',
        ]);

        $response->assertOk()->assertJson([
            'status' => 1,
            'msg' => 'Details saved successfully.',
            'city' => 'Austin, TX',
        ]);

        $saved = MssqlProviders::query()->findOrFail($provider->id);
        $this->assertSame('Austin', $saved->aoc_cf_city);
        $this->assertSame('TX', $saved->aoc_cf_state);
        $this->assertSame(1, (int) $saved->Preference_Status);

        $mapRow = DB::table('maps_nthalerts')->where('ClinicianID', $provider->ClinicianID)->first();
        $this->assertSame(1, (int) $mapRow->Preference_Status);
    }

    // -----------------------------------------------------------------
    // documentDownload()
    // -----------------------------------------------------------------

    /**
     * @test
     * @covers ::documentDownload
     */
    public function it_should_return_an_empty_response_when_no_filename_is_given(): void
    {
        $response = $this->get(route('rightSignature.documentDownload'));

        $response->assertOk();
        $this->assertSame('', $response->getContent());
    }

    /**
     * @test
     * @covers ::documentDownload
     */
    public function it_should_redirect_when_the_file_does_not_exist_on_disk(): void
    {
        $this->get(route('rightSignature.documentDownload', [
            'documentFileName' => 'missing-file-' . Str::random(8) . '.pdf',
        ]))->assertRedirect(route('clinicianListing'));
    }

    /**
     * @test
     * @covers ::documentDownload
     */
    public function it_should_download_the_file_when_it_exists_on_disk(): void
    {
        $filename = 'test-doc-' . Str::random(8) . '.txt';
        $path = storage_path() . '/documents/';
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        file_put_contents($path . $filename, 'hello world');

        $response = $this->get(route('rightSignature.documentDownload', ['documentFileName' => $filename]));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString($filename, $response->headers->get('content-disposition'));

        unlink($path . $filename);
    }

    // -----------------------------------------------------------------
    // payRateAgreementsList()
    // -----------------------------------------------------------------

    /**
     * @test
     * @covers ::payRateAgreementsList
     */
    public function it_should_return_documents_allowed_for_a_given_clinician(): void
    {
        $provider = (new MssqlProvidersFixture())->create();

        (new AocDocumentsFixture())->create([
            'entity_id' => (string) $provider->id,
            'document_type' => 'offer_accepted',
            'description' => 'Offer Accepted Doc',
            'document_filename' => 'offer.pdf',
        ]);

        (new AocDocumentsFixture())->create([
            'entity_id' => (string) $provider->id,
            'document_type' => 'pay_rate',
            'description' => 'Pay Rate Doc',
            'document_filename' => 'payrate.pdf',
            'extra_data' => json_encode([]),
        ]);

        // belongs to a different clinician - must not show up
        (new AocDocumentsFixture())->create([
            'entity_id' => (string) ($provider->id + 999),
            'document_type' => 'offer_accepted',
            'description' => 'Someone else',
        ]);

        $response = $this->getJson(route('rightSignature.payRateAgreementsList', [
            'clinicianId' => $provider->id,
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));

        $response->assertOk();
        $this->assertSame(2, $response->json('recordsTotal'));

        $documentTypes = collect($response->json('data'))->pluck('document_type');
        $this->assertTrue($documentTypes->contains('Offer Accepted'));
        $this->assertTrue($documentTypes->contains('Pay Rate Agreement'));
    }

    // -----------------------------------------------------------------
    // viewDocument()
    // -----------------------------------------------------------------

    /**
     * Non-pdf extension: the controller only takes the JSON/"filename" branch for '.pdf'
     * documents, everything else falls through to the popup view.
     *
     * @test
     * @covers ::viewDocument
     */
    public function it_should_return_the_popup_view_when_the_file_is_missing_from_disk(): void
    {
        $document = (new AocDocumentsFixture())->create([
            'document_filename' => 'does-not-exist-' . Str::random(8) . '.docx',
        ]);

        $this->get(route('document.view', ['id' => $document->id]))
            ->assertOk()
            ->assertViewIs('clinician.viewDocumentPopup');
    }

    /**
     * @test
     * @covers ::viewDocument
     */
    public function it_should_return_a_filename_payload_for_an_existing_pdf_document(): void
    {
        $filename = 'view-doc-' . Str::random(8) . '.pdf';
        $path = storage_path() . '/documents/';
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        file_put_contents($path . $filename, '%PDF-1.4 fake pdf content');

        $document = (new AocDocumentsFixture())->create(['document_filename' => $filename]);

        /** @var TestResponse $response */
        $response = $this->getJson(route('document.view', ['id' => $document->id]));

        $response->assertOk()->assertJsonStructure(['filename']);
        $this->assertStringContainsString('.pdf', $response->json('filename'));

        unlink($path . $filename);
        $tempDir = base_path() . '/viewdocuments';
        array_map('unlink', glob($tempDir . '/*_' . $this->admin->id . '.pdf') ?: []);
    }

    private function datatableParams(): array
    {
        return [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'order' => [['column' => 0, 'dir' => 'asc']],
        ];
    }
}
