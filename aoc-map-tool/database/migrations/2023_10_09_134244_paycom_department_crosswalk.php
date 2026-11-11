<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PaycomDepartmentCrosswalk extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paycom_department_crosswalk', function (Blueprint $table) {
            $table->id();
            $table->string('Department');
            $table->string('Location_Desc');
            $table->string('Division_Desc');
        });
        $data = [
            [
                'Department' => "ABA - Field",
                'Location_Desc' => 'Colorado Springs',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'ABA - Office',
                'Location_Desc' => 'Colorado OH',
                'Division_Desc' => 'CO OFFICE STAFF'
            ],
            [
                'Department' => 'Accounting',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'Executives,'
            ],
            [
                'Department' => 'Accounting',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'TX Salary Office Staff'
            ],
            [
                'Department' => 'Attendant Care - Non-Skilled',
                'Location_Desc' => 'Abilene',
                'Division_Desc' => 'Texas Attendants'
            ],
            [
                'Department' => 'Attendant Care - Non-Skilled',
                'Location_Desc' => 'Amarillo',
                'Division_Desc' => 'Texas Attendants',
            ],
            [
                'Department' => 'Attendant Care - Non-Skilled',
                'Location_Desc' => 'Austin',
                'Division_Desc' => 'Texas Attendants',
            ],
            [
                'Department' => 'Attendant Care - Non-Skilled',
                'Location_Desc' => 'Colorado Springs',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'Attendant Care - Non-Skilled',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'Texas Attendants',
            ],
            [
                'Department' => 'Attendant Care - Non-Skilled',
                'Location_Desc' => 'Denver',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'Attendant Care - Non-Skilled',
                'Location_Desc' => 'Fort Collins',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'Attendant Care - Non-Skilled',
                'Location_Desc' => 'Fort Worth',
                'Division_Desc' => 'Texas Attendants',
            ],
            [
                'Department' => 'Attendant Care - Non-Skilled',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'Texas Attendants',
            ],
            [
                'Department' => 'Attendant Care - Non-Skilled',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'Attendant Care - Non-Skilled',
                'Location_Desc' => 'Lubbock',
                'Division_Desc' => 'Texas Attendants',
            ],
            [
                'Department' => 'Attendant Care - Non-Skilled',
                'Location_Desc' => 'Odessa',
                'Division_Desc' => 'Texas Attendants',
            ],
            [
                'Department' => 'Attendant Care - Non-Skilled',
                'Location_Desc' => 'Pueblo',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'Attendant Care - Non-Skilled',
                'Location_Desc' => 'San Antonio',
                'Division_Desc' => 'Texas Attendants',
            ],
            [
                'Department' => 'Attendant Care - Non-Skilled',
                'Location_Desc' => 'Sherman',
                'Division_Desc' => 'Texas Attendants',
            ],
            [
                'Department' => 'Attendant Care - Non-Skilled',
                'Location_Desc' => 'Sherman',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'Attendant Care - Non-Skilled',
                'Location_Desc' => 'Temple',
                'Division_Desc' => 'Texas Attendants',
            ],
            [
                'Department' => 'Attendant Care - Non-Skilled',
                'Location_Desc' => 'Tyler',
                'Division_Desc' => 'Texas Attendants',
            ],
            [
                'Department' => 'Attendant Care - Non-Skilled',
                'Location_Desc' => 'Wichita Falls',
                'Division_Desc' => 'Texas Attendants',
            ],
            [
                'Department' => 'Authorizations',
                'Location_Desc' => 'Austin',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Authorizations',
                'Location_Desc' => 'Colorado OH',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'Authorizations',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Authorizations',
                'Location_Desc' => 'El Paso',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Authorizations',
                'Location_Desc' => 'Fort Worth',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Authorizations',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Authorizations',
                'Location_Desc' => 'North Carolina OH',
                'Division_Desc' => 'NC OFFICE STAFF',
            ],
            [
                'Department' => 'Authorizations',
                'Location_Desc' => 'Region I',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Authorizations',
                'Location_Desc' => 'Region II',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Authorizations',
                'Location_Desc' => 'Region III',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Authorizations',
                'Location_Desc' => 'Region III',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Authorizations',
                'Location_Desc' => 'Region IV',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Authorizations',
                'Location_Desc' => 'Region V',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Authorizations',
                'Location_Desc' => 'Sherman',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Authorizations',
                'Location_Desc' => 'Texas OH',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Authorizations',
                'Location_Desc' => 'Tyler',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Authorizations',
                'Location_Desc' => 'Wichita Falls',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Branch Operations',
                'Location_Desc' => 'CO CNA Academy',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'Branch Operations',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Branch Operations',
                'Location_Desc' => 'Dubois',
                'Division_Desc' => 'PA OFFICE STAFF'
            ],
            [
                'Department' => 'Branch Operations',
                'Location_Desc' => 'Jacksonville',
                'Division_Desc' => 'FL All Care Office Staff',
            ],
            [
                'Department' => 'Branch Operations',
                'Location_Desc' => 'North Jacksonville',
                'Division_Desc' => 'FL- N JACKSONVILLE OFFICE STAFF',
            ],
            [
                'Department' => 'Branch Operations',
                'Location_Desc' => 'Orange Park',
                'Division_Desc' => 'FL - ORANGE PARK OFFICE STAFF',
            ],
            [
                'Department' => 'Branch Operations',
                'Location_Desc' => 'Orlando',
                'Division_Desc' => 'FL All Care Office Staff',
            ],
            [
                'Department' => 'Branch Operations',
                'Location_Desc' => 'Pittsburgh',
                'Division_Desc' => 'PA OFFICE STAFF',
            ],
            [
                'Department' => 'Branch Operations',
                'Location_Desc' => 'Region III',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Branch Operations',
                'Location_Desc' => 'Sherman',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Branch Operations',
                'Location_Desc' => 'Tampa',
                'Division_Desc' => 'FL All Care Office Staff',
            ],
            [
                'Department' => 'Branch Operations',
                'Location_Desc' => 'Temple',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Branch Operations',
                'Location_Desc' => 'Vero Beach FL',
                'Division_Desc' => 'Vero Beach Office Staff',
            ],
            [
                'Department' => 'Branch Operations',
                'Location_Desc' => 'Wichita Falls',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Abilene',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Allentown',
                'Division_Desc' => 'PA OFFICE STAFF',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Amarillo',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Charlotte',
                'Division_Desc' => 'NC OFFICE STAFF',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Colorado Springs',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Columbia',
                'Division_Desc' => 'NC OFFICE STAFF',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Corpus Christi',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Denver',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Dubois',
                'Division_Desc' => 'PA OFFICE STAFF',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'El Paso',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Fort Worth',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Gainesville',
                'Division_Desc' => 'FL All Care Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Harrisburg',
                'Division_Desc' => 'PA OFFICE STAFF',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Jacksonville',
                'Division_Desc' => 'FL All Care Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Lubbock',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'North Carolina OH',
                'Division_Desc' => 'NC OFFICE STAFF',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Odessa',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Odessa',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Orlando',
                'Division_Desc' => 'FL All Care Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Phoenix',
                'Division_Desc' => 'PHOENIX OFFICE STAFF',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Pittsburgh',
                'Division_Desc' => 'PA OFFICE STAFF',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Pueblo',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Raleigh',
                'Division_Desc' => 'NC OFFICE STAFF',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Reading',
                'Division_Desc' => 'PA OFFICE STAFF',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'San Antonio',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'San Antonio',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Sherman',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Sherman',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Tampa',
                'Division_Desc' => 'FL All Care Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Temple',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Temple',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Tucson',
                'Division_Desc' => 'TUCSON OFFICE STAFF',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Tyler',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Wichita Falls',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Wilmington',
                'Division_Desc' => 'NC OFFICE STAFF',
            ],
            [
                'Department' => 'Case Management',
                'Location_Desc' => 'Winston Salem',
                'Division_Desc' => 'NC OFFICE STAFF',
            ],
            [
                'Department' => 'Case Management - Therapy',
                'Location_Desc' => 'Amarillo',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Case Management - Therapy',
                'Location_Desc' => 'Colorado Springs',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'Case Management - Therapy',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Case Management - Therapy',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Case Management - Therapy',
                'Location_Desc' => 'Denver',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'Case Management - Therapy',
                'Location_Desc' => 'El Paso',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'Case Management - Therapy',
                'Location_Desc' => 'El Paso',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Case Management - Therapy',
                'Location_Desc' => 'El Paso',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Case Management - Therapy',
                'Location_Desc' => 'Fort Collins',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'Case Management - Therapy',
                'Location_Desc' => 'Fort Worth',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Case Management - Therapy',
                'Location_Desc' => 'Fort Worth',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Case Management - Therapy',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'Case Management - Therapy',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Case Management - Therapy',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Case Management - Therapy',
                'Location_Desc' => 'Lubbock',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'Case Management - Therapy',
                'Location_Desc' => 'Lubbock',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Case Management - Therapy',
                'Location_Desc' => 'Lubbock',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Case Management - Therapy',
                'Location_Desc' => 'Odessa',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Case Management - Therapy',
                'Location_Desc' => 'San Antonio',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'Case Management - Therapy',
                'Location_Desc' => 'San Antonio',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Case Management - Therapy',
                'Location_Desc' => 'Sherman',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Case Management - Therapy',
                'Location_Desc' => 'Texas OH',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Case Management - Therapy',
                'Location_Desc' => 'Tyler',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Certified Nursing Aide - Basic',
                'Location_Desc' => 'Allentown',
                'Division_Desc' => 'PA FIELD STAFF',
            ],
            [
                'Department' => 'Certified Nursing Aide - Basic',
                'Location_Desc' => 'Charlotte',
                'Division_Desc' => 'NC FIELD STAFF',
            ],
            [
                'Department' => 'Certified Nursing Aide - Basic',
                'Location_Desc' => '',
                'Division_Desc' => 'PA FIELD STAFF',
            ],
            [
                'Department' => 'Certified Nursing Aide - Basic',
                'Location_Desc' => 'Colorado Springs',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'Certified Nursing Aide - Basic',
                'Location_Desc' => 'Denver',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'Certified Nursing Aide - Basic',
                'Location_Desc' => 'Dubois',
                'Division_Desc' => 'PA FIELD STAFF',
            ],
            [
                'Department' => 'Certified Nursing Aide - Basic',
                'Location_Desc' => 'Fort Collins',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'Certified Nursing Aide - Basic',
                'Location_Desc' => 'Gainesville',
                'Division_Desc' => 'FL All Care Field Staff',
            ],
            [
                'Department' => 'Certified Nursing Aide - Basic',
                'Location_Desc' => 'Greensboro',
                'Division_Desc' => 'NC FIELD STAFF',
            ],
            [
                'Department' => 'Certified Nursing Aide - Basic',
                'Location_Desc' => 'Harrisburg',
                'Division_Desc' => 'PA FIELD STAFF',
            ],
            [
                'Department' => 'Certified Nursing Aide - Basic',
                'Location_Desc' => 'Jacksonville',
                'Division_Desc' => 'FL All Care Field Staff',
            ],
            [
                'Department' => 'Certified Nursing Aide - Basic',
                'Location_Desc' => 'Orange Park',
                'Division_Desc' => 'FL - ORANGE PARK FIELD STAFF',
            ],
            [
                'Department' => 'Certified Nursing Aide - Basic',
                'Location_Desc' => 'Orlando',
                'Division_Desc' => 'FL All Care Field Staff',
            ],
            [
                'Department' => 'Certified Nursing Aide - Basic',
                'Location_Desc' => 'Pittsburgh',
                'Division_Desc' => 'PA FIELD STAFF',
            ],
            [
                'Department' => 'Certified Nursing Aide - Basic',
                'Location_Desc' => 'Pueblo',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'Certified Nursing Aide - Basic',
                'Location_Desc' => 'Raleigh',
                'Division_Desc' => 'NC FIELD STAFF',
            ],
            [
                'Department' => 'Certified Nursing Aide - Basic',
                'Location_Desc' => 'Reading',
                'Division_Desc' => 'PA FIELD STAFF',
            ],
            [
                'Department' => 'Certified Nursing Aide - Basic',
                'Location_Desc' => 'Tampa',
                'Division_Desc' => 'FL All Care Field Staff',
            ],
            [
                'Department' => 'Certified Nursing Aide - Basic',
                'Location_Desc' => 'Wilmington',
                'Division_Desc' => 'NC FIELD STAFF',
            ],
            [
                'Department' => 'Certified Nursing Aide - Basic',
                'Location_Desc' => 'Winston Salem',
                'Division_Desc' => 'NC FIELD STAFF',
            ],
            [
                'Department' => 'Certified Nursing Aide - Exten',
                'Location_Desc' => 'Denver',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'AOC - Medical Supply',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Austin',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Charlotte',
                'Division_Desc' => 'NC OFFICE STAFF',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Corpus Christi',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Dubois',
                'Division_Desc' => 'PA OFFICE STAFF',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'El Paso',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'El Paso',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Fort Worth',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Fort Worth',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Harrisburg',
                'Division_Desc' => 'PA OFFICE STAFF',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Jacksonville',
                'Division_Desc' => 'FL All Care Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Lubbock',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Odessa',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Orlando',
                'Division_Desc' => 'FL - ALL CARE OFFICE STAFF',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Pennsylvania OH',
                'Division_Desc' => 'PA OFFICE STAFF',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Phoenix',
                'Division_Desc' => 'PHOENIX OFFICE STAFF',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Pittsburgh',
                'Division_Desc' => 'PA OFFICE STAFF',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Pueblo',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Raleigh',
                'Division_Desc' => 'NC OFFICE STAFF',

            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Reading',
                'Division_Desc' => 'PA OFFICE STAFF',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'San Antonio',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'San Antonio',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Sherman',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Sherman',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Temple',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Temple',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Texas OH',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Texas OH',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Tyler',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Tyler',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Vero Beach FL',
                'Division_Desc' => 'Vero Beach Office Staff',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Wilmington',
                'Division_Desc' => 'NC OFFICE STAFF',
            ],
            [
                'Department' => 'Client Care Coordinator',
                'Location_Desc' => 'Winston Salem',
                'Division_Desc' => 'NC OFFICE STAFF',
            ],
            [
                'Department' => 'Compliance',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Compliance',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Compliance',
                'Location_Desc' => 'Texas OH',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Coverage',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Coverage',
                'Location_Desc' => 'Region I',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Coverage',
                'Location_Desc' => 'Region II',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Coverage',
                'Location_Desc' => 'Region V',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Coverage',
                'Location_Desc' => 'Region VI',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Coverage',
                'Location_Desc' => 'Texas OH',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'DME - Medical Supply',
                'Location_Desc' => 'AOC - Medical Supply',
                'Division_Desc' => 'Executives',
            ],
            [
                'Department' => 'Executive',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'Executives',
            ],
            [
                'Department' => 'Executive',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Executive',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Government Relations',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'Executives',
            ],
            [
                'Department' => 'Government Relations',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Health Home',
                'Location_Desc' => 'Health Home',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Health Home',
                'Location_Desc' => 'Health Home',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Human Resources',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'Executives',
            ],
            [
                'Department' => 'Human Resources',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'PA OFFICE STAFF',
            ],
            [
                'Department' => 'Human Resources',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'TX Salary Office Staff',  
            ],
            [
                'Department' => 'ICM',
                'Location_Desc' => 'Texas OH',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'ICM',
                'Location_Desc' => 'Texas OH',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'ICM',
                'Location_Desc' => 'Texas OH',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Intake',
                'Location_Desc' => 'Colorado OH',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'Intake',
                'Location_Desc' => 'North Carolina OH',
                'Division_Desc' => 'NC OFFICE STAFF',
            ],
            [
                'Department' => 'Intake',
                'Location_Desc' => 'Region I',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Intake',
                'Location_Desc' => 'Region II',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Intake',
                'Location_Desc' => 'Region III',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Intake',
                'Location_Desc' => 'Region VI',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Intake',
                'Location_Desc' => 'Sherman',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'IT',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'IT',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'NC OFFICE STAFF',
            ],
            [
                'Department' => 'IT',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'IT',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Licensed Health Aide',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'Licensed Health Aide',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'Licensed Health Aide',
                'Location_Desc' => 'Phoenix',
                'Division_Desc' => 'PHOENIX FIELD STAFF',
            ],
            [
                'Department' => 'LVN/LPN-Coverage',
                'Location_Desc' => 'Columbia',
                'Division_Desc' => 'NC FIELD STAFF',
            ],
            [
                'Department' => 'LVN/LPN-Coverage',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Coverage',
                'Location_Desc' => 'Denver',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'LVN/LPN-Coverage',
                'Location_Desc' => 'Harrisburg',
                'Division_Desc' => 'PA FIELD STAFF',
            ],
            [
                'Department' => 'LVN/LPN-Coverage',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Coverage',
                'Location_Desc' => 'Sherman',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Coverage',
                'Location_Desc' => 'Temple',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Coverage',
                'Location_Desc' => 'Tyler',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Abilene',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Allentown',
                'Division_Desc' => 'PA FIELD STAFF',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Amarillo',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Austin',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Charlotte',
                'Division_Desc' => 'NC FIELD STAFF',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Colorado Springs',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Columbia',
                'Division_Desc' => 'NC FIELD STAFF',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Corpus Christi',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Denver',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Dubois',
                'Division_Desc' => 'PA FIELD STAFF',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'El Paso',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Fort Collins',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Fort Worth',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Gainesville',
                'Division_Desc' => 'FL All Care Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Harrisburg',
                'Division_Desc' => 'PA FIELD STAFF',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Jacksonville',
                'Division_Desc' => 'FL - ALL CARE OFFICE STAFF',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Jacksonville',
                'Division_Desc' => 'FL All Care Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Lubbock',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Odessa',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Orange Park',
                'Division_Desc' => 'FL - ORANGE PARK FIELD STAFF',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Orlando',
                'Division_Desc' => 'FL All Care Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Phoenix',
                'Division_Desc' => 'PHOENIX FIELD STAFF',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Pittsburgh',
                'Division_Desc' => 'PA FIELD STAFF',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Pueblo',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Raleigh',
                'Division_Desc' => 'NC FIELD STAFF',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Reading',
                'Division_Desc' => 'PA FIELD STAFF',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'San Antonio',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Sherman',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Tampa',
                'Division_Desc' => 'FL All Care Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Temple',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Tucson',
                'Division_Desc' => 'TUCSON FIELD STAFF',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Tyler',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Vero Beach FL',
                'Division_Desc' => 'Vero Beach Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Wichita Falls',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Wilmington',
                'Division_Desc' => 'NC FIELD STAFF',
            ],
            [
                'Department' => 'LVN/LPN-Regular',
                'Location_Desc' => 'Winston Salem',
                'Division_Desc' => 'NC FIELD STAFF',
            ],
            [
                'Department' => 'LVN/LPN-Spec',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Spec',
                'Location_Desc' => 'Odessa',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'LVN/LPN-Spec',
                'Location_Desc' => 'San Antonio',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'Marketing - PDN',
                'Location_Desc' => 'Austin',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Marketing - PDN',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Marketing - PDN',
                'Location_Desc' => 'El Paso',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Marketing - PDN',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Marketing - PDN',
                'Location_Desc' => 'Pennsylvania OH',
                'Division_Desc' => 'PA OFFICE STAFF',
            ],
            [
                'Department' => 'Marketing - PDN',
                'Location_Desc' => 'San Antonio',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Marketing - PDN',
                'Location_Desc' => 'Texas OH',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Marketing - Therapy',
                'Location_Desc' => 'Colorado OH',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'Marketing - Therapy',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Marketing - Therapy',
                'Location_Desc' => 'El Paso',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Marketing - Therapy',
                'Location_Desc' => 'Fort Worth',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Marketing - Therapy',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Marketing - Therapy',
                'Location_Desc' => 'Lubbock',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Marketing - Therapy',
                'Location_Desc' => 'San Antonio',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Orders',
                'Location_Desc' => 'Texas OH',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Orders',
                'Location_Desc' => 'Texas OH',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Orientation Coordinators',
                'Location_Desc' => 'Abilene',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Orientation Coordinators',
                'Location_Desc' => 'Amarillo',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Orientation Coordinators',
                'Location_Desc' => 'Austin',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Orientation Coordinators',
                'Location_Desc' => 'Charlotte',
                'Division_Desc' => 'NC OFFICE STAFF',
            ],
            [
                'Department' => 'Orientation Coordinators',
                'Location_Desc' => 'Colorado Springs',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'Orientation Coordinators',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Orientation Coordinators',
                'Location_Desc' => 'Denver',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'Orientation Coordinators',
                'Location_Desc' => 'El Paso',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Orientation Coordinators',
                'Location_Desc' => 'Fort Worth',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Orientation Coordinators',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Orientation Coordinators',
                'Location_Desc' => 'Lubbock',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Orientation Coordinators',
                'Location_Desc' => 'Pittsburgh',
                'Division_Desc' => 'PA OFFICE STAFF',
            ],
            [
                'Department' => 'Orientation Coordinators',
                'Location_Desc' => 'Pueblo',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'Orientation Coordinators',
                'Location_Desc' => 'Raleigh',
                'Division_Desc' => 'NC OFFICE STAFF',
            ],
            [
                'Department' => 'Orientation Coordinators',
                'Location_Desc' => 'San Antonio',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Orientation Coordinators',
                'Location_Desc' => 'Sherman',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Orientation Coordinators',
                'Location_Desc' => 'Temple',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Orientation Coordinators',
                'Location_Desc' => 'Tyler',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Orientation Coordinators',
                'Location_Desc' => 'Wichita Falls',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Orientation Coordinators',
                'Location_Desc' => 'Winston Salem',
                'Division_Desc' => 'NC OFFICE STAFF',
            ],
            [
                'Department' => 'OT',
                'Location_Desc' => 'Colorado Springs',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'OT',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'OT',
                'Location_Desc' => 'Denver',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'OT',
                'Location_Desc' => 'El Paso',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'OT',
                'Location_Desc' => 'Fort Collins',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'OT',
                'Location_Desc' => 'Fort Worth',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'OT',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'OT',
                'Location_Desc' => 'Lubbock',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'OT',
                'Location_Desc' => 'Odessa',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'OT',
                'Location_Desc' => 'Pueblo',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'OT',
                'Location_Desc' => 'San Antonio',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'OT',
                'Location_Desc' => 'Sherman',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'OT',
                'Location_Desc' => 'Tyler',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'OTA',
                'Location_Desc' => 'Amarillo',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'OTA',
                'Location_Desc' => 'Colorado Springs',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'OTA',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'OTA',
                'Location_Desc' => 'Denver',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'OTA',
                'Location_Desc' => 'El Paso',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'OTA',
                'Location_Desc' => 'Fort Worth',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'OTA',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'OTA',
                'Location_Desc' => 'Odessa',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'OTA',
                'Location_Desc' => 'San Antonio',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'OTA',
                'Location_Desc' => 'Sherman',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'OTA',
                'Location_Desc' => 'Tyler',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'Payroll and Benefits',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Payroll and Benefits',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'PPEC',
                'Location_Desc' => 'Jacksonville',
                'Division_Desc' => 'FL- N JACKSONVILLE FIELD STAFF',
            ],
            [
                'Department' => 'PPEC',
                'Location_Desc' => 'North Jacksonville',
                'Division_Desc' => 'FL All Care Field Staff',
            ],
            [
                'Department' => 'PPEC',
                'Location_Desc' => 'North Jacksonville',
                'Division_Desc' => 'FL- N JACKSONVILLE FIELD STAFF',
            ],
            [
                'Department' => 'PPEC',
                'Location_Desc' => 'North Jacksonville',
                'Division_Desc' => 'FL- N JACKSONVILLE OFFICE STAFF',
            ],
            [
                'Department' => 'PPEC',
                'Location_Desc' =>' Orange Park',
                'Division_Desc' => 'FL - ORANGE PARK FIELD STAFF',
            ],
            [
                'Department' => 'PPEC',
                'Location_Desc' => 'Orange Park',
                'Division_Desc' => 'FL - ORANGE PARK OFFICE STAFF',
            ],
            [
                'Department' => 'PT',
                'Location_Desc' => 'Amarillo',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'PT',
                'Location_Desc' => 'Colorado Springs',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'PT',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'PT',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'PT',
                'Location_Desc' => 'Denver',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'PT',
                'Location_Desc' => 'El Paso',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'PT',
                'Location_Desc' => 'Fort Collins',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'PT',
                'Location_Desc' => 'Fort Worth',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'PT',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'PT',
                'Location_Desc' => 'Lubbock',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'PT',
                'Location_Desc' => 'Odessa',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'PT',
                'Location_Desc' => 'Pueblo',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'PT',
                'Location_Desc' => 'San Antonio',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'PT',
                'Location_Desc' => 'Sherman',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'PT',
                'Location_Desc' => 'Tyler',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'PTA',
                'Location_Desc' => 'Amarillo',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'PTA',
                'Location_Desc' => 'Colorado Springs',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'PTA',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'PTA',
                'Location_Desc' => 'Denver',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'PTA',
                'Location_Desc' => 'El Paso',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'PTA',
                'Location_Desc' => 'Fort Collins',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'PTA',
                'Location_Desc' => 'Fort Worth',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'PTA',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'PTA',
                'Location_Desc' => 'Lubbock',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'PTA',
                'Location_Desc' => 'Odessa',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'PTA',
                'Location_Desc' => 'San Antonio',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'PTA',
                'Location_Desc' => 'Tyler',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'Quality Assurance',
                'Location_Desc' => 'Arizona OH',
                'Division_Desc' => 'PHOENIX OFFICE STAFF',
            ],
            [
                'Department' => 'Quality Assurance',
                'Location_Desc' => 'Colorado OH',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'Quality Assurance',
                'Location_Desc' => 'Pittsburgh',
                'Division_Desc' => 'PA OFFICE STAFF',
            ],
            [
                'Department' => 'Quality Assurance',
                'Location_Desc' => 'Texas OH',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Recruiting',
                'Location_Desc' => 'Arizona OH',
                'Division_Desc' => 'PHOENIX OFFICE STAFF',
            ],
            [
                'Department' => 'Recruiting',
                'Location_Desc' => 'Colorado OH',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'Recruiting',
                'Location_Desc' => 'Florida OH',
                'Division_Desc' => 'FL All Care Office Staff',
            ],
            [
                'Department' => 'Recruiting',
                'Location_Desc' => 'North Carolina OH',
                'Division_Desc' => 'NC OFFICE STAFF',
            ],
            [
                'Department' => 'Recruiting',
                'Location_Desc' => 'North Carolina OH',
                'Division_Desc' => 'NC OFFICE STAFF',
            ],
            [
                'Department' => 'Recruiting',
                'Location_Desc' => 'Pennsylvania OH',
                'Division_Desc' => 'NC OFFICE STAFF',
            ],
            [
                'Department' => 'Recruiting',
                'Location_Desc' => 'Pennsylvania OH',
                'Division_Desc' => 'PA OFFICE STAFF',
            ],
            [
                'Department' => 'Recruiting',
                'Location_Desc' => 'Region I',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Recruiting',
                'Location_Desc' => 'Region I',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Recruiting',
                'Location_Desc' => 'Region II',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Recruiting',
                'Location_Desc' => 'Region II',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Recruiting',
                'Location_Desc' => 'Region III',
                'Division_Desc' => 'TX Hourly Office Staff',  
            ],
            [
                'Department' => 'Recruiting',
                'Location_Desc' => 'Region III',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Recruiting',
                'Location_Desc' => 'Region IV',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Recruiting',
                'Location_Desc' => 'Region IV',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Recruiting',
                'Location_Desc' => 'Region V',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Recruiting',
                'Location_Desc' => 'Region VI',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Recruiting',
                'Location_Desc' => 'Texas OH',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Regional Operations',
                'Location_Desc' => 'Colorado Springs',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'Regional Operations',
                'Location_Desc' => 'Denver',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'Regional Operations',
                'Location_Desc' => 'Fort Collins',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'Regional Operations',
                'Location_Desc' => 'Harrisburg',
                'Division_Desc' => 'PA OFFICE STAFF',
            ],
            [
                'Department' => 'Regional Operations',
                'Location_Desc' => 'Pittsburgh',
                'Division_Desc' => 'PA OFFICE STAFF',
            ],
            [
                'Department' => 'Regional Operations',
                'Location_Desc' => 'Region I',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Regional Operations',
                'Location_Desc' => 'Region II',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Regional Operations',
                'Location_Desc' => 'Region III',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Regional Operations',
                'Location_Desc' => 'Region IV',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Regional Operations',
                'Location_Desc' => 'Region V',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Regional Operations',
                'Location_Desc' => 'Region VI',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Regional Operations',
                'Location_Desc' => 'Texas OH',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Respiratory Therapy',
                'Location_Desc' => 'El Paso',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Respiratory Therapy',
                'Location_Desc' => 'Region I',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Respiratory Therapy',
                'Location_Desc' => 'Region II',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Respiratory Therapy',
                'Location_Desc' => 'Region III',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Respiratory Therapy',
                'Location_Desc' => 'Region IV',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Respiratory Therapy',
                'Location_Desc' => 'Region V',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Revenue Cycle',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Revenue Cycle',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'RN-Coverage',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'RN-Coverage',
                'Location_Desc' => 'El Paso',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'RN-Coverage',
                'Location_Desc' => 'Fort Worth',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'RN-Coverage',
                'Location_Desc' => 'Sherman',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'RN-Coverage',
                'Location_Desc' => 'Wichita Falls',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Abilene',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Allentown',
                'Division_Desc' => 'PA FIELD STAFF',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Amarillo',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Amarillo',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Austin',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Charlotte',
                'Division_Desc' => 'NC FIELD STAFF',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Colorado OH',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Colorado Springs',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Columbia',
                'Division_Desc' => 'NC FIELD STAFF',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Corpus Christi',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Denver',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Dubois',
                'Division_Desc' => 'PA FIELD STAFF',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Dubois',
                'Division_Desc' => 'PA OFFICE STAFF',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'El Paso',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Fort Collins',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Fort Collins',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Fort Worth',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Fort Worth',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Gainesville',
                'Division_Desc' => 'FL All Care Field Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Greensboro',
                'Division_Desc' => 'NC FIELD STAFF',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Harrisburg',
                'Division_Desc' => 'PA FIELD STAFF',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Harrisburg',
                'Division_Desc' => 'PA OFFICE STAFF',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Harrisburg',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Jacksonville',
                'Division_Desc' => 'FL All Care Field Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Lubbock',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Odessa',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Orlando',
                'Division_Desc' => 'FL All Care Field Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Phoenix',
                'Division_Desc' => 'PHOENIX FIELD STAFF',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Pittsburgh',
                'Division_Desc' => 'PA FIELD STAFF',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Pueblo',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Raleigh',
                'Division_Desc' => 'NC FIELD STAFF',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'San Antonio',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Sherman',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Tampa',
                'Division_Desc' => 'FL All Care Field Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Temple',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Tucson',
                'Division_Desc' => 'TUCSON FIELD STAFF',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Tyler',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Vero Beach FL',
                'Division_Desc' => 'Vero Beach Field Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Wichita Falls',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Wichita Falls',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Wilmington',
                'Division_Desc' => 'NC FIELD STAFF',
            ],
            [
                'Department' => 'RN-Regular',
                'Location_Desc' => 'Winston Salem',
                'Division_Desc' => 'NC FIELD STAFF',
            ],
            [
                'Department' => 'Skilled Nursing',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'Skilled Nursing',
                'Location_Desc' => 'San Antonio',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'Skilled Nursing',
                'Location_Desc' => 'Sherman',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'Skilled Nursing',
                'Location_Desc' => 'Tyler',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'ST',
                'Location_Desc' => 'Amarillo',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'ST',
                'Location_Desc' => 'Colorado Springs',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'ST',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'ST',
                'Location_Desc' => 'Denver',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'ST',
                'Location_Desc' => 'El Paso',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'ST',
                'Location_Desc' => 'Fort Collins',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'ST',
                'Location_Desc' => 'Fort Worth',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'ST',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'ST',
                'Location_Desc' => 'Lubbock',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'ST',
                'Location_Desc' => 'Odessa',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'ST',
                'Location_Desc' => 'Pueblo',
                'Division_Desc' => 'CO FIELD STAFF',
            ],
            [
                'Department' => 'ST',
                'Location_Desc' => 'San Antonio',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'ST',
                'Location_Desc' => 'Sherman',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'ST',
                'Location_Desc' => 'Tyler',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'STA',
                'Location_Desc' => 'Amarillo',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'STA',
                'Location_Desc' => 'Dallas',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'STA',
                'Location_Desc' => 'El Paso',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'STA',
                'Location_Desc' => 'Fort Worth',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'STA',
                'Location_Desc' => 'Houston',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'STA',
                'Location_Desc' => 'Lubbock',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'STA',
                'Location_Desc' => 'Odessa',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'STA',
                'Location_Desc' => 'San Antonio',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'STA',
                'Location_Desc' => 'Sherman',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'STA',
                'Location_Desc' => 'Tyler',
                'Division_Desc' => 'TX Hourly Field Staff',
            ],
            [
                'Department' => 'State Operations',
                'Location_Desc' => 'Arizona OH',
                'Division_Desc' => 'PHOENIX OFFICE STAFF',
            ],
            [
                'Department' => 'State Operations',
                'Location_Desc' => 'Colorado OH',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'State Operations',
                'Location_Desc' => 'Florida OH',
                'Division_Desc' => 'FL All Care Office Staff',
            ],
            [
                'Department' => 'State Operations',
                'Location_Desc' => 'North Carolina OH',
                'Division_Desc' => 'NC OFFICE STAFF',  
            ],
            [
                'Department' => 'State Operations',
                'Location_Desc' => 'Orange Park',
                'Division_Desc' => 'FL - ORANGE PARK OFFICE STAFF',
            ],
            [
                'Department' => 'State Operations',
                'Location_Desc' => 'Pennsylvania OH',
                'Division_Desc' => 'PA OFFICE STAFF',
            ],
            [
                'Department' => 'State Operations',
                'Location_Desc' => 'Texas OH',
                'Division_Desc' => 'Executives',
            ],
            [
                'Department' => 'State Operations',
                'Location_Desc' => 'Texas OH',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'State Operations',
                'Location_Desc' => 'Texas OH',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'State Operations - Therapy',
                'Location_Desc' => 'Colorado OH',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'State Operations - Therapy',
                'Location_Desc' => 'Region I',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'State Operations - Therapy',
                'Location_Desc' => 'Region II',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'State Operations - Therapy',
                'Location_Desc' => 'Region V',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'State Operations - Therapy',
                'Location_Desc' => 'Region VI',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'State Operations - Therapy',
                'Location_Desc' => 'Texas OH',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'State Operations - Therapy',
                'Location_Desc' => 'Texas OH',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'State Operations - Therapy',
                'Location_Desc' => 'Tyler',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'System Applications',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'System Applications',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Total Co. Operations',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'CO OFFICE STAFF',
            ],
            [
                'Department' => 'Total Co. Operations',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'Executives',
            ],
            [
                'Department' => 'Total Co. Operations',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Total Co. Operations',
                'Location_Desc' => 'Home Office',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
            [
                'Department' => 'Triage',
                'Location_Desc' => 'Texas OH',
                'Division_Desc' => 'TX Hourly Office Staff',
            ],
            [
                'Department' => 'Triage',
                'Location_Desc' => 'Texas OH',
                'Division_Desc' => 'TX Salary Office Staff',
            ],
        ];
        DB::table('paycom_department_crosswalk')->insert($data);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('paycom_department_crosswalk');
    }
}
