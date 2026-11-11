// Field metadata for the "Hospitalization Record" modal.
// Single source of truth for layout (row grouping/order), input type, dropdown
// options, and which Current Hospitalization Info fields require the
// floating Save/Cancel ("Log Updates?") flow instead of auto-save.

// Values are EncounterHospitalizationTypeEnum's cases; labels are its label().
export const HOSPITALIZATION_TYPE_OPTIONS = [
    { value: 'ADMISSION', label: 'Admission' },
    { value: 'READMISSION', label: 'Readmission' },
]

// Values are EncounterAdmissionTypeEnum's cases; labels are its label().
export const ADMISSION_TYPE_OPTIONS = [
    { value: 'AVOIDABLE', label: 'Avoidable' },
    { value: 'UNAVOIDABLE', label: 'Unavoidable' },
    { value: 'PLANNED', label: 'Planned' },
]

// Values are HospitalizationTrackerDeclinedProgramContactEnum's cases.
export const DECLINED_PROGRAM_CONTACT_OPTIONS = [
    { value: 'ICM_AND_TRIAGE_TEAM', label: 'ICM & Triage Team' },
    { value: 'ICM_TEAM', label: 'ICM Team' },
    { value: 'TRIAGE_TEAM', label: 'Triage Team' },
]

// Values are HospitalizationTrackerSourceEnum's cases.
export const SOURCE_OPTIONS = [
    { value: 'POINT_CLICK_CARE', label: 'PointClickCare' },
    { value: 'BAMBOO_HEALTH', label: 'Bamboo Health' },
    { value: 'INFORMED_BY_CONTACT', label: 'Informed by Contact' },
]

// Values are HospitalizationTrackerCareRequirementEnum's cases. Multi-select: the
// field is stored as a json array of these.
export const CARE_REQUIREMENTS_OPTIONS = [
    { value: 'GT_GB', label: 'GT/GB' },
    { value: 'NEBULIZER', label: 'Nebulizer' },
    { value: 'SUCTION', label: 'Suction' },
    { value: 'TRACH_AND_VENT', label: 'Trach & Vent' },
    { value: 'VENT', label: 'Vent' },
    { value: 'WOUND_CARE', label: 'Wound Care' },
    { value: 'ADLS', label: 'ADLs' },
    { value: 'MEDICATION_ADMINISTRATION', label: 'Medication Administration' },
]

// Values are HospitalizationTrackerThirtyDayOutcomeEnum's cases.
export const THIRTY_DAY_OUTCOME_OPTIONS = [
    {
        value: 'CLIENT_HOSPITALIZATION_WITHIN_30_DAYS',
        label: 'Client Hospitalization within 30 days',
    },
    {
        value: 'NO_HOSPITALIZATION_IN_PAST_30_DAYS',
        label: 'No hospitalization in past 30 days',
    },
]

// Mock assignee list, still used by the flows whose backends don't exist yet
// (Care Coordination, Follow-ups). The header form and the discharge-lifecycle
// outcome fields (Ready for Review/Ready for Next Steps/Completed) no longer
// use it — their "Assigned To" options come from GET
// /vbc/hospitalization/assignees, which returns the users actually holding
// the Intensive Case Management permission.
export const ASSIGNED_TO_OPTIONS = [
    { value: 'Anna Lopatnova', label: 'Anna Lopatnova' },
    { value: 'Maria Ivanova', label: 'Maria Ivanova' },
    { value: 'James Carter', label: 'James Carter' },
]

// Values are HospitalizationTrackerIcmLevelEnum's cases; labels are its label().
export const LEVEL_OPTIONS = [
    { value: 'LEVEL_I', label: 'Level I' },
    { value: 'LEVEL_II', label: 'Level II' },
    { value: 'LEVEL_III', label: 'Level III' },
    { value: 'LEVEL_I_III', label: 'Level I/III' },
    { value: 'LEVEL_II_III', label: 'Level II/III' },
]

// No backend enum exists for these today — illustrative mock lists only.
export const CLIENT_STATUS_KT_OPTIONS = [
    { value: 'Active', label: 'Active' },
    { value: 'Inactive', label: 'Inactive' },
    { value: 'Pending', label: 'Pending' },
]

export const DME_PROVIDER_OPTIONS = [
    { value: 'Apria Healthcare', label: 'Apria Healthcare' },
    { value: 'Lincare', label: 'Lincare' },
    { value: 'Rotech Healthcare', label: 'Rotech Healthcare' },
]

// Relationship options for the Contact Details modal now come from the backend
// (HospitalizationTrackerService.getContactTypes(), the `contact_types` table)
// instead of a hardcoded list — see ContactDetailsModal.vue.

// Diagnosis Code is no longer a static list (PD-3498) — it's a live ICD-10 typeahead against the NLM
// ClinicalTables API, see IcdCodeSearch.vue/HospitalizationTrackerService.searchDiagnosisCodes().

// The body system a diagnosis belongs to — required, highlighted red if missing (AC10/AC14). Backed by
// the real DiagnosisSystemEnum (app/VBC/Enum/Diagnosis) — BA-provided value set, not a local convention.
export const DIAGNOSIS_SYSTEM_OPTIONS = [
    { value: 'CARDIOVASCULAR', label: 'Cardiovascular' },
    { value: 'RESPIRATORY', label: 'Respiratory' },
    { value: 'GASTROINTESTINAL', label: 'Gastrointestinal' },
    { value: 'NERVOUS', label: 'Nervous' },
    { value: 'MUSCULOSKELETAL', label: 'Musculoskeletal' },
    { value: 'INTEGUMENTARY', label: 'Integumentary (Skin)' },
    { value: 'ENDOCRINE', label: 'Endocrine' },
    { value: 'RENAL_URINARY', label: 'Renal / Urinary' },
    { value: 'REPRODUCTIVE', label: 'Reproductive' },
    { value: 'HEMATOLOGIC', label: 'Hematologic' },
    { value: 'IMMUNE_LYMPHATIC', label: 'Immune / Lymphatic' },
    { value: 'VISUAL', label: 'Visual' },
    { value: 'AUDITORY_VESTIBULAR', label: 'Auditory / Vestibular' },
    { value: 'MULTISYSTEM', label: 'Multiple Systems / Systemic' },
    { value: 'OTHER', label: 'Other' },
    { value: 'UNSPECIFIED', label: 'Unspecified' },
]

// Backed by the real DiagnosisRankEnum/DiagnosisClinicalStatusEnum/DiagnosisVerificationStatusEnum
// (app/VBC/Enum/Diagnosis) — values are the enum's wire contract, not a local convention.
export const DIAGNOSIS_RANK_OPTIONS = [
    { value: 'PRIMARY', label: 'Primary' },
    { value: 'SECONDARY', label: 'Secondary' },
]

export const DIAGNOSIS_CLINICAL_STATUS_OPTIONS = [
    { value: 'ACTIVE', label: 'Active' },
    { value: 'INACTIVE', label: 'Inactive' },
]

export const DIAGNOSIS_VERIFICATION_STATUS_OPTIONS = [
    { value: 'UNCONFIRMED', label: 'Unconfirmed / Suspected' },
    { value: 'PROVISIONAL', label: 'Provisional' },
    { value: 'DIFFERENTIAL', label: 'Differential' },
    { value: 'CONFIRMED', label: 'Confirmed' },
    { value: 'REFUTED', label: 'Refuted' },
    { value: 'ENTERED_IN_ERROR', label: 'Entered in Error' },
]

// No spreadsheet was actually attached to the Sick Plan story — these six
// categories and their descriptions are read off the Figma reference
// (only "Hydration"'s description is legible there); the rest are
// illustrative placeholders pending the real source.
export const SICK_PLAN_TYPE_OPTIONS = [
    {
        value: 'Hydration',
        label: 'Hydration | Encourage increased fluid intake',
    },
    { value: 'Pain', label: 'Pain | Administer prescribed pain management' },
    { value: 'Seizure', label: 'Seizure | Follow seizure precaution protocol' },
    {
        value: 'Respiratory',
        label: 'Respiratory | Monitor respiratory status and support as needed',
    },
    { value: 'Allergy', label: 'Allergy | Monitor for allergic reaction' },
    { value: 'GI', label: 'GI | Monitor gastrointestinal symptoms' },
]

// Training Type / Education Source options are no longer mocked here — Education now calls the
// real backend (HospitalizationTrackerService.getTrainingTypes()/getTrainingSources()), the same way
// Diagnosis's dropdowns call the real API instead of a local list.

// Header info grid — rows 1-5, always rendered in this order/grouping.
// `editable: true` fields become interactive once a client is confirmed
// (no active-tracker conflict); everything else is a read-only display
// field once auto-filled, per the ticket's Figma reference.
// Header form. Keys are the backend's snake_case contract
// (HospitalizationTrackerResource) — do not rename without changing the API.
// Only `assigned_to_id`, `level` and the three client-profile toggles are
// writable; the rest mirror the client record and are display-only.
export const HEADER_FIELDS = [
    {
        key: 'tracker_id',
        grow: 1,
        label: 'Tracker ID',
        row: 1,
        type: 'text',
        editable: false,
    },
    {
        key: 'creation_date',
        grow: 1.6,
        label: 'Creation Date',
        row: 1,
        type: 'text',
        editable: false,
    },
    {
        key: 'author_name',
        grow: 2.6,
        label: 'Author',
        row: 1,
        type: 'text',
        editable: false,
    },
    {
        key: 'assigned_to_id',
        grow: 3.6,
        label: 'Assigned To',
        row: 1,
        type: 'select',
        // Populated from the store at render time — the eligible assignees are
        // whoever *currently* holds the Intensive Case Management permission,
        // which only the server can answer. A tracker assigned to someone who
        // since lost that permission (or was deactivated) won't have their id
        // in this list, so the disabled display falls back to `nameKey` below
        // rather than showing the raw id.
        optionsFrom: 'assignees',
        nameKey: 'assigned_to_name',
        editable: true,
        required: true,
    },

    {
        key: 'patient_name',
        grow: 3.4,
        label: 'Client Name',
        row: 2,
        type: 'clientSearch',
        editable: false,
    },
    {
        key: 'client_id',
        grow: 1.45,
        label: 'Client ID',
        row: 2,
        type: 'text',
        editable: false,
    },
    {
        key: 'date_of_birth',
        grow: 1.45,
        label: 'Date of Birth',
        row: 2,
        type: 'text',
        editable: false,
    },
    {
        key: 'age',
        label: 'Age',
        row: 2,
        grow: 0.85,
        type: 'text',
        editable: false,
    },
    {
        key: 'medicaid_id',
        grow: 1.4,
        label: 'Medicaid ID',
        row: 2,
        type: 'text',
        editable: false,
    },
    {
        key: 'client_status_kt',
        grow: 2.3,
        label: 'Client Status KT',
        row: 2,
        type: 'text',
        editable: false,
    },

    {
        key: 'terminal_payer',
        grow: 3.5,
        label: 'Terminal Payer',
        row: 3,
        type: 'text',
        editable: false,
    },
    {
        key: 'branch',
        label: 'Branch',
        row: 3,
        grow: 2,
        type: 'text',
        editable: false,
    },
    {
        key: 'auth_hours_per_week',
        grow: 2.35,
        label: 'Auth. hours per week',
        row: 3,
        type: 'text',
        editable: false,
    },
    {
        key: 'dme_provider',
        grow: 3.2,
        label: 'DME Provider',
        row: 3,
        type: 'text',
        editable: false,
    },

    {
        key: 'high_utilizer',
        label: 'High Utilizer?',
        row: 4,
        type: 'toggle',
        editable: true,
    },
    {
        key: 'backup_g_tube',
        label: 'Backup G-tube?',
        row: 4,
        type: 'toggle',
        editable: true,
    },
    {
        key: 'backup_trach',
        label: 'Backup trach?',
        row: 4,
        type: 'toggle',
        editable: true,
    },

    {
        key: 'level',
        grow: 2,
        label: 'Level',
        row: 5,
        type: 'select',
        options: LEVEL_OPTIONS,
        editable: true,
        required: true,
    },
    // Timestamps of the two toggles above, stamped by the backend when the
    // toggle changes — shown, never edited.
    {
        key: 'backup_g_tube_last_updated',
        grow: 3,
        label: 'Backup G-tube last updated',
        shortLabel: 'G-tube updated',
        row: 5,
        type: 'text',
        editable: false,
    },
    {
        key: 'backup_trach_last_updated',
        grow: 3,
        label: 'Backup trach last updated',
        shortLabel: 'Trach updated',
        row: 5,
        type: 'text',
        editable: false,
    },
]

// Current Hospitalization Info tab — rows A-F.
// Nothing here saves on its own: the section is validated as a whole and
// committed by its single Submit button, because the required rules can only be
// checked once the user is done. Every field is required except the ones marked
// `optional`; toggles are always answered (false is an answer). Mocked per
// design.md Decision 4 — not sourced from the real data dictionary.
export const CURRENT_INFO_FIELDS = [
    {
        key: 'facility_name',
        label: 'Facility Name',
        row: 'A',
        type: 'text',
    },
    {
        key: 'hospitalization_type',
        label: 'Hospitalization Type',
        row: 'A',
        type: 'select',
        options: HOSPITALIZATION_TYPE_OPTIONS,
    },
    {
        key: 'admission_at',
        label: 'Admission Date/Time',
        row: 'A',
        type: 'datetime',
    },

    {
        key: 'admission_type',
        label: 'Admission Type',
        row: 'B',
        type: 'select',
        options: ADMISSION_TYPE_OPTIONS,
    },
    {
        key: 'declined_program_contact',
        label: 'Declined Program Contact',
        row: 'B',
        type: 'select',
        options: DECLINED_PROGRAM_CONTACT_OPTIONS,
        optional: true,
    },
    {
        key: 'source',
        label: 'Source',
        row: 'B',
        type: 'select',
        options: SOURCE_OPTIONS,
    },

    {
        key: 'admission_reason',
        label: 'Admission Reason',
        row: 'C',
        type: 'textarea',
    },

    {
        key: 'tt_contacted_prior_to_admit',
        label: 'Was Triage Team (TT) Contacted Prior to Admit?',
        row: 'D',
        type: 'toggle',
    },

    {
        key: 'family_contact_at',
        label: 'Family contacted by ICM/TT following notification of Admit',
        shortLabel: 'Family Contacted',
        row: 'E',
        type: 'datetime',
        optional: true,
    },
    {
        key: 'roc_date',
        label: 'ROC Date',
        row: 'E',
        type: 'date',
        optional: true,
    },

    {
        key: 'aoc_care_requirements',
        label: 'Care Requirements',
        row: 'F',
        type: 'multiselect',
        options: CARE_REQUIREMENTS_OPTIONS,
        optional: true,
    },
    {
        key: 'thirty_day_outcome',
        label: '30 Days Outcome',
        row: 'F',
        type: 'select',
        options: THIRTY_DAY_OUTCOME_OPTIONS,
        optional: true,
    },

    // Entered once via the Discharge outcome form (DischargeFields.vue), then
    // remain here as ordinary fields of the section. `postDischargeOnly` hides
    // them until the tracker has passed Discharge (see
    // CurrentHospitalizationInfoTab.vue's filtering), and the backend only
    // demands them when the form actually submits them.
    {
        key: 'expected_discharge_at',
        label: 'Expected Discharge Date',
        row: 'G',
        type: 'date',
        postDischargeOnly: true,
    },
    {
        key: 'discharged_at',
        label: 'Discharge Date',
        row: 'G',
        type: 'date',
        postDischargeOnly: true,
    },
    {
        key: 'first_billable_shift_completed',
        label: 'First billable shift completed within 48 hours following hospital discharge?',
        row: 'H',
        type: 'toggle',
        postDischargeOnly: true,
    },
    {
        key: 'first_billable_shift_at',
        label: 'First billable shift date',
        row: 'H',
        type: 'date',
        postDischargeOnly: true,
        visibleIf: (tracker) => !!tracker?.first_billable_shift_completed,
    },
]
