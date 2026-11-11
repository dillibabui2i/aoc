<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\MssqlPatients;
use App\MssqlFacilities;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MssqlRecruitertracker extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'mssql_recruitertracker';
    protected $guarded = [];

	public static function booted() {

        parent::booted();

        //@todo Remove this temporary fix after checking PD-5231
        static::addGlobalScope('exclude_deleted', function (Builder $builder) {
			$builder->where('mssql_recruitertracker.is_deleted', '0')
                ->orWhere(function (Builder $query) {
                    $query->where('mssql_recruitertracker.is_deleted', '1')
                        ->whereNotNull('mssql_recruitertracker.deleted_at');
                });
		});
    }

    public static function calculateBonusAmount($data)
    {
        if(array_key_exists('pk', $data)){
            $data1 = MssqlRecruitertracker::where('id',$data['pk'])->first();
            if($data['name'] == 'GP'){
                $data['gp'] = $data['value'] * 100;
                $data['title'] = $data1['Skillset'];
            }elseif($data['name'] == 'Skillset'){
                $data['title'] = $data['value'];
                $data['gp'] = $data1['GP'] * 100;
            }
            $data['branch_location'] = $data1['Branch_Location'];
            $data['bilingual'] = $data1['Bilingual'];
            $data['patient_id'] = $data1['aoc_patient_id'];
        }
        $bonusAmount = 0;
        $facilities = MssqlFacilities::where('FacilityName', $data['branch_location'])->first();
        if (!empty($facilities)) {
            $array1 = [
                'TX',
                'CO',
                'AZ'
            ];
            $array2 = [
                'NC',
                'SC',
                'PA',
                'FL'
            ];
            $state = $facilities->State;
            if (in_array($state, $array1)) {
                $bonusAmount = 25;
                if ($data['title'] == 'RN') {
                    $bonusAmount = 50;
                }
            } elseif (in_array($state, $array2)) {
                $bonusAmount = 40;
                if ($data['title'] == 'RN') {
                    $bonusAmount = 40;
                }
            }
            if ($data['gp'] >= 30) {
                $bonusAmount = $bonusAmount + 5;
            }
            if ($data['bilingual'] == 1) {
                if(!empty($data['patient_id'])){
                    $patients = MssqlPatients::where('id', $data['patient_id'])->where('Languages', 'not like', "%English%")->first();
                    if (!empty($patients)) {
                        $bonusAmount = $bonusAmount + 25;
                    }
                }
            }
        }
        return $bonusAmount;
    }

    public function isInactive(): bool
    {
        return $this->Candidate_Current_Status === 'Inactive';
    }

    public function isAutoInactive(): bool
    {
        if (!empty($this->trackerActivityLog)) {
            foreach ($this->trackerActivityLog->pluck('notes') as $note) {
                if (Str::contains($note, 'Auto-closed on tracker creation of')) {
                    return true;
                }
            }
        }

        return false;
    }

    public function trackerActivityLog(): HasMany
    {
        return $this->hasMany(Notes::class, 'entity_id', 'id')->where('entity', 'tracker-activity');
    }
}
