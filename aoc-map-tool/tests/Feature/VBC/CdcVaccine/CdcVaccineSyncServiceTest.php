<?php

namespace Tests\Feature\VBC\CdcVaccine;

use App\Mail\VBC\CdcVaccineGroupsNeedAocNameMail;
use App\VBC\Jobs\CdcVaccine\SyncCdcVaccineGroupsJob;
use App\VBC\Jobs\CdcVaccine\SyncCdcVaccinesJob;
use App\VBC\Models\CdcVaccine\CdcVaccineGroupModel;
use App\VBC\Models\CdcVaccine\CdcVaccineModel;
use App\VBC\Services\CdcVaccine\CdcSyncException;
use App\VBC\Services\CdcVaccine\CdcVaccineSyncService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CdcVaccineSyncServiceTest extends TestCase
{
    use DatabaseTransactions;
    use CdcXmlFixtures;

    private const G1 = 60001;
    private const G2 = 60002;
    private const V1 = 60101;
    private const V2 = 60102;
    private const V3 = 60103;

    private function fakeCdc(): void
    {
        Http::fake([
            self::GROUPS_URL => Http::response($this->vaxToGroupXml([
                [(string) self::V1, ' GROUP ONE ', (string) self::G1],
                [(string) self::V2, 'GROUP TWO', (string) self::G2],
                [(string) self::V3, 'GROUP ONE', (string) self::G1],
            ])),
            self::VACCINES_URL => Http::response($this->cvxXml([
                [(string) self::V1, 'v1', 'Vaccine 1', 'Active', '3/20/2011'],
                [(string) self::V2, 'v2', 'Vaccine 2', 'Inactive', '1/2/2020'],
                [(string) self::V3, 'v3', 'Vaccine 3', 'Active', '12/31/2019'],
            ])),
        ]);
    }

    private function service(): CdcVaccineSyncService
    {
        return app(CdcVaccineSyncService::class);
    }

    public function test_sync_is_idempotent_and_never_writes_aoc_name(): void
    {
        $this->fakeCdc();
        CdcVaccineGroupModel::create(['cvx_group_code' => self::G1, 'aoc_name' => 'Manual name', 'cdc_name' => 'old']);

        $this->service()->syncGroups();
        $this->service()->syncVaccines();
        $this->service()->syncGroups();
        $this->service()->syncVaccines();

        $this->assertSame(1, CdcVaccineGroupModel::where('cvx_group_code', self::G1)->count());
        $this->assertSame(1, CdcVaccineGroupModel::where('cvx_group_code', self::G2)->count());
        $this->assertSame(3, CdcVaccineModel::whereIn('cvx_code', [self::V1, self::V2, self::V3])->count());

        $g1 = CdcVaccineGroupModel::where('cvx_group_code', self::G1)->first();
        $this->assertSame('Manual name', $g1->aoc_name);
        $this->assertSame('GROUP ONE', $g1->cdc_name);
        $this->assertNull(CdcVaccineGroupModel::where('cvx_group_code', self::G2)->first()->aoc_name);

        $v1 = CdcVaccineModel::where('cvx_code', self::V1)->first();
        $this->assertSame(self::G1, $v1->cvx_group_code);
        $this->assertSame('Vaccine 1', $v1->cdc_full_name);
        $this->assertSame('2011-03-20', $v1->cdc_last_updated->format('Y-m-d'));
    }

    public function test_soft_deleted_rows_are_restored_not_duplicated(): void
    {
        $this->fakeCdc();
        $group = CdcVaccineGroupModel::create(['cvx_group_code' => self::G1, 'cdc_name' => 'x']);
        $group->delete();
        CdcVaccineModel::create(['cvx_code' => self::V1])->delete();

        $this->service()->syncGroups();
        $this->service()->syncVaccines();

        $this->assertSame(1, CdcVaccineGroupModel::withTrashed()->where('cvx_group_code', self::G1)->count());
        $this->assertNull(CdcVaccineGroupModel::withTrashed()->where('cvx_group_code', self::G1)->first()->deleted_at);
        $this->assertSame(1, CdcVaccineModel::withTrashed()->where('cvx_code', self::V1)->count());
        $this->assertNull(CdcVaccineModel::withTrashed()->where('cvx_code', self::V1)->first()->deleted_at);
    }

    public function test_unknown_group_code_is_stored_as_null(): void
    {
        $this->fakeCdc();

        // groups are intentionally not synced: the groups table has none of the fixture codes
        $this->service()->syncVaccines();

        $this->assertNull(CdcVaccineModel::where('cvx_code', self::V1)->first()->cvx_group_code);
    }

    public function test_http_error_or_empty_body_leaves_db_unchanged(): void
    {
        foreach ([Http::response('error', 500), Http::response('', 200)] as $response) {
            Http::fake([self::GROUPS_URL => $response]);

            try {
                $this->service()->syncGroups();
                $this->fail('Expected CdcSyncException');
            } catch (CdcSyncException $e) {
                $this->assertSame(0, CdcVaccineGroupModel::withTrashed()->where('cvx_group_code', self::G1)->count());
            }
        }
    }

    public function test_invalid_xml_leaves_db_unchanged(): void
    {
        Http::fake([self::GROUPS_URL => Http::response('<VGCodes><CVXVGInfo>')]);

        $this->expectException(CdcSyncException::class);
        try {
            $this->service()->syncGroups();
        } finally {
            $this->assertSame(0, CdcVaccineGroupModel::withTrashed()->where('cvx_group_code', self::G1)->count());
        }
    }

    public function test_mail_sent_when_group_has_blank_aoc_name(): void
    {
        Mail::fake();
        config(['cdc_vaccines.notification_recipient' => 'team@example.com']);
        CdcVaccineGroupModel::create(['cvx_group_code' => self::G1, 'cdc_name' => 'ONE', 'aoc_name' => '  ']);

        $this->assertGreaterThanOrEqual(1, $this->service()->notifyGroupsWithoutAocName());

        Mail::assertSent(CdcVaccineGroupsNeedAocNameMail::class, function ($mail) {
            return $mail->hasTo('team@example.com')
                && in_array(['cvx_group_code' => self::G1, 'cdc_name' => 'ONE'], $mail->groups, true);
        });
    }

    public function test_mail_not_sent_when_all_groups_named_or_deleted(): void
    {
        Mail::fake();
        config(['cdc_vaccines.notification_recipient' => 'team@example.com']);
        CdcVaccineGroupModel::query()->update(['aoc_name' => 'Named']);
        CdcVaccineGroupModel::create(['cvx_group_code' => self::G1, 'cdc_name' => 'ONE'])->delete();

        $this->assertSame(0, $this->service()->notifyGroupsWithoutAocName());

        Mail::assertNothingSent();
    }

    public function test_missing_recipient_sends_nothing_and_does_not_throw(): void
    {
        Mail::fake();
        config(['cdc_vaccines.notification_recipient' => null]);
        CdcVaccineGroupModel::create(['cvx_group_code' => self::G1, 'cdc_name' => 'ONE']);

        $this->assertSame(0, $this->service()->notifyGroupsWithoutAocName());

        Mail::assertNothingSent();
    }

    public function test_command_dispatches_groups_job(): void
    {
        Bus::fake();

        $this->artisan('vbc:cdc-vaccines-sync')->assertExitCode(0);

        Bus::assertDispatched(SyncCdcVaccineGroupsJob::class);
        Bus::assertNotDispatched(SyncCdcVaccinesJob::class);
    }

    public function test_groups_job_dispatches_vaccines_job_only_on_success(): void
    {
        Bus::fake([SyncCdcVaccinesJob::class]);
        Http::fake([self::GROUPS_URL => Http::response('boom', 500)]);

        try {
            (new SyncCdcVaccineGroupsJob())->handle($this->service());
            $this->fail('Expected CdcSyncException');
        } catch (CdcSyncException $e) {
            Bus::assertNotDispatched(SyncCdcVaccinesJob::class);
        }

        Http::swap(new \Illuminate\Http\Client\Factory());
        $this->fakeCdc();
        (new SyncCdcVaccineGroupsJob())->handle($this->service());

        Bus::assertDispatched(SyncCdcVaccinesJob::class);
    }
}
