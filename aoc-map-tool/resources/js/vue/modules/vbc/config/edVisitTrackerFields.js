// Field metadata for the "ED Visit Record" modal (PD-3983).
//
// `HEADER_FIELDS` is re-exported as-is from hospitalizationTrackerFields.js (single source of truth,
// not a copy that could drift) — the header form is unchanged from Hospitalization's.
//
// `HOSPITALIZATION_INFO_FIELDS` below is the ED-specific field set for the "Hospitalization Info" tab
// (per the Figma screenshots' ED-specific frame, confirmed by stakeholder — not a copy of
// Hospitalization's Current Hospitalization Info section). Options reuse Hospitalization's existing
// option lists wherever the underlying classification is the same (ED Visit Type ~ Admission Type;
// Source is identical), per the backend field enum's storage mapping
// (HospitalizationTrackerFieldEnum::edVisitInfoFields()).
export {
    HEADER_FIELDS,
    LEVEL_OPTIONS,
    SOURCE_OPTIONS,
} from './hospitalizationTrackerFields.js'

import { SOURCE_OPTIONS } from './hospitalizationTrackerFields.js'

// Values are EncounterAdmissionTypeEnum's cases (same enum/column Hospitalization's Admission Type
// uses) — the screenshot's example value "Avoidable" matches this list exactly.
export const ED_VISIT_TYPE_OPTIONS = [
    { value: 'AVOIDABLE', label: 'Avoidable' },
    { value: 'UNAVOIDABLE', label: 'Unavoidable' },
    { value: 'PLANNED', label: 'Planned' },
]

// Values are VaccineQuestionResponseEnum's cases — shared by all three vaccine questions below.
export const VACCINE_RESPONSE_OPTIONS = [
    { value: 'YES', label: 'Yes' },
    { value: 'NO', label: 'No' },
    { value: 'UNABLE_TO_ASSESS', label: 'Unable to assess' },
]

// "Hospitalization Info" tab (AC9/AC10) — rows A-L, saved as a whole by one Submit button, same
// reasoning as the Hospitalization Record form's Current Hospitalization Info section.
export const HOSPITALIZATION_INFO_FIELDS = [
    {
        key: 'facility_name',
        label: 'Facility Name',
        row: 'A',
        type: 'text',
    },
    {
        key: 'ed_visit_type',
        label: 'ED Visit Type',
        row: 'A',
        type: 'select',
        options: ED_VISIT_TYPE_OPTIONS,
    },

    {
        key: 'source',
        label: 'Source',
        row: 'B',
        type: 'select',
        options: SOURCE_OPTIONS,
    },
    {
        key: 'ed_visit_occurred_at',
        label: 'Date and time of occurrence',
        shortLabel: 'Date and time of occurrence',
        row: 'B',
        type: 'datetime',
    },
    {
        key: 'ed_visit_report_filed_at',
        label: 'Date Report filed',
        row: 'B',
        type: 'date',
    },

    {
        key: 'ed_visit_reason',
        label: 'Reason for ED Visit: Description, effects, outcome, risk (Equipment, drug, procedure/tx)',
        shortLabel: 'Reason for ED Visit',
        row: 'C',
        type: 'textarea',
    },

    {
        key: 'sent_2067_form_to_payer',
        label: 'Was a 2067 Form sent to Payer?',
        row: 'D',
        type: 'toggle',
    },
    {
        key: 'ed_tt_contacted_prior_to_admit',
        label: 'Was Triage Team (TT) Contacted Prior to Admit?',
        row: 'E',
        type: 'toggle',
    },
    {
        key: 'did_tt_direct_ed_visit',
        label: 'Did TT Direct this ED Visit?',
        row: 'F',
        type: 'toggle',
    },
    {
        key: 'staffing_concerns',
        label: 'Staffing Concerns?',
        row: 'G',
        type: 'toggle',
    },
    {
        key: 'dme_concerns',
        label: 'DME Concerns?',
        row: 'H',
        type: 'toggle',
    },
    {
        key: 'pdn_on_shift_during_ed_visit',
        label: 'Was PDN on shift during ED visit?',
        row: 'I',
        type: 'toggle',
    },
    {
        key: 'incident_report_completed_in_kt',
        label: 'Incident report completed in KT?',
        row: 'J',
        type: 'toggle',
    },

    {
        key: 'staffing_percentage_during_week_of_ed_visit',
        label: 'Staffing % during the week of ED Visit',
        shortLabel: 'Staffing %',
        row: 'K',
        type: 'text',
        optional: true,
    },

    {
        key: 'annual_flu_vaccine_status',
        label: 'Annual flu vaccine?',
        row: 'L',
        type: 'radio',
        options: VACCINE_RESPONSE_OPTIONS,
        optional: true,
    },
    {
        key: 'flu_vaccination_date',
        label: 'Flu Vaccination Date',
        row: 'L',
        type: 'date',
        optional: true,
        // Only meaningful once its own question is answered "Yes" (matches the backend's required_if
        // rule in UpdateEdVisitInfoRequest).
        visibleWhen: (draft) => draft.annual_flu_vaccine_status === 'YES',
    },

    {
        key: 'covid19_vaccine_status',
        label: 'COVID-19 vaccine?',
        row: 'M',
        type: 'radio',
        options: VACCINE_RESPONSE_OPTIONS,
        optional: true,
    },
    {
        key: 'covid19_vaccination_date',
        label: 'COVID-19 Vaccination Date',
        row: 'M',
        type: 'date',
        optional: true,
        visibleWhen: (draft) => draft.covid19_vaccine_status === 'YES',
    },

    {
        key: 'rsv_vaccine_status',
        label: 'RSV vaccine?',
        row: 'N',
        type: 'radio',
        options: VACCINE_RESPONSE_OPTIONS,
        optional: true,
    },
    {
        key: 'rsv_vaccination_date',
        label: 'RSV Vaccination Date',
        row: 'N',
        type: 'date',
        optional: true,
        visibleWhen: (draft) => draft.rsv_vaccine_status === 'YES',
    },
]
