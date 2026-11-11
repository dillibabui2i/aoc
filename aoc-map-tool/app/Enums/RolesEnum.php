<?php

namespace App\Enums;

enum RolesEnum: int
{
    case SUPER_ADMIN = 1;
    case MAP_ADMIN = 2;
    case MAP_USER = 3;
    case PRIMARY_ADMIN = 4;
    case IT_ADMIN = 5;
    case RECRUITER = 6;
    case REGIONAL_RECRUITING_LEAD = 7;
    case RECRUITING_MANAGER_TEAM_LEAD = 8;
    case RECRUITING_ADMIN = 10;
    case EXECUTIVE_TEAM = 11;
    case RECRUITING_DIRECTOR = 12;
    case DIRECTOR_OF_BRANCH_OPERATIONS = 13;
    case STATE_VP = 14;
    case ORIENTATION_COORDINATOR = 15;
    case CLIENT_CARE_COORDINATOR = 16;
    case DIRECTOR_OF_NURSING = 17;
    case AUTHORIZATION_CASE_MANAGER = 18;
    case CLINICAL_CARE_MANAGER = 19;
    case INTAKE_COORDINATOR = 20;
    case INTAKE_SUPERVISOR = 21;
    case INTEGRATION_CLINICAL_MANAGER = 22;
    case TRAINING_AND_INTEGRATION_SPECIALIST = 23;
    case PPEC_ADMINISTRATOR = 24;
    case REGIONAL_DIRECTOR_OF_OPERATIONS = 25;
    case CASE_MANAGER_COORDINATOR = 26;
    case CASE_MANAGER_COORDINATOR_DUPLICATE = 27;
    case BUSINESS_DEVELOPMENT_EXECUTIVE = 28;
    case BUSINESS_DEVELOPMENT_ASSOCIATE = 29;
    case THERAPY_RECRUITER = 30;
    case HR_GENERALIST = 31;
    case HR_MANAGER = 32;
    case DIRECTOR_OF_THERAPY = 33;
    case THERAPY_CLINICAL_MANAGER = 34; 
    case SOCIAL_MEDIA_MANAGER = 35;
    case TRIAGE_TEAM_DIRECTOR = 36;
    case THERAPY_CLIENT_CARE_COORDINATOR = 37;
    case TRIAGE_CLIENT_CARE_COORDINATOR = 38;
    case TRIAGE_CARE_MANAGER = 39;
    case AUTHORIZATION_CLINICAL_MANAGER = 40;
    case CLIENT_AND_EMPLOYEE_ADVOCACY_SUPERVISOR = 41;
    case MANAGER_OF_CLIENT_CARE = 42;
    case COVERAGE_COORDINATOR_ID = 44;
    case AUTHORIZATION_CLINICAL_SUPERVISOR = 45;
    case CLINICAL_QUALITY_NURSE = 46;
    case SUPERVISOR = 47;
    case RECRUITING_MANAGER = 48;
    case ADVOCACY = 49;
    case WIND_DOWN_NOTIFICATION = 50;
    case BILLING_SPECIALIST = 51;
    case RESPIRATORY_THERAPIST = 52;
    case REGIONAL_VP = 53;
    case QUALITY_ASSURANCE = 54;
    case BILLING_TEAM_LEAD = 55;
    case BILLING_MANAGER = 56;
    case DIRECTOR_OF_REVENUE_CYCLE_MANAGEMENT = 57;
    case VP_OF_REVENUE_CYCLE_MANAGEMENT = 58;
    case PAYMENT_POSTER = 59;
    case REGIONAL_DIRECTOR_OF_THERAPY = 60;
    case ELIGIBILITY_SPECIALIST = 61;

    public static function getRoleName(int $roleId): string
    {
        return match ($roleId) {
            self::SUPER_ADMIN->value => 'Super Admin',
            self::MAP_ADMIN->value => 'Map Admin',
            self::MAP_USER->value => 'Map User',
            self::PRIMARY_ADMIN->value => 'Primary Admin',
            self::IT_ADMIN->value => 'IT Admin',
            self::RECRUITER->value => 'Recruiter',
            self::REGIONAL_RECRUITING_LEAD->value => 'Regional Recruiting Lead',
            self::RECRUITING_MANAGER_TEAM_LEAD->value => 'Recruiting Manager/Team Lead',
            self::RECRUITING_ADMIN->value => 'Recruiting Admin',
            self::EXECUTIVE_TEAM->value => 'Executive Team',
            self::RECRUITING_DIRECTOR->value => 'Recruiting Director',
            self::DIRECTOR_OF_BRANCH_OPERATIONS->value => 'Director of Branch Operations',
            self::STATE_VP->value => 'State VP',
            self::ORIENTATION_COORDINATOR->value => 'Orientation Coordinator',
            self::CLIENT_CARE_COORDINATOR->value => 'Client Care Coordinator',
            self::DIRECTOR_OF_NURSING->value => 'Director of Nursing',
            self::AUTHORIZATION_CASE_MANAGER->value => 'Authorization Case Manager',
            self::CLINICAL_CARE_MANAGER->value => 'Clinical Care Manager',
            self::INTAKE_COORDINATOR->value => 'Intake Coordinator',
            self::INTAKE_SUPERVISOR->value => 'Intake Supervisor',
            self::INTEGRATION_CLINICAL_MANAGER->value => 'Integration Clinical Manager',
            self::TRAINING_AND_INTEGRATION_SPECIALIST->value => 'Training & Integration Specialist',
            self::PPEC_ADMINISTRATOR->value => 'PPEC Administrator',
            self::REGIONAL_DIRECTOR_OF_OPERATIONS->value => 'Regional Director of Operations',
            self::CASE_MANAGER_COORDINATOR->value => 'Case Manager Coordinator',
            self::CASE_MANAGER_COORDINATOR_DUPLICATE->value => 'Case Manager Coordinator',
            self::BUSINESS_DEVELOPMENT_EXECUTIVE->value => 'Business Development Executive',
            self::BUSINESS_DEVELOPMENT_ASSOCIATE->value => 'Business Development Associate',
            self::THERAPY_RECRUITER->value => 'Therapy Recruiter',
            self::HR_GENERALIST->value => 'HR Generalist',
            self::HR_MANAGER->value => 'HR Manager',
            self::DIRECTOR_OF_THERAPY->value => 'Director of Therapy',
            self::THERAPY_CLINICAL_MANAGER->value => 'Therapy Clinical Manager',
            self::SOCIAL_MEDIA_MANAGER->value => 'Social Media Manager',
            self::TRIAGE_TEAM_DIRECTOR->value => 'Triage Team Director',
            self::THERAPY_CLIENT_CARE_COORDINATOR->value => 'Therapy Client Care Coordinator',
            self::TRIAGE_CLIENT_CARE_COORDINATOR->value => 'Triage Client Care Coordinator',
            self::TRIAGE_CARE_MANAGER->value => 'Triage Care Manager',
            self::AUTHORIZATION_CLINICAL_MANAGER->value => 'Authorization Clinical Manager',
            self::CLIENT_AND_EMPLOYEE_ADVOCACY_SUPERVISOR->value => 'Client and Employee Advocacy Supervisor',
            self::MANAGER_OF_CLIENT_CARE->value => 'Manager of Client Care',
            self::COVERAGE_COORDINATOR_ID->value => 'Coverage Coordinator',
            self::AUTHORIZATION_CLINICAL_SUPERVISOR->value => 'Authorization Clinical Supervisor',
            self::CLINICAL_QUALITY_NURSE->value => 'Clinical Quality Nurse',
            self::SUPERVISOR->value => 'Supervisor',
            self::RECRUITING_MANAGER->value => 'Recruiting Manager',
            self::ADVOCACY->value => 'Advocacy',
            self::WIND_DOWN_NOTIFICATION->value => 'Wind Down Notification',
            self::BILLING_SPECIALIST->value => 'Billing Specialist',
            self::RESPIRATORY_THERAPIST->value => 'Respiratory Therapist',
            self::REGIONAL_VP->value => 'Regional VP',
            self::QUALITY_ASSURANCE->value => 'Quality Assurance',
            self::BILLING_TEAM_LEAD->value => 'Billing Team Lead',
            self::BILLING_MANAGER->value => 'Billing Manager',
            self::DIRECTOR_OF_REVENUE_CYCLE_MANAGEMENT->value => 'Director of Revenue Cycle Management',
            self::VP_OF_REVENUE_CYCLE_MANAGEMENT->value => 'VP of Revenue Cycle Management',
            self::PAYMENT_POSTER->value => 'Payment Poster',
            self::REGIONAL_DIRECTOR_OF_THERAPY->value => 'Regional Director of Therapy',
            self::ELIGIBILITY_SPECIALIST->value => 'Eligibility Specialist',
            default => '',
        };
    }
}
