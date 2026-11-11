<?php

namespace App\Console\Commands;

use App\SyncProcesses\BackfillPatientIntakeSequence;
use App\SyncProcesses\DaasScheduleMaster;
use App\SyncProcesses\DaasAuthorizationsMaster;
use App\SyncProcesses\DaasPaymentSource;
use App\SyncProcesses\DaasRenewables;
use App\SyncProcesses\DaasCommanSync;
use App\SyncProcesses\DaasEmergencyContact;
use App\SyncProcesses\DaasHospitalization;
use Illuminate\Console\Command;
use DB;

class SyncDaas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:daas {type} {patient_account?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Sync daas Data
    Available Types:
    schedules - process schedule master table data";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $type = $this->argument('type');

        switch ($type) {
            case 'backfill_patient_intake_sequence':
                echo 'backfilling CLIENT_INTAKE_SEQUENCE_NO for mssql_patients';
                $patient_account = $this->argument('patient_account') ?? 0;
                $process = new BackfillPatientIntakeSequence();
                $process->handle((int)$patient_account);
                break;
            case 'schedules':
                echo 'processing schedule master data (schedules)';
                $process = new DaasScheduleMaster();
                $process->syncScheduleMaster('schedules');
                break;
            case 'schedulescu':
                echo 'processing schedule master data (schedulescu)';
                $process = new DaasScheduleMaster();
                $process->syncScheduleMaster('schedulescu');
                break;
			case 'schedulemidnightcu':
                echo 'processing schedule master data (schedulemidnightcu)';
                $process = new DaasScheduleMaster();
                $process->syncScheduleMaster('schedulemidnightcu');
                break;
			case 'authorizations':
                echo 'processing schedule master data (authorizations)';
                $process = new DaasAuthorizationsMaster();
                $process->syncAuthorizationsMaster();
                break;
			case 'service_code':
                echo 'processing service code data (service_code)';
                $process = new DaasScheduleMaster();
                $process->syncServiceCodes();
                break;
            case 'service_code_rates':
                echo 'processing service code data (service_code_rates)';
                $process = new DaasScheduleMaster();
                $process->syncServiceCodeRateHistory();
                break;
                case 'renewables':
                echo 'processing renewables data';
                $process = new DaasRenewables();
                $process->syncRenewables();
                break;
            case 'renewables_types':
                echo 'processing renewable types data';
                $process = new DaasRenewables();
                $process->syncRenewableTypes();
                break;
            case 'payment_source':
                echo 'processing payment source data';
                $process = new DaasPaymentSource();
                $process->syncPaymentSource();
                break;
			case 'client_payment_source':
                echo 'processing client payment source data';
				$options = [
					'syncType' => 'daas_client_payment_source',
					'tableName' => 'client_payment_source',
					'sfTableName' => 'CLIENTPAYMENTSOURCES_SVW',
					'fieldName' => 'CS_UNIQUE_ID',
					'suffix' => 'CLIENT_PS_',
					'customFields' => [
							'CS_UNIQUE_ID' => 'concat(
								CLIENT_PS_EFFPERIOD_INTERNAL_ID,
								CLIENT_PS_PRIMARY_INTERNAL_ID,
								CLIENT_PS_ADDPAYER_INTERNAL_ID
							)'
					],
					'suffix_ignore' => [
						'CS_UNIQUE_ID'
					],
				];
                $process = new DaasCommanSync();
                $process->syncComman($options);
                break;
			case 'hospitalization_report_master':
                echo 'process hospitalization_report_master data';
                $process = new DaasHospitalization();
                $process->syncHospitalizationMaster();
                break;
			case 'supervisory_form_master':
                echo 'processing supervisory_form_master data';
				$options = [
					'syncType' => 'daas_supervisory_form_master',
					'tableName' => 'supervisory_form_master',
					'sfTableName' => 'SUPERVISORYFORMMASTER_SVW',
					'fieldName' => 'ECHART_SUBFORMID',
					'suffix' => 'SVF_',
					'suffix_ignore' => [
						'CLIENT_ID'
					],
					'callback' => 'App\Helpers\SupervisoryVisits::updateSupVisitNextDateFromDaas'
				];
                $process = new DaasCommanSync();
                $process->syncComman($options);
                break;
			case 'referralsource_master':
                echo 'processing referralsource_master data';
				$options = [
					'syncType' => 'daas_referralsource_master',
					'tableName' => 'referralsource_master',
					'sfTableName' => 'REFERRALSOURCE_SVW',
					'fieldName' => 'SOURCE_ID',
					'suffix' => 'RS_'
				];
                $process = new DaasCommanSync();
                $process->syncComman($options);
                break;
            case 'client_emergency_contacts_init':
                echo 'initial sync for client_emergency_contacts data';
                $options = [
                    'syncType' => 'client_emergency_contacts_init',
                    'tableName' => 'client_emergency_contacts',
                    'sfTableName' => 'CLIENTEMERGENCYCONTACTS_SVW',
                    'fieldName' => 'CLIENT_ID',
                    'suffix' => 'EC_'
                ];
                $process = new DaasCommanSync();
                $process->syncCommonInit($options);
                break;
            case 'client_emergency_contacts':
                echo 'sync client_emergency_contacts data';
                $process = new DaasEmergencyContact();
                $process->syncEmergencyContact();
                break;
			case 'physician_master':
                echo 'processing physician_master data';
				$options = [
					'syncType' => 'daas_physician_master',
					'tableName' => 'physician_master',
					'sfTableName' => 'PHYSICIANMASTER_SVW',
					'fieldName' => 'PHYSICIAN_ID',
					'suffix' => 'PHY_',
					'suffix_ignore' => [
						'PHYSICIAN_ID'
					]
				];
                $process = new DaasCommanSync();
                $process->syncComman($options);
                break;
            case 'claims_master':
                echo 'processing claims master data';
                $options = [
                    'syncType' => 'daas_claims_master',
                    'tableName' => 'claims_master',
                    'sfTableName' => 'CLAIMSMASTER_SVW',
                    'fieldName' => 'CLAIM_ID',
                    'suffix' => 'CLAIM_',
					'suffix_ignore' => [
						'CLAIM_ID'
					]
                ];
                $process = new DaasCommanSync();
                $process->syncComman($options);
                break;
			case 'journal_notes':
                echo 'processing journal notes data';
                $options = [
                    'syncType' => 'daas_journalnotes',
                    'tableName' => 'journal_notes',
                    'sfTableName' => 'JOURNALNOTES_SVW',
                    'fieldName' => 'NOTE_ID',
                    'suffix' => 'JN_',
                    'init' => 0,
                    'replaceColumnName' => [
                        'CLIENT_ID' => 'PatientID'
                    ]
                ];
                $process = new DaasCommanSync();
                $process->syncComman($options);
                break;
			case 'billing_inquery_notes':
                echo 'processing billing inquery notes data';
                $options = [
                    'syncType' => 'daas_billinginquerynotes',
                    'tableName' => 'billing_inquery_notes',
                    'sfTableName' => 'BILLINQUIRYNOTES_SVW',
                    'fieldName' => 'NOTE_ID',
                    'suffix' => 'BN_',
                    'init' => 0,
                    'replaceColumnName' => [
                        'CLIENT_ID' => 'PatientID'
                    ]
                ];
                $process = new DaasCommanSync();
                $process->syncComman($options);
                break;
			case 'claims_detail':
                echo 'processing claims detail data';
                $options = [
                    'syncType' => 'daas_claimsdetail',
                    'tableName' => 'claims_detail',
                    'sfTableName' => 'CLAIMDETAILS_SVW',
                    'fieldName' => 'CLAIM_DETAIL_ID',
                    'suffix' => 'CD_',
                    'init' => 0
                ];
                $process = new DaasCommanSync();
                $process->syncComman($options);
                break;
			case 'claims_payment':
                echo 'processing claims payment data';
                $options = [
                    'syncType' => 'daas_claimspayment',
                    'tableName' => 'claims_payment',
                    'sfTableName' => 'CLAIMPAYMENTS_SVW',
                    'fieldName' => 'CLAIMPAYMENT_ID',
                    'suffix' => 'CP_',
                    'init' => 0
                ];
                $process = new DaasCommanSync();
                $process->syncComman($options);
                break;
			case 'claims_adjustment':
                echo 'processing claims adjustment data';
                $options = [
                    'syncType' => 'daas_claimsadjustment',
                    'tableName' => 'claims_adjustment',
                    'sfTableName' => 'CLAIMADJUSTMENTS_SVW',
                    'fieldName' => 'ADJUSTMENT_ID',
                    'suffix' => 'CA_',
                    'init' => 0
                ];
                $process = new DaasCommanSync();
                $process->syncComman($options);
                break;
			case 'claim_denials':
                echo 'processing claim denials data';
                $options = [
                    'syncType' => 'daas_claimdenials',
                    'tableName' => 'claim_denials',
                    'sfTableName' => 'CLAIMDENIALS_SVW',
                    'fieldName' => 'DENIAL_ID',
                    'suffix' => 'DENIAL_',
                    'init' => 0,
					'suffix_ignore' => [
						'DENIAL_ID',
						'AGENCY_ID',
						'CLIENT_ID'
					]
                ];
                $process = new DaasCommanSync();
                $process->syncComman($options);
                break;
			case 'claims_master_cu':
                echo 'processing claims master data';
                $options = [
                    'syncType' => 'daas_claims_master_cu',
                    'tableName' => 'claims_master',
                    'sfTableName' => 'CLAIMSMASTER_SVW',
                    'fieldName' => 'CLAIM_ID',
                    'suffix' => 'CLAIM_',
                    'suffix_ignore' => [
                        'CLAIM_ID'
                    ]
                ];
                $process = new DaasCommanSync();
                $process->syncComman($options);
                break;
			case 'authorizations_service':
                echo 'processing authorizations service data';
                $options = [
					'syncType' => 'daas_authorizations_service',
					'tableName' => 'authorizations_service',
					'sfTableName' => 'AUTHORIZATIONSERVICE_SVW',
					'fieldName' => 'AUTH_SERVICE_UNIQUE_ID',
					'suffix' => 'AUTH_',
					'customFields' => [
							'AUTH_SERVICE_UNIQUE_ID' => 'concat(
								AUTH_SERVICE_INTERNAL_ID,
								AUTH_PAYMENT_SERVICE_ID
							)'
					],
					'suffix_ignore' => [
						'AUTH_SERVICE_UNIQUE_ID'
					],
				];
                $process = new DaasCommanSync();
                $process->syncComman($options);
                break;
            case 'client_master_additional':
                echo 'processing client master additional data';
                $options = [
                    'syncType' => 'daas_client_master_additional',
                    'tableName' => 'client_master_additional_daas',
                    'sfTableName' => 'CLIENTMASTERADDITIONAL_SVW',
                    'fieldName' => 'CLIENT_ADDITIONAL_UNIQUE_ID',
                    'suffix' => 'CLIENT_',
					'customFields' => [
							'CLIENT_ADDITIONAL_UNIQUE_ID' => 'concat(
								CLIENT_INTAKE_ID,
								ifnull(CLIENT_CLIENT_ID,0)
							)'
					],
					'suffix_ignore' => [
						'CLIENT_CLIENT_ID',
						'CLIENT_ADDITIONAL_UNIQUE_ID'
					],
                ];
                $process = new DaasCommanSync();
                $process->syncComman($options);
				DB::statement("DELETE cmad FROM client_master_additional_daas AS cmad
								WHERE cmad.intake_id IN
								( SELECT intake_id FROM ( SELECT cmad1.intake_id FROM client_master_additional_daas AS cmad1 WHERE cmad1.client_additional_unique_id LIKE CONCAT(cmad1.intake_id, '%') GROUP BY cmad1.intake_id HAVING COUNT(cmad1.id) > 1 ) AS subquery )
								AND cmad.client_client_id IS NULL;
							");
                break;
			case 'client_physician_master':
                echo 'processing client physician master data';
                $options = [
                    'syncType' => 'daas_client_physician_master',
                    'tableName' => 'client_physician_master_daas',
                    'sfTableName' => 'CLIENTPHYSICIANMASTER_SVW',
                    'fieldName' => 'INTERNAL_ID',
                    'suffix' => 'CPHY_',
					'suffix_ignore' => [
						'CLIENT_ID'
					],
                ];
                $process = new DaasCommanSync();
                $process->syncComman($options);
                break;
			case 'client_medication_master':
                echo 'processing client medication master data';
                $options = [
                    'syncType' => 'daas_client_medication_master',
                    'tableName' => 'client_medication_master_daas',
                    'sfTableName' => 'CLIENTMEDICATIONMASTER_SVW',
                    'fieldName' => 'CLIENT_MEDICATION_UNIQUE_ID',
                    'suffix' => 'CM_',
					'customFields' => [
							'CLIENT_MEDICATION_UNIQUE_ID' => 'concat(
								CLIENT_ID,
								CM_MEDICATION_ID,
								CM_EPISODEID
							)'
					],
					'suffix_ignore' => [
						'CLIENT_ID',
						'CLIENT_MEDICATION_UNIQUE_ID'
					],
                ];
                $process = new DaasCommanSync();
                $process->syncComman($options);
				/*DB::statement("DELETE cmad FROM client_master_additional_daas AS cmad
								WHERE cmad.intake_id IN
								( SELECT intake_id FROM ( SELECT cmad1.intake_id FROM client_master_additional_daas AS cmad1 WHERE cmad1.client_additional_unique_id LIKE CONCAT(cmad1.intake_id, '%') GROUP BY cmad1.intake_id HAVING COUNT(cmad1.id) > 1 ) AS subquery )
								AND cmad.client_client_id IS NULL;
							");*/
                break;

			default:
                echo 'no match found!';
        }
        return 0;
    }
}
