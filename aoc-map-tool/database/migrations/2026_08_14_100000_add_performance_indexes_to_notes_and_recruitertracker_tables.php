<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement('CREATE INDEX idx_providers_employment ON mssql_providers(EmploymentStatusID, id);');
        DB::statement('CREATE INDEX idx_recruitertracker_prn ON mssql_recruitertracker(is_prn_confirmed, nurse_status, IPEA_Date);');
        DB::statement('CREATE INDEX idx_patients_prn ON mssql_patients(id, isPRN);');
    }

    public function down()
    {
        DB::statement('DROP INDEX idx_recruitertracker_prn ON mssql_recruitertracker');
        DB::statement('DROP INDEX idx_patients_prn ON mssql_patients');
        DB::statement('DROP INDEX idx_providers_employment ON mssql_providers');
    }
};
