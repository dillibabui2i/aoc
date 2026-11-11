<?php

namespace App\VBC\Models\PatientContact;

use App\MssqlPatients;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $patient_id
 * @property int $contact_type_id
 * @property string $first_name
 * @property string|null $last_name
 * @property string|null $phone
 * @property string|null $email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read MssqlPatients|null $patient
 * @property-read ContactTypeModel|null $type
 */
class PatientContactModel extends Model
{
    use HasFactory;

    public $table = 'patient_contacts';
    protected $fillable = [
        'patient_id',
        'contact_type_id',
        'first_name',
        'last_name',
        'phone',
        'email',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(MssqlPatients::class, 'patient_id', 'id')
            ->where('IsLatestAdmit', true);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ContactTypeModel::class, 'contact_type_id', 'id');
    }
}
