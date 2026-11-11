<?php

namespace App\VBC\Models\HospitalizationTrackerContact;

use App\User;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use App\VBC\Models\PatientContact\ContactTypeModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $hospitalization_tracker_id
 * @property int $contact_type_id
 * @property string $contact_name
 * @property string|null $phone_number
 * @property string|null $email
 * @property Carbon $contact_date
 * @property int $received_by_id
 * @property string|null $contact_notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read HospitalizationTrackerModel $hospitalizationTracker
 * @property-read ContactTypeModel $type
 * @property-read User $receivedBy
 */
class HospitalizationTrackerContactModel extends Model
{
    use HasFactory;

    public $table = 'hospitalization_tracker_contacts';
    protected $fillable = [
        'hospitalization_tracker_id',
        'contact_type_id',
        'contact_name',
        'phone_number',
        'email',
        'contact_date',
        'received_by_id',
        'contact_notes',
    ];
    protected $casts = [
        'contact_date' => 'datetime',
    ];

    public function hospitalizationTracker(): BelongsTo
    {
        return $this->belongsTo(HospitalizationTrackerModel::class, 'hospitalization_tracker_id', 'id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ContactTypeModel::class, 'contact_type_id', 'id');
    }

    /** Who logged the contact — always the current user at creation time, never edited afterward. */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_id', 'id');
    }
}
