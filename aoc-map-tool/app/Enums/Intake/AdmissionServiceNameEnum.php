<?php

namespace App\Enums\Intake;

/**
 * Admission service codes (service_codes_daas.name / mssql_schedulemaster.ServiceName).
 * values() is the full list used for service_codes_daas.is_admissions; the other methods return subsets.
 */
enum AdmissionServiceNameEnum: string
{
    // Nursing / aide admissions
    case CNA_ADMISSION = 'CNA Admission';
    case CNA_ADMISSION_ADD_LOB = 'CNA Admission (Add LOB)';
    case CNA_ADMISSION_FIELD = 'CNA Admission Field (Add LOB)';
    case CNA_ADMISSION_OASIS = 'CNA Admission Oasis';

    case IHSS_ADMISSION = 'IHSS Admission';

    case PDN_ADMISSION_ADD_LOB = 'PDN Admission (Add LOB)';
    case PDN_ADMISSION_BILLABLE = 'PDN Admission (Billable)';
    case PDN_ADMISSION_FIELD = 'PDN Admission Field (Add LOB)';

    case RN_ADMISSION_FIELD = 'RN Admission Field';
    case RN_ADMISSION_OASIS = 'RN Admission OASIS';
    case RN_ADMISSION_OASIS_FIELD = 'RN Admission OASIS Field';

    case SV_ADMISSION = 'SV Admission';
    case SV_ADMISSION_ADD_LOB = 'SV Admission (Add LOB)';
    case SV_ADMISSION_NONBILL = 'SV Admission (NONBILL)';
    case SV_ADMISSION_OFFICE_NONBILL = 'SV Admission (OFFICE NONBILL)';
    case SV_ADMISSION_OFFICE = 'SV Admission (OFFICE)';

    // Other admissions
    case RN_ADMISSION = 'RN ADMISSION'; // preferred admission visit for regular (non Colorado, non respite) clients
    case RA_MISCELLANEOUS = 'RA Miscellaneous';
    case RECERTIFICATION = 'Recertification';

    // Physical therapy
    case PT_INITIAL_EVAL_HIGH_COMPLEXITY = 'Physical Therapy Initial Eval - High Complexity';
    case PT_INITIAL_EVAL_LOW_COMPLEXITY = 'Physical Therapy Initial Eval - Low Complexity';
    case PT_INITIAL_EVAL_MOD_COMPLEXITY = 'Physical Therapy Initial Eval - Mod Complexity';
    case PT_INITIAL_EVALUATION = 'Physical Therapy Initial Evaluation';
    case TELE_PT_INITIAL_EVALUATION = 'Tele-Physical Therapy Initial Evaluation';
    case PT_INITIAL_EVAL_ACUTE = 'PT Initial Evaluation - Acute';
    case PT_INITIAL_EVAL_ACUTE_EI = 'PT Initial Evaluation - Acute EI';
    case PT_INITIAL_EVAL_LONG_TERM = 'PT Initial Evaluation - Long Term';
    case PT_INITIAL_EVAL_LONG_TERM_EI = 'PT Initial Evaluation - Long Term EI';
    case PT_SOC_INITIAL_EVAL_ACUTE = 'PT SOC Initial Evaluation - Acute';
    case PT_SOC_INITIAL_EVAL_ACUTE_OASIS = 'PT SOC Initial Evaluation - Acute - OASIS';
    case PT_SOC_INITIAL_EVAL_ACUTE_EI = 'PT SOC Initial Evaluation - Acute EI';
    case PT_SOC_INITIAL_EVAL_LONG_TERM = 'PT SOC Initial Evaluation - Long Term';
    case PT_SOC_INITIAL_EVAL_LONG_TERM_OASIS = 'PT SOC Initial Evaluation - Long Term - OASIS';
    case PT_SOC_INITIAL_EVAL_LONG_TERM_EI = 'PT SOC Initial Evaluation - Long Term EI';

    // Occupational therapy
    case OT_INITIAL_EVAL_LOW_COMPLEXITY = 'Occupational Therapy Initial Eval - Low Complexity';
    case OT_INITIAL_EVAL_MOD_COMPLEXITY = 'Occupational Therapy Initial Eval - Mod Complexity';
    case OT_INITIAL_EVAL_HIGH_COMPLEXITY = 'Occupational Therapy Initial Eval High Complexity';
    case OT_INITIAL_EVALUATION = 'Occupational Therapy Initial Evaluation';
    case TELE_OT_INITIAL_EVALUATION = 'Tele-Occupational Therapy Initial Evaluation';
    case OT_INITIAL_EVAL_ACUTE = 'OT Initial Evaluation - Acute';
    case OT_INITIAL_EVAL_ACUTE_EI = 'OT Initial Evaluation - Acute EI';
    case OT_INITIAL_EVAL_LONG_TERM = 'OT Initial Evaluation - Long Term';
    case OT_INITIAL_EVAL_LONG_TERM_EI = 'OT Initial Evaluation - Long Term EI';
    case OT_SOC_INITIAL_EVAL_ACUTE = 'OT SOC Initial Evaluation - Acute';
    case OT_SOC_INITIAL_EVAL_ACUTE_OASIS = 'OT SOC Initial Evaluation - Acute - OASIS';
    case OT_SOC_INITIAL_EVAL_ACUTE_EI = 'OT SOC Initial Evaluation - Acute EI';
    case OT_SOC_INITIAL_EVAL_LONG_TERM = 'OT SOC Initial Evaluation - Long Term';
    case OT_SOC_INITIAL_EVAL_LONG_TERM_OASIS = 'OT SOC Initial Evaluation - Long Term - OASIS';
    case OT_SOC_INITIAL_EVAL_LONG_TERM_EI = 'OT SOC Initial Evaluation - Long Term EI';

    // Speech therapy
    case ST_VOICE_RESONANCE_ANALYSIS = 'Behavioral & Qual Analys of Voice & Resonance';
    case ST_VOICE_RESONANCE_ANLYS = 'Behavioral & Qual Anlys of Voice & Resonance';
    case ST_SWALLOW_FUNCTION_EVAL = 'Eval of Oral & Pharyngeal Swallow Function';
    case ST_SPEECH_SOUND_PRODUCTION_LANGUAGE_EVAL = 'Eval of Speech Sound Production w/Language Comp & Expr';
    case ST_SPEECH_SOUND_PROD_LANGUAGE_EVAL = 'Eval of Speech Sound Prod w/Language Comp & Expr';
    case ST_SPEECH_FLUENCY_EVAL = 'Evaluation of Speech Fluency';
    case ST_SPEECH_SOUND_PRODUCTION_EVAL = 'Evaluation of Speech Sound Production';
    case TELE_ST_VOICE_RESONANCE_ANALYSIS = 'Tele-Behavioral & Qual Analys of Voice & Resonance';
    case TELE_ST_VOICE_RESONANCE_ANLYS = 'Tele-Behavioral & Qual Anlys of Voice & Resonance';
    case TELE_ST_SWALLOW_FUNCTION_EVAL = 'Tele-Eval of Oral & Pharyngeal Swallow Function';
    case TELE_ST_SPEECH_SOUND_PRODUCTION_LANGUAGE_EVAL = 'Tele-Eval of Speech Sound Production w/Language Comp & Expr';
    // Truncated name as it is stored in service_codes_daas
    case TELE_ST_SPEECH_SOUND_PROD_LANGUAGE_EVAL = 'Tele-Eval of Speech Sound Prod w/Language Comp &';
    case TELE_ST_SPEECH_FLUENCY_EVAL = 'Tele-Evaluation of Speech Fluency';
    case TELE_ST_SPEECH_SOUND_PRODUCTION_EVAL = 'Tele-Evaluation of Speech Sound Production';
    case ST_INITIAL_EVAL_ACUTE = 'ST Initial Evaluation - Acute';
    case ST_INITIAL_EVAL_ACUTE_EI = 'ST Initial Evaluation - Acute EI';
    case ST_INITIAL_EVAL_LONG_TERM = 'ST Initial Evaluation - Long Term';
    case ST_INITIAL_EVAL_LONG_TERM_EI = 'ST Initial Evaluation - Long Term EI';
    case ST_SOC_INITIAL_EVAL_ACUTE = 'ST SOC Initial Evaluation - Acute';
    case ST_SOC_INITIAL_EVAL_ACUTE_OASIS = 'ST SOC Initial Evaluation - Acute - OASIS';
    case ST_SOC_INITIAL_EVAL_ACUTE_EI = 'ST SOC Initial Evaluation - Acute EI';
    case ST_SOC_INITIAL_EVAL_LONG_TERM = 'ST SOC Initial Evaluation - Long Term';
    case ST_SOC_INITIAL_EVAL_LONG_TERM_OASIS = 'ST SOC Initial Evaluation - Long Term - OASIS';
    case ST_SOC_INITIAL_EVAL_LONG_TERM_EI = 'ST SOC Initial Evaluation - Long Term EI';

    /**
     * Admissions checked by the Intake bucket filter (IntakeCoordinatorStaffTransfers).
     */
    private const BUCKET_FILTER = [
        self::CNA_ADMISSION,
        self::CNA_ADMISSION_ADD_LOB,
        self::CNA_ADMISSION_FIELD,
        self::CNA_ADMISSION_OASIS,
        self::IHSS_ADMISSION,
        self::PDN_ADMISSION_ADD_LOB,
        self::PDN_ADMISSION_BILLABLE,
        self::PDN_ADMISSION_FIELD,
        self::RN_ADMISSION_FIELD,
        self::RN_ADMISSION_OASIS,
        self::RN_ADMISSION_OASIS_FIELD,
        self::SV_ADMISSION,
        self::SV_ADMISSION_ADD_LOB,
        self::SV_ADMISSION_NONBILL,
        self::SV_ADMISSION_OFFICE_NONBILL,
        self::SV_ADMISSION_OFFICE,
    ];

    /**
     * PT / OT / ST initial evaluations.
     */
    private const THERAPY = [
        self::PT_INITIAL_EVAL_HIGH_COMPLEXITY,
        self::PT_INITIAL_EVAL_LOW_COMPLEXITY,
        self::PT_INITIAL_EVAL_MOD_COMPLEXITY,
        self::PT_INITIAL_EVALUATION,
        self::TELE_PT_INITIAL_EVALUATION,
        self::PT_INITIAL_EVAL_ACUTE,
        self::PT_INITIAL_EVAL_ACUTE_EI,
        self::PT_INITIAL_EVAL_LONG_TERM,
        self::PT_INITIAL_EVAL_LONG_TERM_EI,
        self::PT_SOC_INITIAL_EVAL_ACUTE,
        self::PT_SOC_INITIAL_EVAL_ACUTE_OASIS,
        self::PT_SOC_INITIAL_EVAL_ACUTE_EI,
        self::PT_SOC_INITIAL_EVAL_LONG_TERM,
        self::PT_SOC_INITIAL_EVAL_LONG_TERM_OASIS,
        self::PT_SOC_INITIAL_EVAL_LONG_TERM_EI,
        self::OT_INITIAL_EVAL_LOW_COMPLEXITY,
        self::OT_INITIAL_EVAL_MOD_COMPLEXITY,
        self::OT_INITIAL_EVAL_HIGH_COMPLEXITY,
        self::OT_INITIAL_EVALUATION,
        self::TELE_OT_INITIAL_EVALUATION,
        self::OT_INITIAL_EVAL_ACUTE,
        self::OT_INITIAL_EVAL_ACUTE_EI,
        self::OT_INITIAL_EVAL_LONG_TERM,
        self::OT_INITIAL_EVAL_LONG_TERM_EI,
        self::OT_SOC_INITIAL_EVAL_ACUTE,
        self::OT_SOC_INITIAL_EVAL_ACUTE_OASIS,
        self::OT_SOC_INITIAL_EVAL_ACUTE_EI,
        self::OT_SOC_INITIAL_EVAL_LONG_TERM,
        self::OT_SOC_INITIAL_EVAL_LONG_TERM_OASIS,
        self::OT_SOC_INITIAL_EVAL_LONG_TERM_EI,
        self::ST_VOICE_RESONANCE_ANALYSIS,
        self::ST_VOICE_RESONANCE_ANLYS,
        self::ST_SWALLOW_FUNCTION_EVAL,
        self::ST_SPEECH_SOUND_PRODUCTION_LANGUAGE_EVAL,
        self::ST_SPEECH_SOUND_PROD_LANGUAGE_EVAL,
        self::ST_SPEECH_FLUENCY_EVAL,
        self::ST_SPEECH_SOUND_PRODUCTION_EVAL,
        self::TELE_ST_VOICE_RESONANCE_ANALYSIS,
        self::TELE_ST_VOICE_RESONANCE_ANLYS,
        self::TELE_ST_SWALLOW_FUNCTION_EVAL,
        self::TELE_ST_SPEECH_SOUND_PRODUCTION_LANGUAGE_EVAL,
        self::TELE_ST_SPEECH_SOUND_PROD_LANGUAGE_EVAL,
        self::TELE_ST_SPEECH_FLUENCY_EVAL,
        self::TELE_ST_SPEECH_SOUND_PRODUCTION_EVAL,
        self::ST_INITIAL_EVAL_ACUTE,
        self::ST_INITIAL_EVAL_ACUTE_EI,
        self::ST_INITIAL_EVAL_LONG_TERM,
        self::ST_INITIAL_EVAL_LONG_TERM_EI,
        self::ST_SOC_INITIAL_EVAL_ACUTE,
        self::ST_SOC_INITIAL_EVAL_ACUTE_OASIS,
        self::ST_SOC_INITIAL_EVAL_ACUTE_EI,
        self::ST_SOC_INITIAL_EVAL_LONG_TERM,
        self::ST_SOC_INITIAL_EVAL_LONG_TERM_OASIS,
        self::ST_SOC_INITIAL_EVAL_LONG_TERM_EI,
    ];

    /**
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array
     */
    public static function bucketFilterValues(): array
    {
        return array_column(self::BUCKET_FILTER, 'value');
    }

    /**
     * @return array
     */
    public static function therapyValues(): array
    {
        return array_column(self::THERAPY, 'value');
    }
}
