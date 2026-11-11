<?php

namespace Tests\Feature\Kantime;

use App\Enums\PayerEnum;
use App\Enums\PayerGroupEnum;
use App\Kantime\Repository\MariaDB\AocIntakeRepository;
use App\Kantime\Repository\MariaDB\AocPatientsRepository;
use App\Kantime\Services\ClientMasterSyncService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use Tests\Support\Fixtures\MssqlPatientsFixture;
use Tests\TestCase;

/**
 * @coversDefaultClass \App\Kantime\Repository\MariaDB\AocPatientsRepository
 */
class MolinaPayerGroupTest extends TestCase
{
    use DatabaseTransactions;

    private function createPatient(string $account, ?string $payer, ?string $payerGroup, int $notInKantime = 0): int
    {
        return (int) (new MssqlPatientsFixture())->create([
            'PatientAccount' => $account,
            'Payer' => $payer,
            'payer_group' => $payerGroup,
            'not_in_kantime' => $notInKantime,
        ])->id;
    }

    private function group(int $id): ?string
    {
        return DB::table('mssql_patients')->where('id', $id)->value('payer_group');
    }

    /**
     * @test
     * @covers ::updateMolinaPayerGroup
     */
    public function it_should_assign_molina_group_only_to_molina_payers(): void
    {
        $xix = $this->createPatient('T5620-1', PayerEnum::MOLINA_CMS_TITLE_XIX->value, null);
        $xxi = $this->createPatient('T5620-2', PayerEnum::MOLINA_CMS_TITLE_XXI->value, 'SCA');
        $sunshine = $this->createPatient('T5620-3', 'FL- Sunshine CMS Title XXI', 'FL - Sunshine CMS');
        $other = $this->createPatient('T5620-4', 'Other Payer', null);

        (new AocPatientsRepository())->updateMolinaPayerGroup();

        $this->assertSame(PayerGroupEnum::MOLINA_CMS->value, $this->group($xix));
        $this->assertSame(PayerGroupEnum::MOLINA_CMS->value, $this->group($xxi));
        $this->assertSame('FL - Sunshine CMS', $this->group($sunshine));
        $this->assertNull($this->group($other));
    }

    /**
     * @test
     * @covers ::updateMolinaPayerGroup
     */
    public function it_should_skip_patients_not_in_kantime(): void
    {
        $id = $this->createPatient('T5620-5', PayerEnum::MOLINA_CMS_TITLE_XIX->value, null, 1);

        (new AocPatientsRepository())->updateMolinaPayerGroup();

        $this->assertNull($this->group($id));
    }

    /**
     * @test
     * @covers ::updateMolinaPayerGroup
     */
    public function it_should_be_idempotent(): void
    {
        $molinaGroup = PayerGroupEnum::MOLINA_CMS->value;
        $id = $this->createPatient('T5620-6', PayerEnum::MOLINA_CMS_TITLE_XXI->value, $molinaGroup);

        $repository = new AocPatientsRepository();
        $repository->updateMolinaPayerGroup();
        $repository->updateMolinaPayerGroup();

        $this->assertSame($molinaGroup, $this->group($id));
    }

    /**
     * @test
     * @covers ::updateMolinaPayerGroup
     */
    public function it_should_clear_molina_group_when_client_left_molina(): void
    {
        $moved = $this->createPatient('T5620-7', 'FL- Sunshine CMS Title XXI', PayerGroupEnum::MOLINA_CMS->value);
        $noPayer = $this->createPatient('T5620-8', null, PayerGroupEnum::MOLINA_CMS->value);
        $stillMolina = $this->createPatient(
            'T5620-9',
            PayerEnum::MOLINA_CMS_TITLE_XIX->value,
            PayerGroupEnum::MOLINA_CMS->value
        );
        $unrelated = $this->createPatient('T5620-10', 'Other Payer', 'FL - Sunshine CMS');

        (new AocPatientsRepository())->updateMolinaPayerGroup();

        $this->assertNull($this->group($moved));
        $this->assertNull($this->group($noPayer));
        $this->assertSame(PayerGroupEnum::MOLINA_CMS->value, $this->group($stillMolina));
        $this->assertSame('FL - Sunshine CMS', $this->group($unrelated));
    }

    /**
     * Wiring: the post-sync step of ClientMasterSyncService must invoke the Molina derivation.
     * Repositories are final (not mockable), so the real ones run against aoc_test.
     *
     * @test
     * @covers \App\Kantime\Services\ClientMasterSyncService::runPostSyncUpdates
     */
    public function it_should_derive_molina_group_in_sync_post_updates(): void
    {
        // Payer is recomputed from client_payment_source first, so seed it; the group must follow the new payer.
        $id = $this->createPatient('T5620-11', 'Stale Payer', null);
        DB::table('mssql_patients')->where('id', $id)->update(['IntakeID' => 99562011]);
        DB::table('payment_source')->insert([
            'payersource_id' => 99562011,
            'name' => PayerEnum::MOLINA_CMS_TITLE_XIX->value,
            'timely_filing' => 0,
            'contractual_payment_expectations' => 0,
            'historical_payment_norm' => 0,
        ]);
        DB::table('client_payment_source')->insert([
            'intake_id' => 99562011,
            'primary_payer_id' => 99562011,
            'additional_payer_id' => 0,
        ]);

        $reflection = new ReflectionClass(ClientMasterSyncService::class);
        $args = [];
        foreach ($reflection->getConstructor()->getParameters() as $param) {
            /** @var \ReflectionNamedType $type */
            $type = $param->getType();
            $class = $type->getName();
            $args[] = in_array($class, [AocPatientsRepository::class, AocIntakeRepository::class], true)
                ? app($class)
                : (new ReflectionClass($class))->newInstanceWithoutConstructor();
        }
        $service = $reflection->newInstanceArgs($args);

        $method = $reflection->getMethod('runPostSyncUpdates');
        $method->setAccessible(true);
        $method->invoke($service);

        $this->assertSame(PayerGroupEnum::MOLINA_CMS->value, $this->group($id));
    }
}
