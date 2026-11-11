<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $agency_id
 * @property int|null $source_id
 * @property int|null $agency_branch_id
 * @property string|null $name
 * @property int|null $admission_source
 * @property string|null $address_line1
 * @property string|null $address_line2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip
 * @property string|null $phone_no
 * @property string|null $mobile_no
 * @property string|null $contact_name
 * @property string|null $contaxt_phone
 * @property int|null $salesperson_id
 * @property string|null $category
 * @property bool|null $isvalidrecord
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ReferralSourceMasterModel extends Model
{
    public $table = 'referralsource_master';
}
