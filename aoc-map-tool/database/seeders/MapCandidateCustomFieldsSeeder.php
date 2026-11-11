<?php

namespace Database\Seeders;

use App\CustomFields;
use App\Enums\EntityTypeEnum;
use DB;
use Illuminate\Database\Seeder;

class MapCandidateCustomFieldsSeeder extends Seeder
{
    public function run(): void
    {
        $clinicianFields = CustomFields::query()
            ->where('entity', EntityTypeEnum::CLINICIAN)
            ->cursor();

        foreach ($clinicianFields as $field) {
            $data = $field->toArray();
            unset($data['id'], $data['created_at'], $data['updated_at']);

            $data['entity'] = EntityTypeEnum::CANDIDATE;

            CustomFields::query()->firstOrCreate(
                [
                    'entity' => EntityTypeEnum::CANDIDATE,
                    'db_field_name' => $data['db_field_name'],
                ],
                $data
            );
        }
    }
}
