<?php

namespace App\VBC\Models\PatientCustomProperty;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string|null $description
 * @property mixed $default_value
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PatientCustomPropertyDefinitionModel extends Model
{
    use HasFactory;

    public $table = 'patient_custom_property_definitions';
    protected $fillable = [
        'key',
        'name',
        'description',
        'default_value',
        'is_active',
    ];
    protected $casts = [
        'default_value' => 'array',
        'is_active' => 'boolean',
    ];
}
