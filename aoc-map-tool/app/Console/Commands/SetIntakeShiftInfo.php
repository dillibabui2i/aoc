<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Helpers\H;
use App\Helpers\LD;
use App\Models\ServiceCodeDaas;
use App\Services\Intake\IntakeShiftInfoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class SetIntakeShiftInfo extends Command
{
    private const LOG_FILE = 'SetIntakeShiftInfo2.log';

    protected $signature = 'intake:setshifts2';

    protected $description = 'Set admission and first billable shift data for intake clients';

    public function handle(IntakeShiftInfoService $intakeShiftInfo, ServiceCodeDaas $serviceCodeDaas): int
    {
        $this->log('Start SetIntakeShiftInfo2');

        $serviceCodesUpdated = $this->runInTransaction('service codes flags', fn () => [
            'is_admissions' => $serviceCodeDaas->refreshAdmissionsFlag(),
            'is_respite' => $serviceCodeDaas->refreshRespiteFlag(),
        ]);

        if (LD::checkFeatureFlagLoggedOut('intake-buckets-set-shifts-info-updated')) {
            $intakeUpdated = $this->runInTransaction('intake shifts', fn() => $intakeShiftInfo->recalculate());
            return $serviceCodesUpdated && $intakeUpdated ? self::SUCCESS : self::FAILURE;
        }

        return $serviceCodesUpdated ? self::SUCCESS : self::FAILURE;
    }

    private function runInTransaction(string $label, callable $callback): bool
    {
        $this->log("Update {$label}");

        try {
            $affected = DB::transaction($callback);
        } catch (Throwable $e) {
            $this->log("Error ({$label}, rolled back): " . $e->getMessage());

            return false;
        }

        foreach ($affected as $step => $rows) {
            $this->log("{$step}: {$rows} rows updated");
        }

        return true;
    }

    private function log(string $message): void
    {
        H::ExcelLog($message, self::LOG_FILE);
        $this->line($message);
    }
}
