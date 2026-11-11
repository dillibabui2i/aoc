<?php

namespace Tests\Support\Fixtures;

use App\VBC\Enum\CdcVaccine\CdcVaccineStatusEnum;

class CdcVaccinesFixture extends Fixture
{
    protected function table(): string
    {
        return 'cdc_vaccines';
    }

    /**
     * An Active vaccine that belongs to a (freshly created) category, i.e. one the schedule modal can offer.
     * Pass `'cvx_group_code' => null` for a vaccine without a category, `'cdc_status'` for a non-Active one.
     */
    protected function defaults(): array
    {
        return [
            'cdc_full_name' => 'Test Vaccine',
            'cdc_status' => CdcVaccineStatusEnum::ACTIVE->value,
            // unsigned SMALLINT, unique — kept away from the codes the CDC sync fills in
            'cvx_code' => random_int(50000, 64999),
            'cvx_group_code' => fn () => (new CdcVaccineGroupsFixture())->create()->cvx_group_code,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
