<?php

namespace App;

use App\Enums\RolesEnum;
use App\Models\ClientMap;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Mail;

class User extends Authenticatable
{
    use Notifiable;

    public const SYSTEM_ID = 1;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'branchid',
        'territory',
        'recruiting_teamleader',
        'recruiting_manager',
        'recruiting_admin',
        'kantime_username',
        'title',
        'signature',
        'alternateRole',
        'bilingual',
        'facilityid',
        'sending_sms_number',
        'user_timezone',
        'phone_number',
        'ic_goal',
        'billing_team_lead',
        'billing_team_manager',
        'is_instructor',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getCoverageCoordinators($fields = ['*']): Collection
    {
        return $this
            ->whereHas('roles', fn(Builder $q) => $q->where('roles.id', RolesEnum::COVERAGE_COORDINATOR_ID->value))
            ->select($fields)
            ->get();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Roles::class, 'users_has_roles', 'user_id', 'role_id');
    }

    public function getAIUser(): User
    {
        return $this->where('name', 'Thoughtful AI')->where('title', 'Thoughtful AI')->first();
    }

    public static function generateRandomKey($length)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxz0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public function getAPIUserByClientID(string $clientId): ?User
    {
        return $this->whereHas('clientApiMap', function ($q) use ($clientId) {
            $q->where('clientId', $clientId);
        })->first();
    }

    public function clientApiMap(): HasOne
    {
        return $this->hasOne(ClientMap::class, 'serviceUserId', 'id');
    }

    public static function sendPasswordEmail($email, $newpassword, $action = 'edit')
    {
        $data['email'] = $email;
        $data['from_email'] = env('MAIL_FROM_ADDRESS');
        $data['newpassword'] = $newpassword;
        $data['subject'] = env('MAIL_FROM_NAME') . ' - New Login Details';
        if ($action == 'add') {
            $data['body_line1'] = 'Use following details to login on' . env('APP_NAME');
            $data['body_line2'] = 'Email: ' . $email . '<br>Password: ' . $newpassword;
        } else {
            $data['body_line1'] = 'Use following password to login on' . env('APP_NAME');
            $data['body_line2'] = 'Password: ' . $newpassword;
        }
        $data['body_line3'] = 'It is highly recommended to change given password once you login';
        Mail::send('email.newPassword', $data, function ($message) use ($data) {
            $message->from($data['from_email'], env('MAIL_FROM_NAME'));
            $message->subject($data['subject']);
            $message->to($data['email']);
        });
    }

    public static function sendPasswordLink($request, $action = 'edit')
    {
        $data['from_email'] = env('MAIL_FROM_ADDRESS');
        $token = Str::random(64);
        $data['subject'] = env('APP_NAME') . ': Password Reset Confirmation';
        $resetUrl = url('/resetpassword/' . $token);
        $data['body_line1'] = 'Hello ' . $request->name . ',';
        if ($action == 'add') {
            $data['subject'] = env('APP_NAME') . ': Welcome ' . $request->name;
            $data['body_line2'] = 'Your account has been set up. Use the following values to login';
            $data['body_line3'] = 'User: ' . $request->email . '<br>Password: <a href="' . $resetUrl . '?type=reset">Click here to set the password</a>';
        } elseif ($action == 'edit') {
            $data['body_line2'] = 'There was recently a request to change the password for your account.<br>Please click below to reset your password:';
            $data['body_line3'] = '<a href="' . $resetUrl . '?type=reset">Click here to reset the password</a>';
        }
        $data['body_line4'] = 'Thank you, ' . env('APP_NAME');

        DB::table('password_resets')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => Carbon::now(),
        ]);

        $data['token'] = $token;
        Mail::send('email.newPassword', $data, function ($message) use ($request, $data) {
            $message->from($data['from_email'], env('MAIL_FROM_NAME'));
            $message->to($request->email);
            $message->subject($data['subject']);
        });
    }

    public static function checkRoleDepartment($departmentName, $roleArray)
    {
        try {
            $roleids = Roles::where('department', $departmentName)->pluck('id')->toArray();;
            $matchArray = array_intersect($roleids, $roleArray);
            if (!empty($matchArray)) {
                return 1;
            }
            return 0;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
