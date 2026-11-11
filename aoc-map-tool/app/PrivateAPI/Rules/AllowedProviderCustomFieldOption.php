<?php

namespace App\PrivateAPI\Rules;

use App\CustomFields;
use App\Enums\EntityTypeEnum;
use Illuminate\Contracts\Validation\Rule;

class AllowedProviderCustomFieldOption implements Rule
{
    private string $message = 'The selected value is not an allowed option for this field.';

    public function __construct(
        private readonly string $field,
        private readonly array $entryTypes = [],
    ) {
    }

    public function passes($attribute, $value): bool
    {
        $query = CustomFields::query()
            ->where('is_deleted', 0)
            ->where('db_field_name', $this->field)
            ->where('entity', EntityTypeEnum::CLINICIAN->value);

        if (!empty($this->entryTypes)) {
            $query->whereIn('input_type', $this->entryTypes);
        }

        $customField = $query->first();

        if (!$customField) {
            $this->message = 'The selected field does not exist on providers.';

            return false;
        }

        $options = array_values(
            array_filter(
                preg_split('/\r\n|[\r\n]/', (string)$customField->options),
                fn($opt) => trim($opt) !== ''
            )
        );

        $values = is_array($value) ? $value : [$value];

        foreach ($values as $item) {
            if (!in_array($item, $options, true)) {
                $this->message = "The selected value '{$item}' is not an allowed option for this field.";

                return false;
            }
        }

        return true;
    }

    public function message(): string
    {
        return $this->message;
    }
}
