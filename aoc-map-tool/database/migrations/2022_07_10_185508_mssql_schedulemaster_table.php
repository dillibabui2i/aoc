<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MssqlSchedulemasterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mssql_schedulemaster', function (Blueprint $table) {
            $table->id('id');
            $table->integer('MSSQL_ID')->nullable();
            $table->timestamp('CreatedOn')->nullable();
            $table->timestamp('CreatedBy')->nullable();
            $table->integer('FacilityID')->nullable();
            $table->integer('CGTaskID')->nullable();
            $table->integer('PatientID')->nullable();
            $table->integer('ProviderID')->nullable();
            $table->integer('InsuranceID')->nullable();
            $table->integer('ServiceCodeID')->nullable();
            $table->timestamp('PlannedStart')->nullable();
            $table->timestamp('PlannedEnd')->nullable();
            $table->timestamp('ActualStart')->nullable();
            $table->timestamp('ActualEnd')->nullable();
            $table->float('PlannedHours',8,2)->nullable();
            $table->float('ActualHours',8,2)->nullable();
            $table->float('Hours',8,2)->nullable();
            $table->string('ScheduleStatusID',5)->nullable();
            $table->string('EChartStatusID',5)->nullable();
            $table->string('Miles',8,2)->nullable();
            $table->integer('IsAuthRequired')->nullable();
            $table->string('AuthorizationStatusID',5)->nullable();
            $table->string('PaybleHours',10)->nullable();
            $table->string('MissedVisitReasonID',5)->nullable();
            $table->date('WeekStart')->nullable();
            $table->integer('EpisodeID')->nullable();
            $table->integer('IsPRNVisit')->nullable();
            $table->string('RevenueCode',255)->nullable();
            $table->string('GCode',255)->nullable();
            $table->string('Modifier1',255)->nullable();
            $table->string('Modifier2',255)->nullable();
            $table->string('Modifier3',255)->nullable();
            $table->string('Modifier4',255)->nullable();
            $table->float('Units',8,2)->nullable();
            $table->integer('HasChild')->nullable();
            $table->integer('IsChild')->nullable();
            $table->integer('ParentCgTaskID')->nullable();
            $table->integer('IsBillable')->nullable();
            $table->integer('IsPayable')->nullable();
            $table->integer('IsBilled')->nullable();
            $table->integer('IsPaid')->nullable();
            $table->string('BillRate',255)->nullable();
            $table->string('PayRate',255)->nullable();
            $table->string('BillModeID',255)->nullable();
            $table->string('PayMode',255)->nullable();
            $table->string('BilledAmount',255)->nullable();
            $table->string('PayrolledAmount',255)->nullable();
            $table->string('RegularAmount',255)->nullable();
            $table->string('OTAmount',255)->nullable();
            $table->string('BonusAmount',255)->nullable();
            $table->string('BaseRate',255)->nullable();
            $table->string('CancellationReason',255)->nullable();
            $table->float('ContractualRate',8,2)->nullable();
            $table->string('ContractualModeID',255)->nullable();
            $table->string('ContractualAmount',255)->nullable();
            $table->string('NonBillableReason',255)->nullable();
            $table->float('RegularHours',8,2)->nullable();
            $table->float('OTHours',8,2)->nullable();
            $table->integer('isMiscVisit')->nullable();
            $table->string('Cost',255)->nullable();
            $table->string('Revenue',255)->nullable();
            $table->string('ContractualRevenue',255)->nullable();
            $table->string('UserBranchID',255)->nullable();
            $table->date('ScheduleDate')->nullable();
            $table->string('Profit',255)->nullable();
            $table->string('ProfitContractual',255)->nullable();
            $table->string('Margin',255)->nullable();
            $table->string('MarginContractual',255)->nullable();
            $table->float('BilledHours',8,2)->nullable();
            $table->float('ScheduleProductivityPoints',8,2)->nullable();
            $table->date('PayrollWeekStartDate')->nullable();
            $table->integer('WeekNo')->nullable();
            $table->string('ClinicianProductivityPerWeek',255)->nullable();
            $table->integer('AuthorizationID')->nullable();

            $table->string('ScheduleMiscVisitID',255)->nullable();
            $table->string('PrimaryIcdCode',255)->nullable();
            $table->string('SurgicalIcdCode',255)->nullable();
            $table->string('OtherDiagnosisCode',255)->nullable();
            $table->float('HolidayHours',8,2)->nullable();
            $table->float('HolidayPayrollAmount',8,2)->nullable();
            $table->float('HolidayPayrollRates',8,2)->nullable();
            $table->float('OTPayrollRate',8,2)->nullable();
            $table->date('AddedOn')->nullable();
            $table->integer('EMRID')->nullable();
            $table->integer('ClaimDetailID')->nullable();
            $table->string('CalculatedRegularHours',255)->nullable();
            $table->string('CalculatedRegularRate',255)->nullable();
            $table->float('ReportingCompletedHours',8,2)->nullable();
            $table->float('ReportingMissedHours',8,2)->nullable();
            $table->float('ReportingPlannedHours',8,2)->nullable();

            $table->string('MissedVisitReasonCategoryID',255)->nullable();
            $table->float('CalculatedRevenue',8,2)->nullable();
            $table->float('CalculatedCost',8,2)->nullable();
            $table->float('GrossMargin',8,2)->nullable();

            $table->string('MiscVisitReason',255)->nullable();
            $table->integer('DistinctServiceCodeID')->nullable();

            $table->float('PlacedHours',8,2)->nullable();
            $table->float('ReportingHours',8,2)->nullable();
            $table->integer('SecondaryPayer')->nullable();
            $table->integer('TertiaryPayer')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->boolean('is_updated')->default(0);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
