<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\MssqlPatients;
use App\Helpers\Notifications;
use App\Helpers\ConstantHelper;
use App\Helpers\H;

class sendSOCNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:socnotification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send SOC notification when Ready to start = no';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $intakePatients = MssqlPatients::where('is_intake', 1)
            ->where('IsLatestAdmit', 1)
            ->where('intake_readytostart', 0)
            ->whereRaw('CF_GoalSOCDate > now()')
            ->whereRaw('datediff(CF_GoalSOCDate, now()) = ?', [2])
            ->get();
        foreach ($intakePatients as $patient) {
            $careTeams = ConstantHelper::getCareTeam($patient->id);
            $notificationText = $patient->Patient_FirstName .
            ' ' . $patient->Patient_LastName . ' is not ready to work and SOC is scheduled for ' .
            H::getFormatedDate($patient->CF_GoalSOCDate) .
            '.  Please review to ensure SOC is still on track.';
            $this->sendIntakeNotificationToCareTeam($careTeams, $notificationText, $patient->id);
        }
        return Command::SUCCESS;
    }

    protected function sendIntakeNotificationToCareTeam($careTeams, $notificationText, $aoc_patient_id)
    {
        foreach($careTeams as $careTeam) {
            $data = [];
            $data['user_id'] = $careTeam['id'];
            $data['title'] = $notificationText;
            $data['type'] = 'intake';
            $data['related_item'] = $aoc_patient_id;
            $data['notes'] = '';
            Notifications::addNotification($data);
        }
    }
}
