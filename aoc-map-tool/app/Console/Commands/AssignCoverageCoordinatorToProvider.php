<?php

namespace App\Console\Commands;

use App\Enums\RolesEnum;
use App\Helpers\H;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use \Throwable;

class AssignCoverageCoordinatorToProvider extends Command
{
    //@todo replace with UserStatus enum
    private const DISABLED_USER_TYPE = 2;

    protected $signature = 'provider:assign-coverage-coordinator';

    protected $description = 'Assign coverage coordinator to providers';

    public function handle()
    {
        $polygons = DB::table('polygons')
            ->orderBy('id')
            ->where(function (Builder $query) {
                $query->where('user_type', 'client_care_coordinator')
                    ->orWhereNull('user_type');
            })
            ->lazy();

        $usersWithoutRole = [];
        $disabledUsers = [];
        $usersNotExist = [];
        foreach ($polygons as $polygon) {
            $coordinatorName = H::getHelper('ConstantHelper')->extractCoordinatorName($polygon->name);
            $user = User::with('roles')->where('name', $coordinatorName)->first();
            if ($user) {
                if (!$user->roles->contains('id', RolesEnum::COVERAGE_COORDINATOR_ID->value)) {
                    $usersWithoutRole[] = $user->id;
                }
                if ($user->status === self::DISABLED_USER_TYPE) {
                    $disabledUsers[] = $user->id;
                }

                try {
                    list($clinicianData, $ccId) = H::getHelper('ConstantHelper')->autoAssignCoverageCoordinatorMap($polygon->id);
                    if (!empty($clinicianData)) {
                        DB::table('mssql_providers')->whereIn('id', $clinicianData)->update(['cc_id' => $ccId]);
                        Log::channel('assignCoverageCoordinatorToProvider')->info("Assigned user {$user->name} [{$user->id}] to polygon {$polygon->name} [{$polygon->id}] and updated providers with cc_id {$ccId}");
                    }
                } catch (Throwable $e) {
                    Log::channel('assignCoverageCoordinatorToProvider')->error("Error assigning user to polygon: " . $e);
                }
            } else {
                $usersNotExist[] = $coordinatorName;
                Log::channel('assignCoverageCoordinatorToProvider')->error("Error assigning user {$coordinatorName} to polygon: {$polygon->name} [{$polygon->id}].");
            }
        }
        if (!empty($usersWithoutRole)) {
            Log::channel('assignCoverageCoordinatorToProvider')->warning("Users without 'client_care_coordinator' role: " . implode(', ', array_unique($usersWithoutRole)));
        }
        if (!empty($disabledUsers)) {
            Log::channel('assignCoverageCoordinatorToProvider')->warning("Disabled users: " . implode(', ', array_unique($disabledUsers)));
        }
        if (!empty($usersNotExist)) {
            Log::channel('assignCoverageCoordinatorToProvider')->warning("Users not exist: " . implode(', ', array_unique($usersNotExist)));
        }
        return Command::SUCCESS;
    }
}
