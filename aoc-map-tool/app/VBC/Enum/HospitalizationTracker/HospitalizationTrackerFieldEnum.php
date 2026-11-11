<?php

namespace App\VBC\Enum\HospitalizationTracker;

use App\VBC\Enum\Encounter\EncounterAdmissionTypeEnum;
use App\VBC\Enum\Encounter\EncounterHospitalizationTypeEnum;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Every editable field of the "Hospitalization Record" form, plus the ED Visit Record's own
 * "Hospitalization Info" tab (PD-3983): which fields exist, where each one is stored, how its value is
 * validated and cast, whether it is required, and whether changing it is recorded in the Activity Log.
 * Creation, the header form, the Current Hospitalization Info / ED Visit Info sections and the JSON
 * resource all resolve through here, so they can never disagree about a field.
 *
 * Both forms share this one enum (rather than a parallel ED-only enum) because every other piece of the
 * write/read machinery — HospitalizationTrackerFieldsDTO, HospitalizationWriteRepository::updateField(),
 * HospitalizationTrackerFieldValues, HospitalizationTrackerActivityLog — dispatches purely off this
 * enum's own per-case metadata (trackerColumn()/encounterColumn()/trackerPropertyKey()/etc.), with no
 * branching on which form a field belongs to. A second enum would mean forking all of that, not just the
 * field list. `currentInfoFields()` (Hospitalization) and `edVisitInfoFields()` (ED) are disjoint except
 * for the two fields both forms show (Facility Name, Source), so neither form's required-fields or JSON
 * payload gains the other's cases.
 *
 * The case values are the field names of the JSON contract the frontend sends and receives.
 *
 * Storage is one of four places, and exactly one applies per field: a column on hospitalization_trackers,
 * a column on the tracker's encounter, a client-profile custom property, or a tracker custom property
 * (both EAV). Read-only fields of the form (client details, tracker id, author, hospitalization status)
 * are not listed — they are derived in the resources and can never be written.
 *
 * No options()/list() here, unlike the other enums in this module: this is field metadata, not a set of
 * values a user picks from a dropdown. label() exists because validation messages and Activity Log
 * entries name the field to the user.
 */
enum HospitalizationTrackerFieldEnum: string
{
    // Header form.
    case ASSIGNED_TO_ID = 'assigned_to_id';
    case LEVEL = 'level';
    case HIGH_UTILIZER = 'high_utilizer';
    case BACKUP_G_TUBE = 'backup_g_tube';
    case BACKUP_TRACH = 'backup_trach';

    // Current Hospitalization Info.
    case FACILITY_NAME = 'facility_name';
    case HOSPITALIZATION_TYPE = 'hospitalization_type';
    case ADMISSION_AT = 'admission_at';
    case ADMISSION_TYPE = 'admission_type';
    case ADMISSION_REASON = 'admission_reason';
    case DECLINED_PROGRAM_CONTACT = 'declined_program_contact';
    case SOURCE = 'source';
    case TT_CONTACTED_PRIOR_TO_ADMIT = 'tt_contacted_prior_to_admit';
    case FAMILY_CONTACT_AT = 'family_contact_at';
    case CARE_REQUIREMENTS = 'aoc_care_requirements';
    case ROC_DATE = 'roc_date';
    case THIRTY_DAY_OUTCOME = 'thirty_day_outcome';
    case EXPECTED_DISCHARGE_AT = 'expected_discharge_at';
    case DISCHARGED_AT = 'discharged_at';
    case FIRST_BILLABLE_SHIFT_COMPLETED = 'first_billable_shift_completed';
    case FIRST_BILLABLE_SHIFT_AT = 'first_billable_shift_at';

    // ED Visit Info tab (PD-3983). FACILITY_NAME and SOURCE above are shared with Hospitalization's
    // Current Hospitalization Info and are not repeated here.
    case ED_VISIT_TYPE = 'ed_visit_type';
    case ED_VISIT_OCCURRED_AT = 'ed_visit_occurred_at';
    case ED_VISIT_REPORT_FILED_AT = 'ed_visit_report_filed_at';
    case ED_VISIT_REASON = 'ed_visit_reason';
    case SENT_2067_FORM_TO_PAYER = 'sent_2067_form_to_payer';
    case ED_TT_CONTACTED_PRIOR_TO_ADMIT = 'ed_tt_contacted_prior_to_admit';
    case DID_TT_DIRECT_ED_VISIT = 'did_tt_direct_ed_visit';
    case STAFFING_CONCERNS = 'staffing_concerns';
    case DME_CONCERNS = 'dme_concerns';
    case PDN_ON_SHIFT_DURING_ED_VISIT = 'pdn_on_shift_during_ed_visit';
    case INCIDENT_REPORT_COMPLETED_IN_KT = 'incident_report_completed_in_kt';
    case STAFFING_PERCENTAGE_DURING_WEEK_OF_ED_VISIT = 'staffing_percentage_during_week_of_ed_visit';
    case ANNUAL_FLU_VACCINE_STATUS = 'annual_flu_vaccine_status';
    case FLU_VACCINATION_DATE = 'flu_vaccination_date';
    case COVID19_VACCINE_STATUS = 'covid19_vaccine_status';
    case COVID19_VACCINATION_DATE = 'covid19_vaccination_date';
    case RSV_VACCINE_STATUS = 'rsv_vaccine_status';
    case RSV_VACCINATION_DATE = 'rsv_vaccination_date';

    /** The fields the header form owns — saved one at a time, as soon as they change. */
    public const HEADER_FIELDS = [
        self::ASSIGNED_TO_ID,
        self::LEVEL,
        self::HIGH_UTILIZER,
        self::BACKUP_G_TUBE,
        self::BACKUP_TRACH,
    ];

    /**
     * The fields unique to the ED Visit Info tab — excluded from `currentInfoFields()` so Hospitalization's
     * Current Hospitalization Info section/resource payload/required-fields never gain them. Facility
     * Name and Source are deliberately absent: both forms show them, so they must stay in
     * `currentInfoFields()` too.
     */
    private const ED_VISIT_ONLY_FIELDS = [
        self::ED_VISIT_TYPE,
        self::ED_VISIT_OCCURRED_AT,
        self::ED_VISIT_REPORT_FILED_AT,
        self::ED_VISIT_REASON,
        self::SENT_2067_FORM_TO_PAYER,
        self::ED_TT_CONTACTED_PRIOR_TO_ADMIT,
        self::DID_TT_DIRECT_ED_VISIT,
        self::STAFFING_CONCERNS,
        self::DME_CONCERNS,
        self::PDN_ON_SHIFT_DURING_ED_VISIT,
        self::INCIDENT_REPORT_COMPLETED_IN_KT,
        self::STAFFING_PERCENTAGE_DURING_WEEK_OF_ED_VISIT,
        self::ANNUAL_FLU_VACCINE_STATUS,
        self::FLU_VACCINATION_DATE,
        self::COVID19_VACCINE_STATUS,
        self::COVID19_VACCINATION_DATE,
        self::RSV_VACCINE_STATUS,
        self::RSV_VACCINATION_DATE,
    ];

    /** Every field the ED Visit Info tab shows, submitted as a whole the same way Current Hospitalization Info is. */
    public const ED_VISIT_INFO_FIELDS = [
        self::FACILITY_NAME,
        self::ED_VISIT_TYPE,
        self::SOURCE,
        self::ED_VISIT_OCCURRED_AT,
        self::ED_VISIT_REPORT_FILED_AT,
        self::ED_VISIT_REASON,
        self::SENT_2067_FORM_TO_PAYER,
        self::ED_TT_CONTACTED_PRIOR_TO_ADMIT,
        self::DID_TT_DIRECT_ED_VISIT,
        self::STAFFING_CONCERNS,
        self::DME_CONCERNS,
        self::PDN_ON_SHIFT_DURING_ED_VISIT,
        self::INCIDENT_REPORT_COMPLETED_IN_KT,
        self::STAFFING_PERCENTAGE_DURING_WEEK_OF_ED_VISIT,
        self::ANNUAL_FLU_VACCINE_STATUS,
        self::FLU_VACCINATION_DATE,
        self::COVID19_VACCINE_STATUS,
        self::COVID19_VACCINATION_DATE,
        self::RSV_VACCINE_STATUS,
        self::RSV_VACCINATION_DATE,
    ];

    /** How the field is named to the user — validation messages and Activity Log entries. */
    public function label(): string
    {
        return match ($this) {
            self::ASSIGNED_TO_ID => 'Assigned To',
            self::LEVEL => 'Level',
            self::HIGH_UTILIZER => 'High Utilizer?',
            self::BACKUP_G_TUBE => 'Backup G-tube?',
            self::BACKUP_TRACH => 'Backup trach?',
            self::FACILITY_NAME => 'Facility Name',
            self::HOSPITALIZATION_TYPE => 'Hospitalization Type',
            self::ADMISSION_AT => 'Admission Date/Time',
            self::ADMISSION_TYPE => 'Admission Type',
            self::ADMISSION_REASON => 'Admission Reason',
            self::DECLINED_PROGRAM_CONTACT => 'Declined Program Contact',
            self::SOURCE => 'Source',
            self::TT_CONTACTED_PRIOR_TO_ADMIT => 'Was Triage Team (TT) Contacted Prior to Admit?',
            self::FAMILY_CONTACT_AT => 'Family Contacted',
            self::CARE_REQUIREMENTS => 'Care Requirements',
            self::ROC_DATE => 'ROC Date',
            self::THIRTY_DAY_OUTCOME => '30 Days Outcome',
            self::EXPECTED_DISCHARGE_AT => 'Expected Discharge Date',
            self::DISCHARGED_AT => 'Discharge Date',
            self::FIRST_BILLABLE_SHIFT_COMPLETED => 'First billable shift completed within 48 hours following hospital discharge?',
            self::FIRST_BILLABLE_SHIFT_AT => 'First billable shift date',
            self::ED_VISIT_TYPE => 'ED Visit Type',
            self::ED_VISIT_OCCURRED_AT => 'Date and time of occurrence',
            self::ED_VISIT_REPORT_FILED_AT => 'Date Report filed',
            self::ED_VISIT_REASON => 'Reason for ED Visit: Description, effects, outcome, risk (Equipment, drug, procedure/tx)',
            self::SENT_2067_FORM_TO_PAYER => 'Was a 2067 Form sent to Payer?',
            self::ED_TT_CONTACTED_PRIOR_TO_ADMIT => 'Was Triage Team (TT) Contacted Prior to Admit?',
            self::DID_TT_DIRECT_ED_VISIT => 'Did TT Direct this ED Visit?',
            self::STAFFING_CONCERNS => 'Staffing Concerns?',
            self::DME_CONCERNS => 'DME Concerns?',
            self::PDN_ON_SHIFT_DURING_ED_VISIT => 'Was PDN on shift during ED visit?',
            self::INCIDENT_REPORT_COMPLETED_IN_KT => 'Incident report completed in KT?',
            self::STAFFING_PERCENTAGE_DURING_WEEK_OF_ED_VISIT => 'Staffing % during the week of ED Visit',
            self::ANNUAL_FLU_VACCINE_STATUS => 'Annual flu vaccine?',
            self::FLU_VACCINATION_DATE => 'Flu Vaccination Date',
            self::COVID19_VACCINE_STATUS => 'COVID-19 vaccine?',
            self::COVID19_VACCINATION_DATE => 'COVID-19 Vaccination Date',
            self::RSV_VACCINE_STATUS => 'RSV vaccine?',
            self::RSV_VACCINATION_DATE => 'RSV Vaccination Date',
        };
    }

    /** Column on hospitalization_trackers. */
    public function trackerColumn(): ?string
    {
        return match ($this) {
            self::ASSIGNED_TO_ID => 'assigned_to_id',
            self::LEVEL => 'icm_level',
            self::DECLINED_PROGRAM_CONTACT => 'declined_program_contact',
            self::SOURCE => 'source',
            self::CARE_REQUIREMENTS => 'aoc_care_requirements',
            default => null,
        };
    }

    /**
     * Column on the tracker's encounter — the hospitalisation/ED visit itself, not the ICM record of it.
     * ED_VISIT_TYPE/ED_VISIT_REASON/ED_VISIT_OCCURRED_AT intentionally share
     * `admission_type`/`admission_reason`/`admission_at` with ADMISSION_TYPE/ADMISSION_REASON/ADMISSION_AT:
     * a tracker's encounter is either IP or ED, so only one of each pair is ever shown or written for a
     * given tracker — same column, two form-specific labels.
     */
    public function encounterColumn(): ?string
    {
        return match ($this) {
            self::FACILITY_NAME => 'facility_name',
            self::HOSPITALIZATION_TYPE => 'hospitalization_type',
            self::ADMISSION_AT,
            self::ED_VISIT_OCCURRED_AT => 'admission_at',
            self::ADMISSION_TYPE,
            self::ED_VISIT_TYPE => 'admission_type',
            self::ADMISSION_REASON,
            self::ED_VISIT_REASON => 'admission_reason',
            self::EXPECTED_DISCHARGE_AT => 'expected_discharge_at',
            self::DISCHARGED_AT => 'discharged_at',
            default => null,
        };
    }

    /**
     * patient_custom_property_definitions.key. These live on the client profile (not the tracker), so
     * editing them updates the client for every tracker — intentional, the form presents them as
     * client-profile fields. Their "last updated" values are the property row's own updated_at.
     */
    public function patientPropertyKey(): ?string
    {
        return match ($this) {
            self::HIGH_UTILIZER => 'high_utilizer',
            self::BACKUP_G_TUBE => 'backup_g_tube',
            self::BACKUP_TRACH => 'backup_trach',
            default => null,
        };
    }

    /** hospitalization_tracker_custom_property_definitions.key — seeded, not created on the fly. */
    public function trackerPropertyKey(): ?string
    {
        return match ($this) {
            self::TT_CONTACTED_PRIOR_TO_ADMIT => 'was_tt_contacted_prior_to_admit_or_transfer',
            self::FAMILY_CONTACT_AT => 'family_contact_date_by_icm_or_tt',
            self::ROC_DATE => 'resumption_of_care_date',
            self::THIRTY_DAY_OUTCOME => 'thirty_day_outcome',
            self::FIRST_BILLABLE_SHIFT_COMPLETED => 'first_billable_shift_completed_within_48_hours',
            self::FIRST_BILLABLE_SHIFT_AT => 'first_billable_shift_after_discharge_date',
            self::ED_VISIT_REPORT_FILED_AT => 'incident_report_filed_at',
            self::SENT_2067_FORM_TO_PAYER => 'sent_2067_form_to_payer',
            self::ED_TT_CONTACTED_PRIOR_TO_ADMIT => 'was_tt_contacted_for_ed_visit',
            self::DID_TT_DIRECT_ED_VISIT => 'did_tt_direct_ed_visit',
            self::STAFFING_CONCERNS => 'staffing_concerns',
            self::DME_CONCERNS => 'dme_concerns',
            self::PDN_ON_SHIFT_DURING_ED_VISIT => 'was_pdn_on_shift_during_ed_visit',
            self::INCIDENT_REPORT_COMPLETED_IN_KT => 'incident_report_completed_in_kt',
            self::STAFFING_PERCENTAGE_DURING_WEEK_OF_ED_VISIT => 'staffing_percentage_during_week_of_ed_visit',
            self::ANNUAL_FLU_VACCINE_STATUS => 'annual_flu_vaccine_status',
            self::FLU_VACCINATION_DATE => 'flu_vaccination_date',
            self::COVID19_VACCINE_STATUS => 'covid19_vaccine_status',
            self::COVID19_VACCINATION_DATE => 'covid19_vaccination_date',
            self::RSV_VACCINE_STATUS => 'rsv_vaccine_status',
            self::RSV_VACCINATION_DATE => 'rsv_vaccination_date',
            default => null,
        };
    }

    /**
     * Validation rules for the field's value, without the presence rule — whether the field may be
     * omitted depends on the endpoint, so each request prepends its own (see isRequired()).
     *
     * @return array<int, mixed>
     */
    public function valueRules(): array
    {
        return match ($this) {
            self::ASSIGNED_TO_ID => ['integer', Rule::exists('users', 'id')],
            self::HIGH_UTILIZER,
            self::BACKUP_G_TUBE,
            self::BACKUP_TRACH,
            self::TT_CONTACTED_PRIOR_TO_ADMIT,
            self::FIRST_BILLABLE_SHIFT_COMPLETED,
            self::SENT_2067_FORM_TO_PAYER,
            self::ED_TT_CONTACTED_PRIOR_TO_ADMIT,
            self::DID_TT_DIRECT_ED_VISIT,
            self::STAFFING_CONCERNS,
            self::DME_CONCERNS,
            self::PDN_ON_SHIFT_DURING_ED_VISIT,
            self::INCIDENT_REPORT_COMPLETED_IN_KT => ['boolean'],
            self::FACILITY_NAME => ['string', 'max:255'],
            self::ADMISSION_REASON => ['string', 'max:500'],
            self::ED_VISIT_REASON => ['string', 'max:500'],
            self::STAFFING_PERCENTAGE_DURING_WEEK_OF_ED_VISIT => ['string', 'max:10'],
            self::LEVEL,
            self::HOSPITALIZATION_TYPE,
            self::ADMISSION_TYPE,
            self::DECLINED_PROGRAM_CONTACT,
            self::SOURCE,
            self::THIRTY_DAY_OUTCOME,
            self::ED_VISIT_TYPE,
            self::ANNUAL_FLU_VACCINE_STATUS,
            self::COVID19_VACCINE_STATUS,
            self::RSV_VACCINE_STATUS => ['string', Rule::enum($this->optionEnum())],
            self::CARE_REQUIREMENTS => ['array'],
            self::ADMISSION_AT,
            self::FAMILY_CONTACT_AT,
            self::ROC_DATE,
            self::EXPECTED_DISCHARGE_AT,
            self::DISCHARGED_AT,
            self::FIRST_BILLABLE_SHIFT_AT,
            self::ED_VISIT_OCCURRED_AT,
            self::ED_VISIT_REPORT_FILED_AT,
            self::FLU_VACCINATION_DATE,
            self::COVID19_VACCINATION_DATE,
            self::RSV_VACCINATION_DATE => ['date'],
        };
    }

    /** Rules for each element of a multi-value field; empty when the field holds a single value. */
    public function elementRules(): array
    {
        return match ($this) {
            self::CARE_REQUIREMENTS => ['string', Rule::enum(HospitalizationTrackerCareRequirementEnum::class)],
            default => [],
        };
    }

    /**
     * The enum that backs this field's values — the single source for both its validation rule and the
     * label the Activity Log prints instead of the stored UPPER_SNAKE value.
     *
     * @return class-string|null
     */
    private function optionEnum(): ?string
    {
        return match ($this) {
            self::LEVEL => HospitalizationTrackerIcmLevelEnum::class,
            self::HOSPITALIZATION_TYPE => EncounterHospitalizationTypeEnum::class,
            self::ADMISSION_TYPE => EncounterAdmissionTypeEnum::class,
            self::DECLINED_PROGRAM_CONTACT => HospitalizationTrackerDeclinedProgramContactEnum::class,
            self::SOURCE => HospitalizationTrackerSourceEnum::class,
            self::THIRTY_DAY_OUTCOME => HospitalizationTrackerThirtyDayOutcomeEnum::class,
            self::CARE_REQUIREMENTS => HospitalizationTrackerCareRequirementEnum::class,
            self::ED_VISIT_TYPE => EncounterAdmissionTypeEnum::class,
            self::ANNUAL_FLU_VACCINE_STATUS,
            self::COVID19_VACCINE_STATUS,
            self::RSV_VACCINE_STATUS => VaccineQuestionResponseEnum::class,
            default => null,
        };
    }

    /** The field's value as a person reads it — for Activity Log entries. */
    public function describeValue(mixed $value): string
    {
        if (is_array($value)) {
            return empty($value)
                ? '(empty)'
                : implode(', ', array_map(fn ($item) => $this->describeValue($item), $value));
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value === null || $value === '') {
            return '(empty)';
        }

        $optionEnum = $this->optionEnum();

        if (!empty($optionEnum)) {
            $case = $optionEnum::tryFrom((string) $value);

            return $case === null ? (string) $value : $case->label();
        }

        return (string) $value;
    }

    /**
     * What the field reads as when nothing has been stored for it yet. Only the toggles have one: false
     * is the answer "no", which is what their seeded EAV definitions default to as well — a toggle must
     * never surface as null, or the form would show it as unanswered rather than off.
     */
    public function defaultValue(): mixed
    {
        return match ($this) {
            self::HIGH_UTILIZER,
            self::BACKUP_G_TUBE,
            self::BACKUP_TRACH,
            self::TT_CONTACTED_PRIOR_TO_ADMIT,
            self::FIRST_BILLABLE_SHIFT_COMPLETED,
            self::SENT_2067_FORM_TO_PAYER,
            self::ED_TT_CONTACTED_PRIOR_TO_ADMIT,
            self::DID_TT_DIRECT_ED_VISIT,
            self::STAFFING_CONCERNS,
            self::DME_CONCERNS,
            self::PDN_ON_SHIFT_DURING_ED_VISIT,
            self::INCIDENT_REPORT_COMPLETED_IN_KT => false,
            default => null,
        };
    }

    /** Casts an already-validated value to the type it is stored as. */
    public function cast(mixed $value): mixed
    {
        return match ($this) {
            self::ASSIGNED_TO_ID => empty($value) ? null : (int) $value,
            self::HIGH_UTILIZER,
            self::BACKUP_G_TUBE,
            self::BACKUP_TRACH,
            self::TT_CONTACTED_PRIOR_TO_ADMIT,
            self::FIRST_BILLABLE_SHIFT_COMPLETED,
            self::SENT_2067_FORM_TO_PAYER,
            self::ED_TT_CONTACTED_PRIOR_TO_ADMIT,
            self::DID_TT_DIRECT_ED_VISIT,
            self::STAFFING_CONCERNS,
            self::DME_CONCERNS,
            self::PDN_ON_SHIFT_DURING_ED_VISIT,
            self::INCIDENT_REPORT_COMPLETED_IN_KT => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            self::CARE_REQUIREMENTS => array_values((array) $value),
            self::ADMISSION_AT,
            self::FAMILY_CONTACT_AT,
            self::ROC_DATE,
            self::EXPECTED_DISCHARGE_AT,
            self::DISCHARGED_AT,
            self::FIRST_BILLABLE_SHIFT_AT,
            self::ED_VISIT_OCCURRED_AT,
            self::ED_VISIT_REPORT_FILED_AT,
            self::FLU_VACCINATION_DATE,
            self::COVID19_VACCINATION_DATE,
            self::RSV_VACCINATION_DATE => empty($value) ? null : Carbon::parse($value),
            default => $value,
        };
    }

    /**
     * A tracker cannot exist without these, and they cannot be cleared afterwards either — this is the
     * set the header form and Move to In Progress both require. The toggles are excluded on purpose:
     * false is an answer, not a missing value. Declined Program Contact / Family Contacted / Care
     * Requirements / 30 Days Outcome / ROC Date stay optional here per product decision (Mya, via
     * Andrii/Maciej/Artem, 2026-09-07) — Family Contacted / Care Requirements / 30 Days Outcome become
     * required again later, but only to reach Completed (see `requiredForCompletedFields()`), not here.
     */
    public function isRequired(): bool
    {
        return match ($this) {
            self::HIGH_UTILIZER,
            self::BACKUP_G_TUBE,
            self::BACKUP_TRACH,
            self::TT_CONTACTED_PRIOR_TO_ADMIT,
            self::FIRST_BILLABLE_SHIFT_COMPLETED,
            self::ROC_DATE,
            self::DECLINED_PROGRAM_CONTACT,
            self::FAMILY_CONTACT_AT,
            self::CARE_REQUIREMENTS,
            self::THIRTY_DAY_OUTCOME,
            // ED Visit Info toggles — false is itself an answer, same reasoning as the toggles above.
            self::SENT_2067_FORM_TO_PAYER,
            self::ED_TT_CONTACTED_PRIOR_TO_ADMIT,
            self::DID_TT_DIRECT_ED_VISIT,
            self::STAFFING_CONCERNS,
            self::DME_CONCERNS,
            self::PDN_ON_SHIFT_DURING_ED_VISIT,
            self::INCIDENT_REPORT_COMPLETED_IN_KT,
            // Free-text/optional ED Visit Info fields: not explicitly mandated by the ticket.
            self::STAFFING_PERCENTAGE_DURING_WEEK_OF_ED_VISIT,
            // "Unable to assess" is itself a valid, non-committal answer for all three.
            self::ANNUAL_FLU_VACCINE_STATUS,
            self::COVID19_VACCINE_STATUS,
            self::RSV_VACCINE_STATUS,
            // Only relevant (and required) when its own vaccine question is answered "Yes" — see
            // UpdateEdVisitInfoRequest's required_if rule, not this flag.
            self::FLU_VACCINATION_DATE,
            self::COVID19_VACCINATION_DATE,
            self::RSV_VACCINATION_DATE => false,
            default => true,
        };
    }

    /**
     * Fields whose changes are written to the Activity Log, per the ticket's "Yes" column. Deliberately
     * not every field: the toggles the client profile owns (Backup G-tube/trach), the ones the system
     * derives, and ROC Date are not logged.
     */
    public function logsActivity(): bool
    {
        return match ($this) {
            self::HIGH_UTILIZER,
            self::HOSPITALIZATION_TYPE,
            self::ADMISSION_AT,
            self::ADMISSION_TYPE,
            self::ADMISSION_REASON,
            self::DECLINED_PROGRAM_CONTACT,
            self::SOURCE,
            // ED Visit Info (PD-5800) — ED-only cases, so logging them leaves Hospitalization untouched.
            self::ED_VISIT_TYPE,
            self::ED_VISIT_OCCURRED_AT,
            self::ED_VISIT_REPORT_FILED_AT,
            self::ED_VISIT_REASON,
            self::CARE_REQUIREMENTS,
            self::THIRTY_DAY_OUTCOME,
            self::EXPECTED_DISCHARGE_AT,
            self::DISCHARGED_AT,
            self::FIRST_BILLABLE_SHIFT_AT => true,
            default => false,
        };
    }

    /**
     * Fields whose changes are logged for an ED Visit tracker only (PD-5800). Facility Name is shared with
     * Hospitalization, where it is deliberately not logged, so it cannot go through `logsActivity()`.
     */
    public function logsActivityForEdVisit(): bool
    {
        return $this === self::FACILITY_NAME;
    }

    /** The name used in Activity Log entries and in "complete these fields" messages — the form's short label. */
    public function shortLabel(): string
    {
        return match ($this) {
            self::ED_VISIT_REASON => 'Reason for ED Visit',
            default => $this->label(),
        };
    }

    /**
     * The format the field is rendered in and accepted back in — flatpickr's, since UiDateTimepicker
     * drives these inputs ('m/d/Y' for date mode, config('vbc.date_time_format') for datetime — US
     * 12-hour clock, matching every other datetime the app already shows). Null for non-dated fields.
     */
    public function dateFormat(): ?string
    {
        return match ($this) {
            self::ADMISSION_AT,
            self::FAMILY_CONTACT_AT,
            self::ED_VISIT_OCCURRED_AT => config('vbc.date_time_format'),
            self::ROC_DATE,
            self::EXPECTED_DISCHARGE_AT,
            self::DISCHARGED_AT,
            self::FIRST_BILLABLE_SHIFT_AT,
            self::ED_VISIT_REPORT_FILED_AT,
            self::FLU_VACCINATION_DATE,
            self::COVID19_VACCINATION_DATE,
            self::RSV_VACCINATION_DATE => 'm/d/Y',
            default => null,
        };
    }

    /**
     * The header form's fields, which are the only ones creation accepts and the only ones editable one
     * at a time. Everything else belongs to the Current Hospitalization Info section, which is submitted
     * as a whole.
     *
     * @return array<int, string>
     */
    public static function headerFieldValues(): array
    {
        return array_column(self::HEADER_FIELDS, 'value');
    }

    /** @return array<int, self> */
    public static function currentInfoFields(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $case) => !in_array($case, self::HEADER_FIELDS, true)
                && !in_array($case, self::ED_VISIT_ONLY_FIELDS, true),
        ));
    }

    /**
     * The ED Visit Record's "Hospitalization Info" tab (PD-3983) — saved as a whole, same reasoning as
     * `currentInfoFields()`.
     *
     * @return array<int, self>
     */
    public static function edVisitInfoFields(): array
    {
        return self::ED_VISIT_INFO_FIELDS;
    }

    /**
     * Required Current Hospitalization Info fields the tracker must already have answered before it may
     * move to "In Progress" — isRequired()'s set, minus discharge-time fields, which can't be answered
     * yet at this point in the hospitalization.
     *
     * @return array<int, self>
     */
    public static function requiredForInProgressFields(): array
    {
        $dischargeOnly = [self::EXPECTED_DISCHARGE_AT, self::DISCHARGED_AT, self::FIRST_BILLABLE_SHIFT_AT];

        return array_values(array_filter(
            self::currentInfoFields(),
            fn (self $case) => $case->isRequired() && !in_array($case, $dischargeOnly, true),
        ));
    }

    /**
     * The Current Hospitalization Info fields the Transfer form asks for — the new hospitalization's, except
     * the discharge-time ones (it is not discharged yet) and Admission Reason: a transfer records its
     * Transfer Reason (the Outcome request's `notes`) in its place.
     *
     * @return array<int, self>
     */
    public static function transferFields(): array
    {
        $excluded = [self::ADMISSION_REASON, self::FIRST_BILLABLE_SHIFT_AT, ...self::dischargeFields()];

        return array_values(array_filter(
            self::currentInfoFields(),
            fn (self $case) => !in_array($case, $excluded, true),
        ));
    }

    /**
     * Required fields of the new hospitalization a Transfer opens: what "In Progress" requires, plus Declined
     * Program Contact — the new tracker is created already In Progress, with nothing filled in beforehand.
     * Admission Reason is not asked for (see transferFields()), so it is not required here either.
     *
     * @return array<int, self>
     */
    public static function requiredForTransferFields(): array
    {
        return array_values(array_filter(
            [...self::requiredForInProgressFields(), self::DECLINED_PROGRAM_CONTACT],
            fn (self $case) => $case !== self::ADMISSION_REASON,
        ));
    }

    /**
     * The discharge-time fields the Discharge outcome submits together with the transition — every field
     * the Discharge form shows, whether or not it is required (see `requiredForDischargeFields()`).
     * `FIRST_BILLABLE_SHIFT_AT` is excluded: it's conditionally required (only when the toggle is on),
     * which the request validates separately rather than through this list.
     *
     * @return array<int, self>
     */
    public static function dischargeFields(): array
    {
        return [self::EXPECTED_DISCHARGE_AT, self::DISCHARGED_AT, self::FIRST_BILLABLE_SHIFT_COMPLETED];
    }

    /**
     * Of the Discharge form's fields, the ones unconditionally required. Per the ticket, that's only the
     * Discharge Date itself — Expected Discharge Date stays optional, and the toggle is never "required"
     * (false is itself a valid answer, same as every other toggle in this enum).
     *
     * @return array<int, self>
     */
    public static function requiredForDischargeFields(): array
    {
        return [self::DISCHARGED_AT];
    }

    /**
     * Everything `requiredForInProgressFields()` requires, plus Family Contacted / Care Requirements /
     * 30 Days Outcome — optional up to that point, but required to reach Completed.
     *
     * @return array<int, self>
     */
    public static function requiredForCompletedFields(): array
    {
        return [
            ...self::requiredForInProgressFields(),
            self::FAMILY_CONTACT_AT,
            self::CARE_REQUIREMENTS,
            self::THIRTY_DAY_OUTCOME,
        ];
    }

    /**
     * The ED Visit Info fields that must be filled in before an ED tracker can change status (PD-5800).
     *
     * @return array<int, self>
     */
    public static function requiredForEdVisitTransitionFields(): array
    {
        return [
            self::FACILITY_NAME,
            self::ED_VISIT_TYPE,
            self::SOURCE,
            self::ED_VISIT_OCCURRED_AT,
            self::ED_VISIT_REPORT_FILED_AT,
            self::ED_VISIT_REASON,
        ];
    }
}
