// Backend for the "Hospitalization Record" modal.
//
// The header-form methods (`searchClients`, `getClient`, `getAssignees`,
// `createTracker`, `getTrackerById`, `saveField`), notes, the outcome
// transitions, Sick Plan, Contacts (PD-3693), Diagnosis (PD-3498) and
// Education (Training/Education records) call the real `/vbc/*` endpoints.
// Everything else — follow-ups — has no backend yet and stays mocked until
// the story that owns it lands (AOC-3472). That feature reads and writes
// `MOCK_TRACKERS_BY_ID`, so a tracker that came back from the real API is
// mirrored into it (see `mirrorTracker`) to keep them working; their
// changes live in memory only and are lost on reload.
//
// Field names below are the backend's snake_case contract, not a local
// convention — see HospitalizationTrackerResource on the PHP side. Sick
// Plan items are the one exception: they keep the camelCase shape their
// (pre-existing) components already read — see SickPlanItemResource on the
// PHP side.

// Named apart from the mock fixtures' local `client` variables further down.
import apiClient from '@/core/api/client.js'
import { route } from 'ziggy-js'

const MOCK_DELAY_MS = 300

// Exported so the store can attribute notes to "the current user" without
// duplicating this value.
export const CURRENT_USER = 'Anna Lopatnova'
const MOCK_CURRENT_USER = CURRENT_USER

// Contacts store phone numbers as bare 10 digits (see HospitalizationTrackerContactResource) — display
// formatting is a frontend concern, mirroring UiInput type="phone"'s own mask (US/+1 only, the sole
// country this module serves). Used for both the Contacts table and the read-only view; the editable
// field's mask handles itself once a value is typed, but a prefilled value still needs this to look
// right before the user touches it.
export function formatUsPhoneDisplay(digits) {
    if (!digits) return null

    const clean = digits.replace(/\D/g, '').slice(0, 10)
    if (clean.length < 10) return digits

    return `+1 (${clean.slice(0, 3)}) ${clean.slice(3, 6)}-${clean.slice(6)}`
}

// `reason` on the wire, as the real endpoint's UpdateHospitalizationTrackerOutcomeRequest accepts it —
// see HospitalizationTrackerClosingReasonEnum.
const REASON_TO_ENUM = {
    'ED Visit': 'ED_VISIT',
    'Opened by mistake': 'OPENED_BY_MISTAKE',
    Transfer: 'TRANSFERRED',
}

// Formats a Date as 'm/d/Y H:i' (24h clock) — matches UiDateTimepicker's
// `datetime` mode format string, used for `admissionDateTime` and friends.
function formatMockDateTime(date) {
    const pad = (n) => String(n).padStart(2, '0')
    return `${pad(date.getMonth() + 1)}/${pad(date.getDate())}/${date.getFullYear()} ${pad(date.getHours())}:${pad(date.getMinutes())}`
}

// Computed at module load (not hardcoded) so the AC4 admission-age banner
// is reliably >24h old whenever this fixture is opened, regardless of when.
const MOCK_ADMISSION_DATETIME = formatMockDateTime(
    new Date(Date.now() - 30 * 60 * 60 * 1000),
)

// Clients sharing the same full name are included on purpose, to exercise
// the ID-based disambiguation required by AC8.
const MOCK_CLIENTS = [
    {
        clientId: '12345-PDN',
        fullName: 'John Taylor',
        dob: '02/14/1958',
        age: '68',
        medicaidId: 'MCD-100234',
        clientStatusKT: 'Active',
        terminalPayer: 'Medicaid',
        branch: 'Dallas',
        authHoursPerWeek: '40',
        dmeProvider: 'Apria Healthcare',
        highUtilizer: false,
        backupGTube: true,
        backupTrach: true,
        backupGTubeLastUpdated: '01/15/2026 10:00 AM',
        backupTrachLastUpdated: '01/15/2026 10:00 AM',
        level: '',
    },
    {
        clientId: '99999-PDN',
        fullName: 'John Doe',
        dob: '07/22/1962',
        age: '63',
        medicaidId: 'MCD-100987',
        clientStatusKT: 'Active',
        terminalPayer: 'Medicare',
        branch: 'Houston',
        authHoursPerWeek: '35',
        dmeProvider: 'Lincare',
        highUtilizer: true,
        backupGTube: true,
        backupTrach: false,
        backupGTubeLastUpdated: '02/01/2026 08:30 AM',
        backupTrachLastUpdated: '',
        level: 'Level II',
    },
    {
        clientId: '91299-PDN',
        fullName: 'John Doe',
        dob: '11/03/1970',
        age: '55',
        medicaidId: 'MCD-100555',
        clientStatusKT: 'Pending',
        terminalPayer: 'Medicaid',
        branch: 'Austin',
        authHoursPerWeek: '20',
        dmeProvider: 'Rotech Healthcare',
        highUtilizer: false,
        backupGTube: false,
        backupTrach: false,
        backupGTubeLastUpdated: '',
        backupTrachLastUpdated: '',
        level: '',
    },
    {
        clientId: '8979-PDN',
        fullName: 'John Wayne',
        dob: '05/26/1980',
        age: '46',
        medicaidId: 'MCD-100112',
        clientStatusKT: 'Active',
        terminalPayer: 'Medicaid',
        branch: 'Dallas',
        authHoursPerWeek: '40',
        dmeProvider: 'Apria Healthcare',
        highUtilizer: false,
        backupGTube: true,
        backupTrach: true,
        backupGTubeLastUpdated: '03/10/2026 02:00 PM',
        backupTrachLastUpdated: '03/10/2026 02:00 PM',
        level: '',
    },
]

// In-memory store of created/loaded trackers, keyed by trackerId, so
// getTrackerById/saveField reflect prior calls within the same session.
const MOCK_TRACKERS_BY_ID = {
    '#10087': {
        trackerId: '#10087',
        status: 'Open',
        creationDate: '3/12/2026 9:14 AM',
        author: MOCK_CURRENT_USER,
        assignedTo: MOCK_CURRENT_USER,
        clientId: '99999-PDN',
        clientName: 'John Doe',
        level: 'Level II',
        ...MOCK_CLIENTS.find((c) => c.clientId === '99999-PDN'),
        notes: [],
        reason: null,
        wasInProgress: false,
        // Current Hospitalization Info demo data, so opening this fixture
        // exercises the admission-age banner (AC4) and shows a populated
        // tab, matching the Figma reference.
        facilityName: 'Child Care Hospital',
        hospitalizationType: 'Admission',
        admissionDateTime: MOCK_ADMISSION_DATETIME,
        admissionType: 'Avoidable',
        declinedProgramContact: 'ICM & Triage Team',
        source: 'PointClickCare',
        // Demo contacts, matching the Figma reference, so opening this
        // fixture's Contacts tab shows a populated table.
        contacts: [
            {
                id: 'contact-1',
                contactName: 'Pamela Hopkins',
                relationship: 'Step Mother',
                phoneNumber: '(555) 113-4212',
                email: 'not@for.you',
                contactDate: '3/30/2026 12:01 AM',
                contactReceivedBy: 'Melinda James',
                contactNotes: '',
            },
            {
                id: 'contact-2',
                contactName: 'Earl Jones',
                relationship: 'Father',
                phoneNumber: '(555) 123-4432',
                email: 'not@for.you',
                contactDate: '3/30/2026 12:01 AM',
                contactReceivedBy: 'Melinda James',
                contactNotes: '',
            },
            {
                id: 'contact-3',
                contactName: 'Amana Byrne',
                relationship: 'Relative',
                phoneNumber: '(555) 123-1256',
                email: 'not@for.you',
                contactDate: '3/30/2026 12:01 AM',
                contactReceivedBy: 'Melinda James',
                contactNotes: '',
            },
        ],
        // Diagnosis (PD-3498) and Education (PD-????) now call the real backend — see
        // createDiagnosis/updateDiagnosis/searchDiagnosisCodes/updateWasDiagnosisMade and
        // createEducationRecord/updateEducationRecord/updateStaffFamilyTrainingRequired below — so this
        // fixture no longer seeds either.
        // Demo follow-ups, matching the Figma reference, so opening this
        // fixture's Follow-up tab shows a populated table.
        followUps: [
            {
                id: 'followup-1',
                status: 'Scheduled',
                clientId: '99999-PDN',
                clientName: 'John Doe',
                facilityName: 'Child Care Hospital',
                admissionDateTime: MOCK_ADMISSION_DATETIME,
                level: 'Level II',
                followUpDate: '3/01/2026 12:00 AM',
                assignedTo: 'Axel Hawker',
                createdAt: Date.now() - 4 * 24 * 60 * 60 * 1000,
            },
            {
                id: 'followup-2',
                status: 'Completed',
                clientId: '99999-PDN',
                clientName: 'John Doe',
                facilityName: 'Child Care Hospital',
                admissionDateTime: MOCK_ADMISSION_DATETIME,
                level: 'Level II',
                followUpDate: '3/08/2026 12:00 AM',
                assignedTo: 'Axel Hawker',
                completionDate: '3/08/2026 12:05 AM',
                completedBy: 'Axel Hawker',
                statusNote: 'Lorem ipsum dolor sit amet, consectetur.',
                followUpsUpdates: 'Proin varius sed lectus vitae sagittis.',
                needs: 'Suspendisse auctor lobortis risus non luctus.',
                additionalNotes: '',
                createdAt: Date.now() - 3 * 24 * 60 * 60 * 1000,
            },
            {
                id: 'followup-3',
                status: 'Completed',
                clientId: '99999-PDN',
                clientName: 'John Doe',
                facilityName: 'Child Care Hospital',
                admissionDateTime: MOCK_ADMISSION_DATETIME,
                level: 'Level II',
                followUpDate: '3/15/2026 12:00 AM',
                assignedTo: 'Ben Chapman',
                completionDate: '3/15/2026 12:05 AM',
                completedBy: 'Ben Chapman',
                statusNote:
                    'Cras id libero vel nisi semper tempor id quis justo.',
                followUpsUpdates:
                    'Suspendisse lacinia volutpat ex vel fringilla.',
                needs: 'Vestibulum est odio, luctus et justo non efficitur.',
                additionalNotes: '',
                createdAt: Date.now() - 2 * 24 * 60 * 60 * 1000,
            },
            {
                id: 'followup-4',
                status: 'Attempted',
                clientId: '99999-PDN',
                clientName: 'John Doe',
                facilityName: 'Child Care Hospital',
                admissionDateTime: MOCK_ADMISSION_DATETIME,
                level: 'Level II',
                followUpDate: '3/20/2026 12:00 AM',
                assignedTo: 'Axel Hawker',
                attemptDate: '3/20/2026 12:05 AM',
                attemptedBy: 'Axel Hawker',
                attemptNotes: 'Morbi egestas ultrices odio quis molestie.',
                createdAt: Date.now() - 1 * 24 * 60 * 60 * 1000,
            },
        ],
    },
}


// Reconciles this service's mocked trackers with the otherwise-disconnected
// bucket mock dataset in `store/Dashboard/hospitalization.store.js`'s
// MOCK_DATA, purely so the bucket's Notes icon (design.md Decision 6) has a
// real tracker to attach notes to. A narrow, explicit list rather than an
// import of that store's data, to avoid coupling this service to a Pinia
// store.
const BUCKET_MOCK_TRACKERS = [
    { tracker_id: 12345, status: 'Open' },
    { tracker_id: 12412, status: 'Open' },
    { tracker_id: 15541, status: 'Open' },
    { tracker_id: 17656, status: 'Open' },
    { tracker_id: 76454, status: 'In Progress' },
    { tracker_id: 74342, status: 'In Progress' },
    { tracker_id: 24534, status: 'In Progress' },
    { tracker_id: 65654, status: 'In Progress' },
    { tracker_id: 23432, status: 'Discharged' },
    { tracker_id: 54643, status: 'Ready for Review' },
    { tracker_id: 23434, status: 'Ready for Next Steps' },
]

BUCKET_MOCK_TRACKERS.forEach(({ tracker_id: bucketTrackerId, status }) => {
    const trackerId = `#${bucketTrackerId}`
    MOCK_TRACKERS_BY_ID[trackerId] = { trackerId, status, notes: [] }
})

// "In Progress" tracker fixtures for the Care Coordination Activities story
// (design.md Decision 4 there): three `MOCK_CLIENTS` that previously had no
// tracker at all, so the new patient search has realistic data without
// touching `#10087`/`99999-PDN` (whose default status other stories'
// tests depend on). Each also carries a `level` and an admission age past
// the corresponding 30/60/90-day threshold, for the Hospitalization
// Follow-ups story's informational-message and Next-Due-default logic.
const ACTIVE_HOSPITALIZED_CLIENT_TRACKERS = [
    {
        trackerId: '#12346',
        clientId: '12345-PDN',
        facilityName: 'First Provider of Dallas',
        level: 'Level I/III',
        admissionDaysAgo: 35,
    },
    {
        trackerId: '#21001',
        clientId: '91299-PDN',
        facilityName: 'Child Care Hospital',
        level: 'Level II/III',
        admissionDaysAgo: 65,
    },
    {
        trackerId: '#44002',
        clientId: '8979-PDN',
        facilityName: 'Child Care Hospital',
        level: 'Level III',
        admissionDaysAgo: 95,
    },
]

ACTIVE_HOSPITALIZED_CLIENT_TRACKERS.forEach(
    ({ trackerId, clientId, facilityName, level, admissionDaysAgo }) => {
        const client = MOCK_CLIENTS.find((c) => c.clientId === clientId)
        MOCK_TRACKERS_BY_ID[trackerId] = {
            trackerId,
            status: 'In Progress',
            clientId,
            clientName: client.fullName,
            facilityName,
            level,
            admissionDateTime: formatMockDateTime(
                new Date(Date.now() - admissionDaysAgo * 24 * 60 * 60 * 1000),
            ),
            notes: [],
            followUps: [],
        }
    },
)

// Starts past the seeded fixture follow-ups (followup-1..4) to avoid id clashes.
let nextFollowUpSequence = 5

// Fields the still-mocked tabs expect to find on a tracker. A tracker fetched
// from the real API carries only the header form, so these defaults fill the gap
// until the stories that own them are implemented.
const MOCK_ONLY_TRACKER_DEFAULTS = {
    notes: [],
    reason: null,
    wasInProgress: false,
    contacts: [],
    followUps: [],
}

// Hospitalization Follow-ups (AC3-AC41): the backend's snake_case FollowupResource shape, mapped to
// the camelCase shape FollowUpModal.vue/FollowUpTab.vue/followUp.store.js already read. `additionalNotes`
// (Completed) and `attemptNotes` (Attempted) both come off the same `notes` column — a given follow-up
// only ever reaches one of those two terminal outcomes, so the duplication is harmless.
function mapFollowUp(raw) {
    if (!raw) return null

    return {
        id: raw.id,
        status: raw.status_label,
        trackerId: raw.hospitalization_tracker_id,
        clientId: raw.patient_account,
        clientName: raw.patient_name,
        facilityName: raw.facility_name,
        admissionDateTime: raw.admission_date_time,
        level: raw.level_label,
        followUpDate: raw.followup_date,
        assignedTo: raw.assigned_to_name,
        assignedToId: raw.assigned_to_id,
        completionDate: raw.completion_date,
        completedBy: raw.completed_by_name,
        attemptDate: raw.attempt_date,
        attemptedBy: raw.attempted_by_name,
        statusNote: raw.status_note,
        followUpsUpdates: raw.follow_ups_updates,
        needs: raw.needs,
        additionalNotes: raw.notes,
        attemptNotes: raw.notes,
        createdAt: raw.created_at,
    }
}

// One "Hospitalized Client Name" autocomplete suggestion (AC4/AC5).
function mapFollowUpClient(raw) {
    return {
        clientId: raw.patient_account,
        fullName: raw.patient_name,
        trackerId: raw.hospitalization_tracker_id,
        facilityName: raw.facility_name,
        admissionDateTime: raw.admission_date_time,
        level: raw.level_label,
    }
}

// Keeps the in-memory store used by the not-yet-implemented tabs in step with
// the real record, without letting a server response wipe unsaved mock state.
function mirrorTracker(tracker) {
    if (!tracker) return null

    const merged = {
        ...MOCK_ONLY_TRACKER_DEFAULTS,
        ...(MOCK_TRACKERS_BY_ID[tracker.tracker_id] ?? {}),
        ...tracker,
    }

    // Follow-ups (AC37-AC41) are real: the tracker's own `follow_ups` always wins over whatever mocked
    // `followUps` the merge above picked up, even when empty.
    if (tracker.follow_ups) {
        merged.followUps = tracker.follow_ups.map(mapFollowUp)
    }

    MOCK_TRACKERS_BY_ID[tracker.tracker_id] = merged

    return { ...merged }
}

function delay(value) {
    return new Promise((resolve) =>
        window.setTimeout(() => resolve(value), MOCK_DELAY_MS),
    )
}

// ContactDetailsModal's form fields (camelCase, matching the resource's read
// shape) mapped to StoreHospitalizationTrackerContactRequest's wire contract.
// `contactReceivedBy`/`contactDate` are deliberately left out: the backend
// always stamps both from the current user/time, never from client input
// (see the request classes).
function mapContactPayload(payload) {
    return {
        contact_type_id: payload.relationship,
        contact_name: payload.contactName,
        phone_number: payload.phoneNumber,
        email: payload.email,
        contact_notes: payload.contactNotes || null,
    }
}

// Folds a created/updated contact into the tracker's in-memory `contacts`
// list and returns the whole tracker, matching what `updateOutcome` etc.
// resolve to — `submitContact` assigns the result straight to `tracker.value`.
function mirrorContact(trackerId, contact) {
    const tracker = MOCK_TRACKERS_BY_ID[trackerId] ?? {
        ...MOCK_ONLY_TRACKER_DEFAULTS,
        tracker_id: trackerId,
    }
    const contacts = tracker.contacts ?? []
    const index = contacts.findIndex((c) => c.id === contact.id)
    tracker.contacts =
        index === -1
            ? [...contacts, contact]
            : contacts.map((c, i) => (i === index ? contact : c))

    MOCK_TRACKERS_BY_ID[trackerId] = tracker

    return { ...tracker }
}

// Same shape as mirrorContact, for Diagnosis (PD-3498). No field renaming here — the backend
// (HospitalizationTrackerDiagnosisResource) already returns the exact keys
// DiagnosisTab/DiagnosisDetailsModal read.
function mirrorDiagnosis(trackerId, diagnosis) {
    const tracker = MOCK_TRACKERS_BY_ID[trackerId] ?? {
        ...MOCK_ONLY_TRACKER_DEFAULTS,
        tracker_id: trackerId,
    }
    const diagnoses = tracker.diagnoses ?? []
    const index = diagnoses.findIndex((d) => d.id === diagnosis.id)
    tracker.diagnoses =
        index === -1
            ? [...diagnoses, diagnosis]
            : diagnoses.map((d, i) => (i === index ? diagnosis : d))

    MOCK_TRACKERS_BY_ID[trackerId] = tracker

    return { ...tracker }
}

// Same shape as mirrorContact/mirrorDiagnosis, for Education. The backend
// (HospitalizationTrackerTrainingResource) returns the record in the same snake_case shape
// EducationTab/EducationDetailsModal read and send back 1:1 (no payload mapping); the tracker's own list lives at `education_records`
// (snake_case, matching HospitalizationTrackerResource's top-level key — see EducationTab.vue).
function mirrorEducation(trackerId, record) {
    const tracker = MOCK_TRACKERS_BY_ID[trackerId] ?? {
        ...MOCK_ONLY_TRACKER_DEFAULTS,
        tracker_id: trackerId,
    }
    const records = tracker.education_records ?? []
    const index = records.findIndex((r) => r.id === record.id)
    tracker.education_records =
        index === -1
            ? [...records, record]
            : records.map((r, i) => (i === index ? record : r))

    MOCK_TRACKERS_BY_ID[trackerId] = tracker

    return { ...tracker }
}

// status !== 'Completed' && status !== 'Closed', per the ticket's business
// rule (AC10). Exported so the store/tests can reuse the exact same rule.
export function isActiveTracker(status) {
    return status !== 'Completed' && status !== 'Closed'
}

// status !== 'Closed' && status !== 'Completed' — the only two statuses
// this mocked lifecycle has that represent "done" (design.md Decision 5:
// "Cancelled" isn't a real status here yet). Exported so components can
// reuse the exact same rule for the Notes icon's visibility.
export function isNotesEligible(status) {
    return status !== 'Closed' && status !== 'Completed'
}

// `outcome` on the wire, as UpdateHospitalizationTrackerOutcomeRequest accepts it — see
// HospitalizationTrackerStatusEnum. The dropdown's label doesn't always match the target status's own
// label (the action is "Discharge", the status it produces is "Discharged"); the other three happen to
// match, but are listed explicitly rather than derived, since the wire value is a backend contract, not a
// transformation of the label.
const OUTCOME_TO_ENUM = {
    Discharge: 'DISCHARGED',
    'Ready for Review': 'READY_FOR_REVIEW',
    'Ready for Next Steps': 'READY_FOR_NEXT_STEPS',
    Completed: 'COMPLETED',
}

export const HospitalizationTrackerService = {
    // Autocomplete for the "Client Name" field.
    searchClients(query) {
        const search = (query ?? '').trim()
        if (!search) return Promise.resolve([])

        return apiClient
            .get(route('hospitalization.clients.index'), { params: { search } })
            .then((r) => r.data.data)
    },

    // Everything needed to pre-fill the form once a client is picked, plus
    // whether that client already has a tracker in flight.
    getClient(patientId) {
        return apiClient
            .get(route('hospitalization.clients.show', { patientId }))
            .then((r) => ({ ...r.data.data, ...r.data.meta }))
    },

    // Options for the "Assigned To" dropdown — users holding the Intensive Case
    // Management permission, resolved server-side.
    getAssignees() {
        return apiClient
            .get(route('hospitalization.assignees.index'))
            .then((r) => r.data)
    },

    // Options for the Contact Details modal's "Relationship" dropdown (PD-3693).
    getContactTypes() {
        return apiClient
            .get(route('hospitalization.contact-types.index'))
            .then((r) => r.data)
    },

    // Care Coordination Activities creation (AC5/AC6 there): only clients
    // with a tracker in status exactly 'In Progress' are eligible, per
    // design.md Decision 3 of that story (stricter than `isActiveTracker`).
    searchActiveHospitalizedClients(query) {
        const needle = (query ?? '').trim().toLowerCase()
        if (!needle) return delay([])

        const matches = Object.values(MOCK_TRACKERS_BY_ID).filter(
            (tracker) =>
                tracker.status === 'In Progress' &&
                tracker.clientName?.toLowerCase().includes(needle),
        )

        return delay(
            matches.map((tracker) => ({
                clientId: tracker.clientId,
                fullName: tracker.clientName,
                trackerId: tracker.trackerId,
                facilityName: tracker.facilityName,
                admissionDateTime: tracker.admissionDateTime,
            })),
        )
    },

    // Hospitalization Follow-ups creation (AC4/AC5): broader than
    // `searchActiveHospitalizedClients` above — any status other than
    // 'Closed' is eligible, not just 'In Progress'.
    searchHospitalizedClientsForFollowUp(query) {
        const search = (query ?? '').trim()
        if (!search) return Promise.resolve([])

        return apiClient
            .get(route('follow-up.clients.index'), { params: { search } })
            .then((r) => r.data.data.map(mapFollowUpClient))
    },

    // The server assigns the id, sets status to Open and records the author; the
    // editable header fields the user filled in before submitting are sent with
    // it so nothing entered on the form is lost.
    createTracker(payload) {
        return apiClient
            .post(route('hospitalization.trackers.store'), payload)
            .then((r) => mirrorTracker(r.data.data))
    },

    // encounterType=IP is required by the backend (PD-3983 follow-up) so this viewer can never be
    // handed an ED tracker by id.
    getTrackerById(trackerId) {
        return apiClient
            .get(route('hospitalization.trackers.show', { trackerId, encounterType: 'IP' }))
            .then((r) => mirrorTracker(r.data.data))
            .catch((e) => {
                if (e.response?.status === 404) return null
                throw e
            })
    },

    // One field at a time; the field key is named in the body, so every header
    // field shares this endpoint.
    saveField(trackerId, fieldKey, value) {
        return apiClient
            .patch(route('hospitalization.trackers.update', { trackerId }), {
                field: fieldKey,
                value,
            })
            .then((r) => mirrorTracker(r.data.data))
    },

    // The discharge-lifecycle outcomes (Discharge/Ready for Review/Ready for
    // Next Steps/Completed) — hits the same real endpoint as
    // `updateOutcomeStatus` below. `fields` carries the discharge-time
    // fields (Discharge only); `assigneeId`/`notes` carry the reviewer/next-
    // steps-owner/completed-by selection and free-text note (the other
    // three). The Activity Log entry is written server-side, same as
    // `updateOutcomeStatus`.
    updateOutcome(trackerId, { outcome, fields, assigneeId, notes }) {
        const payload = { outcome: OUTCOME_TO_ENUM[outcome] }
        if (fields) payload.fields = fields
        if (assigneeId !== undefined) payload.assignee_id = assigneeId
        if (notes !== undefined) payload.notes = notes

        return apiClient
            .patch(
                route('hospitalization.trackers.outcome.update', {
                    trackerId,
                }),
                payload,
            )
            .then((r) => mirrorTracker(r.data.data))
    },

    // Contact CRUD (AC7/AC10/AC11). Contacts are a list on the tracker, so
    // they get dedicated create/update-by-id methods rather than going
    // through `updateOutcome`'s scalar `fields` merge. The backend
    // (HospitalizationTrackerContactResource) already returns the contact in
    // the same camelCase shape ContactsTab/ContactDetailsModal read, so only
    // the outgoing payload needs mapping to the wire's snake_case contract.
    createContact(trackerId, payload) {
        return apiClient
            .post(
                route('hospitalization.trackers.contacts.store', { trackerId }),
                mapContactPayload(payload),
            )
            .then((r) => mirrorContact(trackerId, r.data.data))
    },

    updateContact(trackerId, contactId, payload) {
        return apiClient
            .patch(
                route('hospitalization.trackers.contacts.update', {
                    trackerId,
                    contactId,
                }),
                mapContactPayload(payload),
            )
            .then((r) => mirrorContact(trackerId, r.data.data))
    },

    // Diagnosis CRUD (AC9/AC11/AC12/AC15). Diagnoses are a list on the tracker, mirroring
    // `createContact`/`updateContact` exactly — field names already match the wire contract 1:1, so no
    // payload mapping is needed (see DiagnosisDetailsModal.vue).
    createDiagnosis(trackerId, payload) {
        return apiClient
            .post(
                route('hospitalization.trackers.diagnoses.store', { trackerId }),
                payload,
            )
            .then((r) => mirrorDiagnosis(trackerId, r.data.data))
    },

    updateDiagnosis(trackerId, diagnosisId, payload) {
        return apiClient
            .patch(
                route('hospitalization.trackers.diagnoses.update', {
                    trackerId,
                    diagnosisId,
                }),
                payload,
            )
            .then((r) => mirrorDiagnosis(trackerId, r.data.data))
    },

    // Typeahead for the "Diagnosis Code" field (PD-3498's technical spec) — proxies the NLM
    // ClinicalTables ICD-10-CM search API through our own backend.
    searchDiagnosisCodes(terms) {
        return apiClient
            .get(route('hospitalization.diagnosis-codes.search'), {
                params: { terms },
            })
            .then((r) => r.data)
    },

    // "Was the diagnosis made?" toggle (AC4/AC6) — a dedicated endpoint, not `saveField`, since it isn't
    // a header-form field. Returns the whole tracker, same as `saveField`/`updateOutcome`.
    updateWasDiagnosisMade(trackerId, value) {
        return apiClient
            .patch(
                route('hospitalization.trackers.diagnosis-toggle.update', {
                    trackerId,
                }),
                { was_diagnosis_made: value },
            )
            .then((r) => mirrorTracker(r.data.data))
    },

    // Options for the Training/Education modal's "Training Type" dropdown.
    getTrainingTypes() {
        return apiClient
            .get(route('hospitalization.training-types.index'))
            .then((r) => r.data)
    },

    // Options for the Training/Education modal's "Education Source" dropdown.
    getTrainingSources() {
        return apiClient
            .get(route('hospitalization.training-sources.index'))
            .then((r) => r.data)
    },

    // Training/Education record CRUD. Records are a list on the tracker, mirroring
    // `createContact`/`updateContact` exactly.
    createEducationRecord(trackerId, payload) {
        return apiClient
            .post(
                route('hospitalization.trackers.educations.store', { trackerId }),
                payload,
            )
            .then((r) => mirrorEducation(trackerId, r.data.data))
    },

    updateEducationRecord(trackerId, recordId, payload) {
        return apiClient
            .patch(
                route('hospitalization.trackers.educations.update', {
                    trackerId,
                    educationId: recordId,
                }),
                payload,
            )
            .then((r) => mirrorEducation(trackerId, r.data.data))
    },

    // "Staff / Family Training/Education required?" toggle — a dedicated endpoint, not `saveField`,
    // since it isn't a header-form field. Returns the whole tracker, same as `updateWasDiagnosisMade`.
    updateStaffFamilyTrainingRequired(trackerId, value) {
        return apiClient
            .patch(
                route('hospitalization.trackers.education-toggle.update', {
                    trackerId,
                }),
                { staff_family_training_required: value },
            )
            .then((r) => mirrorTracker(r.data.data))
    },

    // Follow-up create/outcome (AC6-AC19): the server derives every header field (client, facility,
    // admission date, level) from the tracker, and chains the next Scheduled follow-up itself when the
    // outcome payload's `next_follow_up_due` is set (AC13/AC17) — nothing else to send here beyond the
    // tracker id and, for outcome submissions, the outcome's own fields.
    createFollowUp(trackerId) {
        return apiClient
            .post(route('follow-up.store'), { hospitalization_tracker_id: trackerId })
            .then((r) => mapFollowUp(r.data.data))
    },

    updateFollowUpOutcome(followUpId, payload) {
        return apiClient
            .patch(route('follow-up.outcome.update', { followupId: followUpId }), payload)
            .then((r) => mapFollowUp(r.data.data))
    },

    // Sick Plan (AC3-AC12): the client's Sick Plan, resolved through the tracker. The blank plan
    // itself is created server-side when the tracker is first saved as "Open" (createTracker above),
    // and its items are fixed (one per catalog entry) — so there's nothing to create here, only
    // read/update-item.
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

    // User-created notes (AC5-AC10). Used both from an already-open tracker
    // and from the bucket's decoupled entry point (design.md Decision 1),
    // so it only needs a trackerId, not a loaded tracker. The server
    // attributes the note to the current user and timestamps it; the
    // response is the tracker's full Activity Log, newest first, same
    // shape as `getTrackerNotes`.
    createNote(trackerId, notes) {
        return apiClient
            .post(route('hospitalization.trackers.notes.store', { trackerId }), {
                notes,
            })
            .then((r) => r.data.data)
    },

    // Current Hospitalization Info, committed as a whole: its fields carry required
    // rules that only make sense checked together, so it has its own endpoint
    // rather than going through `saveField`. The Activity Log entries for the
    // fields the ticket marks as logged are written server-side.
    saveCurrentInfo(trackerId, values) {
        return apiClient
            .put(
                route('hospitalization.trackers.current-info.update', {
                    trackerId,
                }),
                values,
            )
            .then((r) => mirrorTracker(r.data.data))
    },

    // The tracker's Activity Log, newest first — the shared `notes` rows the
    // backend writes on every logged change.
    getTrackerNotes(trackerId) {
        return apiClient
            .get(route('hospitalization.trackers.notes.index', { trackerId }))
            .then((r) => r.data.data)
    },

    // The Open -> In Progress -> Closed transition. `updateOutcome` above hits the same real endpoint
    // for the rest of the lifecycle (Discharge onward); this one stays separate since its payload shape
    // (`reason` instead of `fields`/`assignee_id`/`notes`) doesn't fit the same call. The Activity Log
    // entry is written server-side, so the caller only needs to reload the notes panel afterwards.
    // A Transfer additionally carries the new hospitalization's Current Hospitalization Info (`fields`) and
    // the free-text reason (`notes`); the response is then the NEW tracker, not the one that was closed.
    updateOutcomeStatus(trackerId, { outcome, reason, fields, notes }) {
        const payload = {
            outcome: outcome === 'In Progress' ? 'IN_PROGRESS' : 'CLOSED',
            reason: REASON_TO_ENUM[reason],
        }
        if (fields) payload.fields = fields
        if (notes) payload.notes = notes

        return apiClient
            .patch(route('hospitalization.trackers.outcome.update', { trackerId }), payload)
            .then((r) => mirrorTracker(r.data.data))
    },

    // The hospitalizations this one was transferred from, nearest first — the "Hospitalization History" table.
    getTrackerHistory(trackerId) {
        return apiClient
            .get(route('hospitalization.trackers.history.index', { trackerId }))
            .then((r) => r.data.data)
    },
}
