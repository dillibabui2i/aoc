<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;

class SetConversionReportRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:conversionrecords';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set conversion report records';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
		try{

				//update candidate_applications.workFlowState = candidates.appWorkFlowState to all records where 
				//candidate_applications.application_date = candidates.application_date
				echo 'update candidate_applications.workFlowState' . "\n";
				DB::statement("update candidate_applications
								left join candidates c on candidate_applications.candidate_id = c.id
								set workflowState = c.appWorkflowState
								where candidate_applications.is_trackable = 1
								and candidate_applications.application_date = c.application_date;"
						);				

				//add the Monday of the week to each record
				echo 'update WeekStart' . "\n";
				DB::statement("update  `candidate_applications` 
								set WeekStart = date_add(application_date, interval  -WEEKDAY(application_date) day);"
						);
				//set is_trackable = 1 for the first application each week
				echo 'update is_trackable' . "\n";
				DB::statement("update candidate_applications 
								set is_trackable = 1
								where application_date >= '2023-01-01' 
								and candidate_id <> 0 
								and candidate_applications.id = 
									(select min(id) from candidate_applications ca2 
										where ca2.candidate_id = candidate_applications.candidate_id 
										and ca2.WeekStart = candidate_applications.WeekStart);");
				
				
				//set the next application for any application where the candidate has a later is_trackable application
				echo 'update next application' . "\n";
				DB::statement("update candidate_applications 
								set candidate_applications.next_app_id = 
									ifnull((select ca2.id from candidate_applications ca2 
										where ca2.candidate_id = candidate_applications.candidate_id 
											and  ca2.is_trackable = 1 
											and ca2.application_date > candidate_applications.application_date
										order by ca2.application_date asc limit 1),0),
								candidate_applications.next_app_date = 
									(select ca2.application_date from candidate_applications ca2 
										where ca2.candidate_id = candidate_applications.candidate_id 
											and ca2.is_trackable = 1 
											and ca2.application_date > candidate_applications.application_date
										order by ca2.application_date asc limit 1) 
								WHERE candidate_applications.is_trackable = 1;");
				
				//update latest_tracker_id to the candidate_applications table
				//this needs to be refined to get the right tracker (earliest or latest) that has the most successful progress???? Discuss with rachel
				echo 'update latest_tracker_id' . "\n";
				DB::statement("update candidate_applications
								set latest_tracker_id = (select rt.id from  mssql_recruitertracker rt 
											where rt.aoc_candidate_id = candidate_applications.candidate_id 
											and rt.created_at >= candidate_applications.application_date 
											and rt.created_at < ifnull(candidate_applications.next_app_date,'2030-01-01') 
											ORDER BY 
												case when Candidate_Current_Status = 'Inactive' then 1
												when Candidate_Current_Status = 'Deleted' then 2
												when Candidate_Current_Status = 'Need to Review New Clients' then 3
												when Candidate_Current_Status = 'Need to be Scheduled by RA' then 4
												when Candidate_Current_Status = 'Need to schedule IPEA' then 5
												when Candidate_Current_Status = 'IPEA - Scheduled' then 6
												when Candidate_Current_Status = 'Clinical Competency Review' then 7
												when Candidate_Current_Status = 'PEPI - Scheduled' then 8
												when Candidate_Current_Status = 'WEPI - Scheduled' then 9
												when Candidate_Current_Status = 'Training Shift Scheduled' then 10
												when Candidate_Current_Status = 'Client Match' then 11
												when Candidate_Current_Status = 'PRN' then 12
												when Candidate_Current_Status = 'First Shift Confirmed' then 13
												else 0 end desc, created_at desc  limit 1) 
								where is_trackable = 1;");
				
				DB::statement("update candidate_applications left join mssql_recruitertracker on mssql_recruitertracker.id = candidate_applications.latest_tracker_id left join mssql_patients on mssql_patients.id = mssql_recruitertracker.aoc_patient_id set candidate_applications.isPRN = ifnull(mssql_patients.isPRN, 0) where ifnull(candidate_applications.latest_tracker_id, 0) <> 0;");
				
				//update candidate_application_id to recruiting_schedule
				echo 'update candidate_application_id in recruiting_scheduel' . "\n";
				DB::statement("update recruiting_schedule rs
					set rs.candidate_application_id = ifnull(
						(
							select ca.id from candidate_applications ca 
								where ca.is_trackable = 1
								and ca.latest_tracker_id = rs.tracker_id 
								and ca.candidate_id = rs.aoc_candidate_id
								order by ca.sentDate desc limit 1
						),0
					)
					where ifnull(rs.aoc_candidate_id,0) <> 0 and rs.candidate_application_id = 0;"
				);

				//all candidate work flow states
				echo 'update converion_state for candidate work flow status' . "\n";
				DB::statement("UPDATE candidate_applications ca
						left join candidates c on ca.candidate_id = c.id 
						set ca.conversion_state = (case
							when ca.workflowState = 'New' then 1
							when ca.workflowState = 'Recruiter Phone Screen' then 2
							when ca.workflowState = 'P.E.T. #1' then 3
							when ca.workflowState = 'P.E.T. #2' then 4
							
							when ca.workflowState = 'Offer Rejected' then 6
							when ca.workflowState = 'Rejected Offer' then 6
							when ca.workflowState = 'Rejected by Agency' then 7
							when ca.workflowState = 'Do Not Contact' then 8
							when ca.workflowState = 'Nursing Student: Pending Graduation' then 9
							when ca.workflowState = 'Added to Potential Nurse List' then 10
							when ca.workflowState = 'IPEA Scheduled' then 11
							else 0 end
						)
						where ca.is_trackable = 1 and c.id is not null
						and ca.workflowState in ('New','Recruiter Phone Screen','P.E.T. #1','P.E.T. #2','Offer Rejected','Rejected Offer',
								'Rejected by Agency','IPEA Scheduled','Do Not Contact','Nursing Student: Pending Graduation',
								'Added to Potential Nurse List') 
								and ifnull(ca.latest_tracker_id,0) = 0"
				);

				//IPEA Scheduled
				echo 'IPEA Scheduled' . "\n";
				DB::statement("update candidate_applications
								left join mssql_recruitertracker on candidate_applications.latest_tracker_id = mssql_recruitertracker.id 
								set conversion_state = 11
								where ifnull(latest_tracker_id,0) <> 0
								and candidate_applications.is_trackable = 1
								and mssql_recruitertracker.Candidate_Current_Status <> 'First Shift Confirmed'
								and mssql_recruitertracker.Candidate_Current_Status <> 'PRN'
								and candidate_applications.is_complete = 0
								and candidate_applications.conversion_state < 19;"
							);
				//IPEA Cancelled
				echo 'IPEA Cancelled' . "\n";
				DB::statement("update candidate_applications
								left join mssql_recruitertracker on candidate_applications.latest_tracker_id = mssql_recruitertracker.id 
								set conversion_state = 12 
								where (
									select case when rs.IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' 
												when rs.IPEA_clinician_arrived = 'NC/NS' then 'NC/NS'
												else ifnull(rs.IPEA_onboarding_status,'Not Set') end
										from recruiting_schedule rs 
											where rs.type_id = 1 
											and rs.candidate_application_id = candidate_applications.id 
											and date(rs.appointment_date) <= date(now()) 
											order by rs.id desc limit 1) = 'Cancelled'
								and candidate_applications.is_trackable = 1
								and mssql_recruitertracker.Candidate_Current_Status <> 'First Shift Confirmed'
								and mssql_recruitertracker.Candidate_Current_Status <> 'PRN'
								and candidate_applications.is_complete = 0
								and candidate_applications.conversion_state < 19;"
							);
				
				//IPEA Inactive
				echo 'IPEA Inactive' . "\n";
				DB::statement("update candidate_applications 
								left join mssql_recruitertracker on candidate_applications.latest_tracker_id = mssql_recruitertracker.id
								set conversion_state = 13 
								where (
									select case when rs.IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' 
												when rs.IPEA_clinician_arrived = 'NC/NS' then 'NC/NS'
												else ifnull(rs.IPEA_onboarding_status,'Not Set') end
										from recruiting_schedule rs 
											where rs.type_id = 1
											and rs.candidate_application_id = candidate_applications.id 
											and date(rs.appointment_date) <= date(now()) 
											order by rs.id desc limit 1) = 'Inactive'
									and candidate_applications.is_trackable = 1
									and mssql_recruitertracker.Candidate_Current_Status <> 'First Shift Confirmed'
									and mssql_recruitertracker.Candidate_Current_Status <> 'PRN'
									and candidate_applications.is_complete = 0
									and candidate_applications.conversion_state < 19;"
							);


				//IPEA Incomplete
				echo 'IPEA Incomplete' . "\n";
				DB::statement("update candidate_applications 
								left join mssql_recruitertracker on candidate_applications.latest_tracker_id = mssql_recruitertracker.id
								set conversion_state = 14 
								where (
									select case when rs.IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' 
												when rs.IPEA_clinician_arrived = 'NC/NS' then 'NC/NS'
												else ifnull(rs.IPEA_onboarding_status,'Not Set') end
										from recruiting_schedule rs 
											where rs.type_id = 1 
											and rs.candidate_application_id = candidate_applications.id 
											and date(rs.appointment_date) <= date(now()) 
											order by rs.id desc limit 1) = 'Incomplete'
									and candidate_applications.is_trackable = 1
									and mssql_recruitertracker.Candidate_Current_Status <> 'First Shift Confirmed'
									and mssql_recruitertracker.Candidate_Current_Status <> 'PRN'
									and candidate_applications.is_complete = 0
									and candidate_applications.conversion_state < 19;"
							);

				//IPEA NC/NS
				echo 'IPEA NC/NS' . "\n";
				DB::statement("update candidate_applications 
								left join mssql_recruitertracker on candidate_applications.latest_tracker_id = mssql_recruitertracker.id
								set conversion_state = 15 
								where (
									select rs.IPEA_clinician_arrived 
									from recruiting_schedule rs 
										where rs.type_id = 1 
										and rs.candidate_application_id = candidate_applications.id 
										and date(rs.appointment_date) <= date(now()) 
										order by rs.id desc limit 1) = 'NC/NS'
								and candidate_applications.is_trackable = 1
								and mssql_recruitertracker.Candidate_Current_Status <> 'First Shift Confirmed'
								and mssql_recruitertracker.Candidate_Current_Status <> 'PRN'
								and candidate_applications.is_complete = 0
								and candidate_applications.conversion_state < 19;"
							);

				//IPEA Not Set
				echo 'IPEA Not Set' . "\n";
				DB::statement("update candidate_applications 
								left join mssql_recruitertracker on candidate_applications.latest_tracker_id = mssql_recruitertracker.id
								set conversion_state = 16 
								where (
									select case when rs.IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' 
												when rs.IPEA_clinician_arrived = 'NC/NS' then 'NC/NS'
												else ifnull(rs.IPEA_onboarding_status,'Not Set') end
										from recruiting_schedule rs 
										where rs.type_id = 1 
										and rs.candidate_application_id = candidate_applications.id 
										and date(rs.appointment_date) <= date(now())  
										order by rs.id desc limit 1) = 'Not Set'
								and candidate_applications.is_trackable = 1
								and mssql_recruitertracker.Candidate_Current_Status <> 'First Shift Confirmed'
								and mssql_recruitertracker.Candidate_Current_Status <> 'PRN'
								and candidate_applications.is_complete = 0
								and candidate_applications.conversion_state < 19;"
							);

				//IPEA Ineligible for rehire
				echo 'IPEA Ineligible for rehire' . "\n";
				DB::statement("update candidate_applications 
								left join mssql_recruitertracker on candidate_applications.latest_tracker_id = mssql_recruitertracker.id
								set conversion_state = 17 
								where ifnull((
									select rs.id
										from recruiting_schedule rs 
										where rs.type_id = 1 
										and rs.candidate_application_id = candidate_applications.id 
										and date(rs.appointment_date) <= date(now()) 
										and rs.IPEA_onboarding_status = 'Ineligible for Hire/Rehire'
										order by rs.id desc limit 1),0) <> 0
								and candidate_applications.is_trackable = 1
								and mssql_recruitertracker.Candidate_Current_Status <> 'First Shift Confirmed'
								and mssql_recruitertracker.Candidate_Current_Status <> 'PRN'
								and candidate_applications.is_complete = 0
								and candidate_applications.conversion_state < 19;"
							);

				//IPEA Ready To Work
				echo 'IPEA Ready To Work' . "\n";
				DB::statement("update candidate_applications 
								left join mssql_recruitertracker on candidate_applications.latest_tracker_id = mssql_recruitertracker.id
								set conversion_state = 18
								where (
									select case when rs.IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' 
												when rs.IPEA_clinician_arrived = 'NC/NS' then 'NC/NS'
												else ifnull(rs.IPEA_onboarding_status,'Not Set') end
										from recruiting_schedule rs 
										where rs.type_id = 1 
										and rs.candidate_application_id = candidate_applications.id 
										and date(rs.appointment_date) <= date(now()) 
										order by rs.id desc limit 1) = 'Ready To Work'
								and candidate_applications.is_trackable = 1
								and mssql_recruitertracker.Candidate_Current_Status <> 'First Shift Confirmed'
								and mssql_recruitertracker.Candidate_Current_Status <> 'PRN'
								and candidate_applications.is_complete = 0
								and candidate_applications.conversion_state < 19;"
							);

				//if the tracker is Inactive and it has reached any of the IPEA statuses,  change it to back to IPEA Inactive
				/*
				DB::statement("update candidate_applications 
								left join mssql_recruitertracker on candidate_applications.latest_tracker_id = mssql_recruitertracker.id
								set conversion_state = 13 
								where mssql_recruitertracker.Candidate_Current_Status = 'Inactive' 
									and candidate_applications.is_trackable = 1
									and candidate_applications.is_complete = 0
									and candidate_applications.conversion_state = 18;"
							);
*/
				//pepi/wepi/training Scheduled
				echo 'pepi/weip/training scheduled' . "\n";
				DB::statement("update candidate_applications 
								left join mssql_recruitertracker on candidate_applications.latest_tracker_id = mssql_recruitertracker.id
								set conversion_state = 19
									where mssql_recruitertracker.Candidate_Current_Status 
											IN ('PEPI - Scheduled', 'WEPI - Scheduled', 'Training Shift Scheduled')
									and candidate_applications.is_trackable = 1
									and candidate_applications.is_complete = 0;");

				//pepi/wepi/training Parent Declined
				echo 'pepi/weip/training Parent Declined' . "\n";
				DB::statement("update candidate_applications
								left join mssql_recruitertracker on candidate_applications.latest_tracker_id = mssql_recruitertracker.id
								set conversion_state = 20
								where ifnull(
									(select rs.outcome_reason
										from recruiting_schedule rs 
										where rs.type_id IN (3,4,5) 
										and rs.candidate_application_id = candidate_applications.id
										and rs.outcome = 'Parent Declined'
										and rs.outcome_reason in (
											'Placed on case by different recruiter',
											'Nurse decided not to work for AOC',
											'Cancelled - Ineligible for hire/rehire'
										)
										and date(rs.appointment_date) <= date(now()) 
										order by rs.id desc limit 1),'') <> ''
								and candidate_applications.is_trackable = 1
								and mssql_recruitertracker.Candidate_Current_Status <> 'First Shift Confirmed'
								and mssql_recruitertracker.Candidate_Current_Status <> 'PRN'
								and candidate_applications.is_complete = 0;");
				
				//pepi/wepi/training Nurse Declined
				echo 'pepi/weip/training Nurse Declined' . "\n";
				DB::statement("update candidate_applications
								left join mssql_recruitertracker on candidate_applications.latest_tracker_id = mssql_recruitertracker.id
								set conversion_state = 21
								where ifnull(
									(select rs.id
										from recruiting_schedule rs 
										where rs.candidate_application_id = candidate_applications.id 
										and rs.type_id IN (3,4,5) 
										AND ((rs.outcome = 'Cancelled'
												and rs.outcome_reason = 'Nurse decided not to work for AOC') 
											OR
												(rs.outcome = 'NC/NS') 
											OR 
											(rs.outcome = 'Nurse Declined'
												and rs.outcome_reason in (
												'Placed on case by different recruiter',
												'Nurse decided not to work for AOC',
												'Cancelled - Ineligible for hire/rehire'
											)))
										and date(rs.appointment_date) <= date(now()) 
										order by rs.id desc limit 1),0) <> 0
								and candidate_applications.is_trackable = 1
								and mssql_recruitertracker.Candidate_Current_Status <> 'First Shift Confirmed'
								and mssql_recruitertracker.Candidate_Current_Status <> 'PRN'
								and candidate_applications.is_complete = 0;");

				//pepi/wepi/training Agency
				echo 'pepi/weip/training Agency Declined' . "\n";
 				DB::statement("update candidate_applications
								left join mssql_recruitertracker on candidate_applications.latest_tracker_id = mssql_recruitertracker.id
								set conversion_state = 22
								where ifnull(
									(select rs.outcome_reason
										from recruiting_schedule rs 
										where rs.candidate_application_id = candidate_applications.id 
										and rs.type_id IN (3,4,5) 
										AND rs.outcome = 'Cancelled'
											and rs.outcome_reason in ('Placed on case by different recruiter', 'Cancelled - Ineligible for hire/rehire')
										and date(rs.appointment_date) <= date(now()) 
										order by rs.id desc limit 1),'') <> ''
								and candidate_applications.is_trackable = 1
								and mssql_recruitertracker.Candidate_Current_Status <> 'First Shift Confirmed'
								and mssql_recruitertracker.Candidate_Current_Status <> 'PRN'
								and candidate_applications.is_complete = 0;");								


				//Need to Review New Clients
				echo 'Need To Review New Client' . "\n";
				//question for Rachel on this:
					// if a candidate gets to a further stage like 'Client Match' and the nback to Need to Review New Clients
					// does it get back to Need To Review New Clients or keep the higher status?
					DB::statement("update candidate_applications 
					left join mssql_recruitertracker on candidate_applications.latest_tracker_id = mssql_recruitertracker.id
					set conversion_state = 23 
					where mssql_recruitertracker.Candidate_Current_Status = 'Need to Review New Clients'
						and candidate_applications.is_trackable = 1
						and candidate_applications.is_complete = 0;");

				//Active Coverage (PRN)
				echo 'PRN' . "\n";
				DB::statement("update candidate_applications 
								left join mssql_recruitertracker on candidate_applications.latest_tracker_id = mssql_recruitertracker.id
								set conversion_state = 24
								where mssql_recruitertracker.Candidate_Current_Status = 'PRN'
								and candidate_applications.is_trackable = 1
								and candidate_applications.is_complete = 0;");

				//Client Match - First Shift Scheduled
				//this covers any that we missed
				echo 'Client Match - First Shift Scheduled' . "\n";
				DB::statement("update candidate_applications 
								left join mssql_recruitertracker on candidate_applications.latest_tracker_id = mssql_recruitertracker.id
								set conversion_state = 25 
								where mssql_recruitertracker.Candidate_Current_Status = 'Client Match'
									and mssql_recruitertracker.is_active = 1
									and candidate_applications.is_trackable = 1
									and candidate_applications.is_complete = 0;");

				//Client Match Parent Declined
				echo 'Client Match - Parent Declined' . "\n";
				DB::statement("update candidate_applications
								left join mssql_recruitertracker on candidate_applications.latest_tracker_id = mssql_recruitertracker.id
								set conversion_state = 26 
								where ifnull(
									(select rs.outcome_reason
										from recruiting_schedule rs 
										where rs.type_id = 6 
										and rs.candidate_application_id = candidate_applications.id
										and rs.outcome IN('Client/Caregiver declined services', 
											'Client hospitalized - nurse discussing other cases')
										and date(rs.appointment_date) <= date(now()) 
										order by rs.id desc limit 1),'') <> ''
								and candidate_applications.is_trackable = 1
								and mssql_recruitertracker.Candidate_Current_Status <> 'First Shift Confirmed'
								and mssql_recruitertracker.Candidate_Current_Status <> 'PRN'
								and candidate_applications.is_complete = 0;");
			
				//Client Match Nurse Declined
				echo 'Client Match - Nurse Declined' . "\n";
				DB::statement("update candidate_applications
								left join mssql_recruitertracker on candidate_applications.latest_tracker_id = mssql_recruitertracker.id
								set conversion_state = 27
								where ifnull(
									(select rs.outcome_reason
										from recruiting_schedule rs 
										where rs.type_id = 6 
										and rs.candidate_application_id = candidate_applications.id
										and (rs.outcome in ('Nurse decided not to work for Angels of Care', 
													'Nurse declined case - nurse discussing other cases')
												OR (rs.outcome = 'Cancelled' and mssql_recruitertracker.inactive_reason = 'Nurse decided not to work for AOC')
												OR rs.outcome = 'NC/NS'
											)
										and date(rs.appointment_date) <= date(now()) 
										order by rs.id desc limit 1),'') <> ''
								and candidate_applications.is_trackable = 1
								and mssql_recruitertracker.Candidate_Current_Status <> 'First Shift Confirmed'
								and mssql_recruitertracker.Candidate_Current_Status <> 'PRN'
								and candidate_applications.is_complete = 0;");
							
				//Client Match Agency
				echo 'Client Match - Agency Declined' . "\n";
				DB::statement("update candidate_applications
								left join mssql_recruitertracker on candidate_applications.latest_tracker_id = mssql_recruitertracker.id
								set conversion_state = 28 
								where ifnull(
									(select rs.outcome_reason
										from recruiting_schedule rs 
										where rs.type_id = 6
										and rs.candidate_application_id = candidate_applications.id
										and (rs.outcome = 'Client Authorization Denied'
												OR (rs.outcome = 'Cancelled' and mssql_recruitertracker.inactive_reason = 'Placed on case by different recruiter')
												OR (rs.outcome = 'Cancelled' and mssql_recruitertracker.inactive_reason = 'ineligible')
											)
										and date(rs.appointment_date) <= date(now()) 
										order by rs.id desc limit 1),'') <> ''
								and candidate_applications.is_trackable = 1
								and mssql_recruitertracker.Candidate_Current_Status <> 'First Shift Confirmed'
								and mssql_recruitertracker.Candidate_Current_Status <> 'PRN'
								and candidate_applications.is_complete = 0;");
				
				echo 'First Shift Confirmed' . "\n";			
				DB::statement("update candidate_applications 
								left join mssql_recruitertracker on candidate_applications.latest_tracker_id = mssql_recruitertracker.id
								set conversion_state = 29 
								where mssql_recruitertracker.Candidate_Current_Status = 'First Shift Confirmed'
								and candidate_applications.is_trackable = 1
								and candidate_applications.is_complete = 0;");
							
				//AFTER everything else is done,  need to run queries that set applications as completed 

				//remark this off until they confirm the conditions
				/*
				echo 'Mark applications complete' . "\n";
				DB::statement("update  `candidate_applications` 
								left join mssql_recruitertracker on candidate_applications.latest_tracker_id = mssql_recruitertracker.id 
								set is_complete = 1 
								where is_trackable = 1 
								and latest_tracker_id is not null 
								and mssql_recruitertracker.Candidate_Current_Status in ('First Shift Confirmed','PRN','Inactive');");
				DB::statement("update  `candidate_applications` 
								set is_complete = 1 
								where is_trackable = 1 
								and ifnull(latest_tracker_id,0) = 0
								and candidate_applications.workflowState in ('Rejected by Agency','Offer Rejecte','Rejected Offer','Do Not Contact');");
				*/			
        }catch (\Exception $e){
			H::ExcelLog($e->getMessage(),'setConversionReportRecordsErrorLog.log');
        }
        return Command::SUCCESS;
    }
}
