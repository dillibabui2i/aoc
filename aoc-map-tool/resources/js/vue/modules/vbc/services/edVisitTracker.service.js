// Backend for the "ED Visit Record" modal (PD-3983).
//
// Client lookup and the header form's per-field save hit the same real `/vbc/hospitalization/*`
// endpoints the Hospitalization Record form uses — a tracker row is a tracker row regardless of which
// encounter type it hangs off. Creation and the "Hospitalization Info" tab's save each have their own
// endpoint instead: creation because that's the one place IP/"Open" vs ED/"In Progress" is decided (see
// CreateEdVisitTrackerRequest), and the Info tab because its field set is ED-specific, not a copy of
// Hospitalization's Current Hospitalization Info (see UpdateEdVisitInfoRequest /
// HospitalizationTrackerFieldEnum::edVisitInfoFields()).
//
// Field names below are the backend's snake_case contract — see HospitalizationTrackerResource.
import apiClient from '@/core/api/client.js'
import { route } from 'ziggy-js'

// Contact Details modal's form fields (camelCase) -> the contacts endpoints' wire contract. Contact Date
// and Contact Received By are stamped server-side, never sent.
function mapContactPayload(payload) {
    return {
        contact_type_id: payload.relationship,
        contact_name: payload.contactName,
        phone_number: payload.phoneNumber,
        email: payload.email,
        contact_notes: payload.contactNotes || null,
    }
}

export const EdVisitTrackerService = {
    searchClients(search) {
        return apiClient
            .get(route('hospitalization.clients.index'), { params: { search } })
            .then((r) => r.data.data)
    },

    getClient(patientId) {
        return apiClient
            .get(route('hospitalization.clients.show', { patientId }))
            .then((r) => ({ ...r.data.data, ...r.data.meta }))
    },

    // Users holding "Manage ED Visits" (not the ICM list the Hospitalization tracker uses).
    getAssignees() {
        return apiClient
            .get(route('hospitalization.assignees.index'), { params: { encounter_type: 'ED' } })
            .then((r) => r.data)
    },

    // The server assigns the id, saves the tracker as an ED encounter at status "In Progress" (AC7),
    // and records the author; the editable header fields filled in before submitting go with it.
    createTracker(payload) {
        return apiClient
            .post(route('hospitalization.ed-visits.trackers.store'), payload)
            .then((r) => r.data.data)
    },

    // encounterType=ED is required by the backend (PD-3983 follow-up) so this viewer can never be
    // handed an IP tracker by id.
    getTrackerById(trackerId) {
        return apiClient
            .get(route('hospitalization.trackers.show', { trackerId, encounterType: 'ED' }))
            .then((r) => r.data.data)
            .catch((e) => {
                if (e.response?.status === 404) return null
                throw e
            })
    },

    // One header field at a time — same endpoint/contract the Hospitalization Record form uses.
    saveField(trackerId, fieldKey, value) {
        return apiClient
            .patch(route('hospitalization.trackers.update', { trackerId }), {
                field: fieldKey,
                value,
            })
            .then((r) => r.data.data)
    },

    // The Hospitalization Info section, committed as a whole — its own endpoint/contract, distinct from
    // the Hospitalization Record form's Current Hospitalization Info tab (ED's field set differs).
    saveInfo(trackerId, values) {
        return apiClient
            .put(
                route('hospitalization.trackers.ed-info.update', {
                    trackerId,
                }),
                values,
            )
            .then((r) => r.data.data)
    },

    // ED Visit Outcome workflow (PD-4069): same real endpoint/DTO Hospitalization's lifecycle uses
    // (HospitalizationController::updateOutcome -> HospitalizationTrackerOutcomeService::transition()),
    // whose transition map/accepted closing reasons are now encounter-type-aware. Unlike
    // hospitalizationTracker.service.js's `updateOutcome`, `outcome`/`reason` here are already the
    // backend's own enum values (see EdOutcomeSubmitBar.vue's dropdown `option` values) — no UI-label ->
    // enum lookup table needed on this side.
    updateOutcome(trackerId, { outcome, reason, assigneeId, notes }) {
        const payload = { outcome }
        if (reason !== undefined) payload.reason = reason
        if (assigneeId !== undefined) payload.assignee_id = assigneeId
        if (notes !== undefined) payload.notes = notes

        return apiClient
            .patch(
                route('hospitalization.trackers.outcome.update', { trackerId }),
                payload,
            )
            .then((r) => r.data.data)
    },

    // ED Visit Follow-up outcome (PD-4073): `outcome` is the backend's own follow-up status value
    // (COMPLETED / FIRST_ATTEMPT / SECOND_ATTEMPT / THIRD_ATTEMPT); notes are always required.
    updateFollowUp(trackerId, { outcome, notes }) {
        return apiClient
            .patch(
                route('hospitalization.trackers.ed-follow-up.update', {
                    trackerId,
                }),
                { outcome, notes },
            )
            .then((r) => r.data.data)
    },

    // The tracker's Activity Log, newest first — same shared `notes` rows/endpoint the Hospitalization
    // Record form's NotesSidebar.vue reads (EdNotesSidebar.vue renders it).
    getTrackerNotes(trackerId) {
        return apiClient
            .get(route('hospitalization.trackers.notes.index', { trackerId }))
            .then((r) => r.data.data)
    },
    // PD-3972: "Create Notes" — same shared endpoint as Hospitalization; the response is the tracker's
    // full Activity Log, newest first, same shape as `getTrackerNotes`.
    createNote(trackerId, notes) {
        return apiClient
            .post(route('hospitalization.trackers.notes.store', { trackerId }), {
                notes,
            })
            .then((r) => r.data.data)
    },

    // Contacts tab (PD-3655): same endpoints/payload as the Hospitalization Contacts tab
    // (hospitalizationTracker.service.js), always scoped to encounter_type=ED so an IP tracker id is
    // rejected by the backend (404). The response is one contact in the resource's camelCase shape.
    getContactTypes() {
        return apiClient
            .get(route('hospitalization.contact-types.index'))
            .then((r) => r.data)
    },

    createContact(trackerId, payload) {
        return apiClient
            .post(route('hospitalization.trackers.contacts.store', { trackerId }), {
                ...mapContactPayload(payload),
                encounter_type: 'ED',
            })
            .then((r) => r.data.data)
    },

    updateContact(trackerId, contactId, payload) {
        return apiClient
            .patch(
                route('hospitalization.trackers.contacts.update', {
                    trackerId,
                    contactId,
                }),
                { ...mapContactPayload(payload), encounter_type: 'ED' },
            )
            .then((r) => r.data.data)
    },

    // Education tab (PD-3973): same endpoints/payload as the Hospitalization Education tab, always scoped
    // to encounter_type=ED so an IP tracker id is rejected by the backend (404). Records are returned
    // in the resource's snake_case shape and are read/sent back 1:1; the tracker's own list/toggle live
    // at `education_records` / `staff_family_training_required` on the tracker payload.
    getTrainingTypes() {
        return apiClient
            .get(route('hospitalization.training-types.index'))
            .then((r) => r.data)
    },

    getTrainingSources() {
        return apiClient
            .get(route('hospitalization.training-sources.index'))
            .then((r) => r.data)
    },

    createEducationRecord(trackerId, payload) {
        return apiClient
            .post(
                route('hospitalization.trackers.educations.store', { trackerId }),
                { ...payload, encounter_type: 'ED' },
            )
            .then((r) => r.data.data)
    },

    updateEducationRecord(trackerId, educationId, payload) {
        return apiClient
            .patch(
                route('hospitalization.trackers.educations.update', {
                    trackerId,
                    educationId,
                }),
                { ...payload, encounter_type: 'ED' },
            )
            .then((r) => r.data.data)
    },

    // Diagnosis (PD-3970): same shared hospitalization endpoints; `encounter_type: 'ED'` makes an IP
    // tracker id 404. ICD-10 code search is tracker-independent (IcdCodeSearch uses the IP service).
    createDiagnosis(trackerId, payload) {
        return apiClient
            .post(route('hospitalization.trackers.diagnoses.store', { trackerId }), {
                ...payload,
                encounter_type: 'ED',
            })
            .then((r) => r.data.data)
    },

    updateDiagnosis(trackerId, diagnosisId, payload) {
        return apiClient
            .patch(
                route('hospitalization.trackers.diagnoses.update', {
                    trackerId,
                    diagnosisId,
                }),
                { ...payload, encounter_type: 'ED' },
            )
            .then((r) => r.data.data)
    },

    // Dedicated endpoint (not saveField); returns the whole tracker.
    updateWasDiagnosisMade(trackerId, value) {
        return apiClient
            .patch(
                route('hospitalization.trackers.diagnosis-toggle.update', {
                    trackerId,
                }),
                { was_diagnosis_made: value, encounter_type: 'ED' },
            )
            .then((r) => r.data.data)
    },

    // Sick Plan (PD-3971): belongs to the patient and is resolved through the tracker, so the same
    // hospitalization endpoints serve ED trackers. A tracker without a plan yields null (404).
    getSickPlan(trackerId) {
        return apiClient
            .get(route('hospitalization.trackers.sick-plan.show', { trackerId }))
            .then((r) => r.data.data)
            .catch((e) => {
                if (e.response?.status === 404) return null
                throw e
            })
    },

    updateSickPlanItem(trackerId, itemId, payload) {
        return apiClient
            .patch(
                route('hospitalization.trackers.sick-plan.items.update', {
                    trackerId,
                    itemId,
                }),
                payload,
            )
            .then((r) => r.data.data)
    },

    // Dedicated endpoint (not saveField); returns the whole tracker.
    updateStaffFamilyTrainingRequired(trackerId, value) {
        return apiClient
            .patch(
                route('hospitalization.trackers.education-toggle.update', {
                    trackerId,
                }),
                { staff_family_training_required: value, encounter_type: 'ED' },
            )
            .then((r) => r.data.data)
    },
}

// PD-3972 AC4: new notes are blocked once an ED Visit is Completed, Closed or Cancelled. Exported so the
// Notes icon in the bucket and title bar share one rule.
export function isEdNotesEligible(status) {
    return !['Completed', 'Closed', 'Cancelled'].includes(status)
}
