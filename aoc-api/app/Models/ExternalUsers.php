<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class ExternalUsers extends Model
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'token',
        'status'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password'
    ];

    /**
     * @return HasMany
     */
    protected function externalUserPatients(): HasMany
    {
        return $this->hasMany(ExternalUserPatients::class, 'external_user_id', 'id');
    }

    /**
     * @param $id
     * @param $fields
     * @return ExternalUsers
     */
    protected static function getUserDetails($id, $fields): ExternalUsers
    {
        return self::select($fields)->where('id', $id)->where('status', 1)->first();
    }

    /**
     * @param $request
     * @return mixed
     */
    protected static function doPostLogOperation($request): ExternalUsers
    {
        $userDetails = self::find($request['id']);
        $userDetails->tokens()->delete();

        $token = $userDetails->createToken('mobile-app')->plainTextToken;
        $userDetails->token = $token;

        return $userDetails;
    }

    /**
     * @param ExternalUsers $user
     * @return void
     */
    protected static function doLogoutOperation(ExternalUsers $user): void
    {
        $user->currentAccessToken()->delete();

        Device::where([['user_id', '=', $user->id]])->update(['status' => 0, 'user_id' => 0]);
    }
}
