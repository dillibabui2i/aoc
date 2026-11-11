import { defineStore } from 'pinia'
import { ref, computed, nextTick } from 'vue'
import { EdVisitTrackerService } from '@/modules/vbc/services/edVisitTracker.service.js'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

// Mirrors CLIENT_SEARCH_MIN_LENGTH in hospitalizationTracker.store.js — kept in sync with
// HospitalizationClientSearchDTO::MIN_SEARCH_LENGTH on the backend, which both forms share.
export const CLIENT_SEARCH_MIN_LENGTH = 3

// Header fields the create endpoint accepts alongside patient_id — mirrors
// HospitalizationTrackerFieldEnum::HEADER_FIELDS, same set the Hospitalization Record form submits.
const CREATABLE_FIELDS = [
    'assigned_to_id',
    'level',
    'high_utilizer',
    'backup_g_tube',
    'backup_trach',
]

// Follow-up tab (PD-4073): outcomes offered per current follow-up status. Values/labels mirror
// HospitalizationTrackerFollowupStatusEnum; the backend enforces the same transitions
// (HospitalizationTrackerFollowupStatusEnum::edVisitNextOutcomes()).
const FOLLOW_UP_COMPLETED = { value: 'COMPLETED', label: 'Completed' }
export const ED_FOLLOW_UP_OPTIONS = {
    NOT_STARTED: [
        FOLLOW_UP_COMPLETED,
        { value: 'FIRST_ATTEMPT', label: 'First Attempt' },
    ],
    FIRST_ATTEMPT: [
        FOLLOW_UP_COMPLETED,
        { value: 'SECOND_ATTEMPT', label: 'Second Attempt' },
    ],
    SECOND_ATTEMPT: [
        FOLLOW_UP_COMPLETED,
        { value: 'THIRD_ATTEMPT', label: 'Third Attempt' },
    ],
}

/**
 * "ED Visit Record" modal (PD-3983): client search -> active-tracker conflict check -> create -> the
 * created tracker's Hospitalization Info/History/Sick Plan/Activity Log tabs (AC9). A separate store
 * from `hospitalizationTracker.store.js`, per stakeholder direction — not a parametrization of it.
 *
 * "Open the active tracker" (AC16) is the one place this store hands off to the other one: the active
 * tracker blocking creation may be either kind (AC5/AC18 check for "any active tracker"), and an IP
 * tracker belongs in the Hospitalization viewer with its full tab set, not this one.
 */
export const useEdVisitTracker = defineStore('edVisitTracker', () => {
    const isModalOpen = ref(false)

    // Set once `submitTracker()` succeeds, so the bucket section can tell "just created a tracker,
    // then closed the modal" apart from "opened an existing tracker to view it" — mirrors
    // hospitalizationTracker.store.js's own `justCreated`.
    const justCreated = ref(false)

    // 'search' -> 'conflict' | 'confirmed' -> 'open'
    const wizardStep = ref('search')

    const clientSearchResults = ref([])
    const clientSearchLoading = ref(false)
    const clientSearchPerformed = ref(false)

    const assignees = ref([])

    const selectedClient = ref(null)
    const activeTrackerConflict = ref(null) // { tracker_id }
    const tracker = ref(null)

    // Per-field messages from a 422 — client-side validation catches empty required fields before this.
    const formErrors = ref({})

    // Outcome workflow (PD-4069): the tracker's Activity Log as the server has it, and the one
    // server-side error (illegal transition, 409/422 with no field of its own) `EdOutcomeSubmitBar.vue`
    // shows outside any single Fields component. Mirrors hospitalizationTracker.store.js's own pair.
    const trackerNotes = ref([])
    const outcomeError = ref(null)
    // PD-5800: keys of the Hospitalization Info fields a blocked transition reported as empty — the tab
    // highlights them and the modal switches to it. A fresh array per failure, so a repeat re-triggers.
    const outcomeMissingFields = ref([])
    const followUpError = ref(null)

    // Create Notes (PD-3972): `noteModalTrackerId` is tracked apart from `tracker` because the bucket's
    // Notes icon opens the modal without loading the full tracker.
    const noteModalOpen = ref(false)
    const noteModalTrackerId = ref(null)

    // Contacts tab (PD-3655): one modal handles create/edit/read-only; `editingContact` null => creating.
    const contactModalOpen = ref(false)
    const editingContact = ref(null)

    // Education tab (PD-3973): records and the toggle live on the tracker payload
    // (`education_records`, `staff_family_training_required`).
    const educationModalOpen = ref(false)
    const editingEducationRecord = ref(null) // null => creating

    // Diagnosis tab (PD-3970): records and the toggle live on the tracker payload
    // (`diagnoses`, `was_diagnosis_made`).
    const diagnosisModalOpen = ref(false)
    const editingDiagnosis = ref(null) // null => creating

    // Sick Plan tab (PD-3971): items are fixed (one per catalog entry), so the modal only ever edits.
    const sickPlanModalOpen = ref(false)
    const editingSickPlanItem = ref(null)

    // Unsaved work that lives in a component's local draft rather than in the store: the Current
    // Info section (committed with its own Submit) and a picked-but-not-submitted outcome. The
    // components report it through setUnsaved(); the header fields are not here, they auto-save.
    const unsavedSources = ref({})
    const hasUnsavedChanges = computed(() =>
        Object.values(unsavedSources.value).some(Boolean),
    )
    const clientProfileConfirmOpen = ref(false)

    function setUnsaved(source, dirty) {
        if (!!unsavedSources.value[source] === dirty) return
        unsavedSources.value = { ...unsavedSources.value, [source]: dirty }
    }

    function reset() {
        sickPlanModalOpen.value = false
        editingSickPlanItem.value = null
        educationModalOpen.value = false
        editingEducationRecord.value = null
        diagnosisModalOpen.value = false
        editingDiagnosis.value = null
        noteModalOpen.value = false
        noteModalTrackerId.value = null
        contactModalOpen.value = false
        editingContact.value = null
        justCreated.value = false
        wizardStep.value = 'search'
        clientSearchResults.value = []
        clientSearchPerformed.value = false
        selectedClient.value = null
        activeTrackerConflict.value = null
        tracker.value = null
        formErrors.value = {}
        trackerNotes.value = []
        outcomeError.value = null
        outcomeMissingFields.value = []
        followUpError.value = null
        unsavedSources.value = {}
        clientProfileConfirmOpen.value = false
    }

    function openCreateModal() {
        reset()
        isModalOpen.value = true
        loadAssignees()
    }

    function closeModal() {
        isModalOpen.value = false
    }

    // Title-bar "Client Profile" icon. The legacy profile is a Bootstrap modal whose global
    // handlers rewrite every `.modal-backdrop`, which breaks a Vue modal still open underneath
    // — so the tracker is closed first, and the user is asked first when that would lose work.
    async function openClientProfile() {
        if (!tracker.value?.patient_id) return

        if (hasUnsavedChanges.value) {
            clientProfileConfirmOpen.value = true

            return
        }

        await closeAndOpenClientProfile()
    }

    function cancelClientProfile() {
        clientProfileConfirmOpen.value = false
    }

    async function confirmClientProfile() {
        clientProfileConfirmOpen.value = false
        await closeAndOpenClientProfile()
    }

    async function closeAndOpenClientProfile() {
        const patientId = tracker.value?.patient_id

        closeModal()
        // The tracker's UiModal (v-if) has to be unmounted before the legacy modal opens.
        await nextTick()
        window.getClientDetails?.(patientId)
    }

    async function searchClients(query) {
        const term = query?.trim() ?? ''

        if (term.length < CLIENT_SEARCH_MIN_LENGTH) {
            clientSearchResults.value = []
            clientSearchPerformed.value = false

            return
        }

        clientSearchLoading.value = true
        try {
            clientSearchResults.value =
                await EdVisitTrackerService.searchClients(term)
            clientSearchPerformed.value = true
        } finally {
            clientSearchLoading.value = false
        }
    }

    async function selectClient(client) {
        clientSearchResults.value = []
        clientSearchPerformed.value = false

        const profile = await EdVisitTrackerService.getClient(client.patient_id)
        selectedClient.value = profile

        if (profile.has_active_tracker) {
            activeTrackerConflict.value = {
                tracker_id: profile.active_tracker_id,
                encounter_type: profile.active_tracker_encounter_type,
            }
            wizardStep.value = 'conflict'
        } else {
            activeTrackerConflict.value = null
            wizardStep.value = 'confirmed'
        }
    }

    async function loadAssignees() {
        assignees.value = await EdVisitTrackerService.getAssignees()
    }

    function searchAnotherClient() {
        clientSearchResults.value = []
        clientSearchPerformed.value = false
        selectedClient.value = null
        activeTrackerConflict.value = null
        wizardStep.value = 'search'
    }

    // Fetches a tracker by id and, if it's an ED encounter, loads it into this module's own viewer
    // state (tracker/wizardStep/isModalOpen) and clears any pending conflict. Returns the fetched
    // tracker either way, so callers decide what a non-ED result means for them. Shared by
    // `openExistingTracker` (AC16, where a non-ED result is an expected IP tracker) and
    // `loadTrackerById` (bucket calendar icon, where it would only mean stale list data).
    async function fetchAndOpenIfEd(trackerId) {
        const existing = await EdVisitTrackerService.getTrackerById(trackerId)

        if (existing?.encounter_type === 'ED') {
            tracker.value = existing
            activeTrackerConflict.value = null
            wizardStep.value = 'open'
            isModalOpen.value = true
            // AC15: the notes panel, outcome status, and assignment all come back on the tracker/notes
            // response itself — reloading them here is what makes a refresh show the saved state rather
            // than whatever the modal last rendered before it was closed.
            // PD-5636: the Assigned To / Follow-up / Outcome pickers read `assignees`, which only the create
            // flow loaded — so a reloaded page opened an existing tracker with empty options. A failed
            // request leaves the list empty rather than blocking the open.
            await Promise.all([
                loadTrackerNotes(),
                loadAssignees().catch(() => {}),
            ])
        }

        return existing
    }

    // AC16: hands off to whichever viewer fits the tracker that's actually active — this module's own
    // "open" step for an ED tracker, or the shared Hospitalization viewer for an IP one.
    async function openExistingTracker() {
        const trackerId = activeTrackerConflict.value?.tracker_id
        if (!trackerId) return

        const existing = await fetchAndOpenIfEd(trackerId)

        if (existing?.encounter_type !== 'ED') {
            closeModal()
            await useHospitalizationTracker().loadTrackerById(trackerId)
        }
    }

    // ED Visits bucket's calendar icon (PD-3983 bug fix). The bucket's own backend query already
    // filters to ED trackers only (HospitalizationReadRepository::getEdVisitsBucketPaginated()), but
    // `hospitalization.trackers.show` is the shared single-tracker-by-id endpoint both this module and
    // the Hospitalization viewer use — a single id already identifies exactly one row, so it isn't
    // type-scoped there (a non-ED result here would only mean this bucket's list is stale, not a real
    // ambiguity for the backend to resolve). Guard defensively on the client instead: never open the
    // wrong viewer for a row from this bucket, and surface the mismatch rather than silently doing
    // nothing.
    async function loadTrackerById(trackerId) {
        const existing = await fetchAndOpenIfEd(trackerId)

        if (existing?.encounter_type !== 'ED') {
            throw new Error(
                `Tracker ${trackerId} is not an ED Visit tracker (encounter_type: ${existing?.encounter_type ?? 'unknown'}).`,
            )
        }
    }

    async function submitTracker(overrides = {}) {
        const draft = { ...selectedClient.value, ...overrides }
        const payload = { patient_id: draft.patient_id }

        for (const key of CREATABLE_FIELDS) {
            const value = draft[key]
            if (value !== undefined && value !== null && value !== '') {
                payload[key] = value
            }
        }

        formErrors.value = {}
        try {
            tracker.value = await EdVisitTrackerService.createTracker(payload)
        } catch (error) {
            // Race: an active IP tracker appeared after the client was selected. An ED Visit can't be
            // created over it, so show the same blocking message as at selection time.
            if (
                error?.response?.status === 409 &&
                error.response.data?.encounter_type === 'IP'
            ) {
                activeTrackerConflict.value = {
                    tracker_id: error.response.data.tracker_id,
                    encounter_type: 'IP',
                }
                wizardStep.value = 'conflict'

                return
            }

            formErrors.value = validationErrors(error)
            if (!Object.keys(formErrors.value).length) throw error

            return
        }
        wizardStep.value = 'open'
        justCreated.value = true
        await loadTrackerNotes()
    }

    function validationErrors(error) {
        if (error?.response?.status !== 422) return {}

        const errors = error.response.data?.errors ?? {}

        return Object.fromEntries(
            Object.entries(errors).map(([key, messages]) => [
                key,
                Array.isArray(messages) ? messages[0] : messages,
            ]),
        )
    }

    function clearFormError(key) {
        if (formErrors.value[key] === undefined) return
        const remaining = { ...formErrors.value }
        delete remaining[key]
        formErrors.value = remaining
    }

    // Auto-save path (AC19): persists immediately, used by every editable header field.
    async function autoSaveField(key, value) {
        if (!tracker.value) return false
        tracker.value[key] = value
        clearFormError(key)
        try {
            await EdVisitTrackerService.saveField(
                tracker.value.tracker_id,
                key,
                value,
            )
        } catch (error) {
            const errors = validationErrors(error)
            if (!Object.keys(errors).length) throw error
            formErrors.value = {
                ...formErrors.value,
                [key]: errors.value ?? Object.values(errors)[0],
            }

            return false
        }

        return true
    }

    // The Hospitalization Info section (AC10) is committed as a whole, same reasoning as the
    // Hospitalization Record form's Current Hospitalization Info section.
    async function submitInfo(values) {
        if (!tracker.value) return false

        formErrors.value = {}
        try {
            tracker.value = await EdVisitTrackerService.saveInfo(
                tracker.value.tracker_id,
                values,
            )
        } catch (error) {
            formErrors.value = validationErrors(error)
            if (!Object.keys(formErrors.value).length) throw error

            return false
        }

        return true
    }

    // Read back after every write, so the panel shows what the server actually recorded rather than a
    // locally guessed entry — mirrors hospitalizationTracker.store.js's own `loadTrackerNotes`.
    async function loadTrackerNotes() {
        if (!tracker.value) return
        trackerNotes.value = await EdVisitTrackerService.getTrackerNotes(
            tracker.value.tracker_id,
        )
    }

    function openCreateNote() {
        if (!tracker.value) return
        noteModalTrackerId.value = tracker.value.tracker_id
        noteModalOpen.value = true
    }

    function openCreateNoteForTracker(trackerId) {
        noteModalTrackerId.value = trackerId
        noteModalOpen.value = true
    }

    function closeNoteModal() {
        noteModalOpen.value = false
        noteModalTrackerId.value = null
    }

    async function submitNote(text) {
        const trimmed = (text ?? '').trim()
        if (!trimmed || !noteModalTrackerId.value) return false

        const trackerId = noteModalTrackerId.value
        const notes = await EdVisitTrackerService.createNote(trackerId, trimmed)
        if (tracker.value?.tracker_id === trackerId) {
            trackerNotes.value = notes
        }
        closeNoteModal()
        return true
    }

    // Outcome workflow (PD-4069): each action trusts its input — required-field (red-border) validation
    // happens in the calling Fields component, same as hospitalizationTracker.store.js's discharge
    // lifecycle. `outcome`/`reason` are already the backend's own enum values (see
    // edVisitTracker.service.js), so there is no UI-label mapping step here.
    async function applyOutcome(trackerId, payload) {
        outcomeError.value = null
        outcomeMissingFields.value = []

        try {
            tracker.value = await EdVisitTrackerService.updateOutcome(
                trackerId,
                payload,
            )
        } catch (error) {
            outcomeError.value =
                error?.response?.data?.message ?? 'Unable to update outcome.'
            outcomeMissingFields.value =
                error?.response?.data?.missing_fields ?? []

            return
        }

        await loadTrackerNotes()
    }

    async function submitReadyForReview({ reviewer, notes }) {
        if (!tracker.value) return
        await applyOutcome(tracker.value.tracker_id, {
            outcome: 'READY_FOR_REVIEW',
            assigneeId: reviewer,
            notes,
        })
    }

    async function submitReadyForNextSteps({ nextStepsBy, notes }) {
        if (!tracker.value) return
        await applyOutcome(tracker.value.tracker_id, {
            outcome: 'READY_FOR_NEXT_STEPS',
            assigneeId: nextStepsBy,
            notes,
        })
    }

    async function submitCompleted({ completedBy, notes }) {
        if (!tracker.value) return
        await applyOutcome(tracker.value.tracker_id, {
            outcome: 'COMPLETED',
            assigneeId: completedBy,
            notes,
        })
    }

    async function submitClosed({ reason, notes }) {
        if (!tracker.value) return
        await applyOutcome(tracker.value.tracker_id, {
            outcome: 'CLOSED',
            reason,
            notes,
        })
    }

    // Follow-up outcome (PD-4073): the response is the updated tracker (carries the new
    // ed_follow_up_status), notes are re-read so the panel shows what the server logged.
    async function submitFollowUp({ outcome, notes }) {
        if (!tracker.value) return
        followUpError.value = null

        try {
            tracker.value = await EdVisitTrackerService.updateFollowUp(
                tracker.value.tracker_id,
                { outcome, notes },
            )
        } catch (error) {
            followUpError.value =
                error?.response?.data?.message ?? 'Unable to update follow-up.'

            return
        }

        await loadTrackerNotes()
    }

    function openAddContact() {
        editingContact.value = null
        contactModalOpen.value = true
    }

    function openEditContact(contact) {
        editingContact.value = contact
        contactModalOpen.value = true
    }

    function closeContactModal() {
        contactModalOpen.value = false
        editingContact.value = null
    }

    // The create/update response is the single saved contact; fold it into the tracker's `contacts` so
    // the table shows it without a refetch (AC3/AC5), then close the modal.
    async function submitContact(fields) {
        if (!tracker.value) return
        const trackerId = tracker.value.tracker_id
        const editingId = editingContact.value?.id
        const saved = editingId
            ? await EdVisitTrackerService.updateContact(trackerId, editingId, fields)
            : await EdVisitTrackerService.createContact(trackerId, fields)

        const contacts = tracker.value.contacts ?? []
        tracker.value = {
            ...tracker.value,
            contacts: editingId
                ? contacts.map((c) => (c.id === saved.id ? saved : c))
                : [...contacts, saved],
        }
        closeContactModal()
    }

    // Optimistic update, then the server's whole-tracker response (same as the Hospitalization store).
    async function setStaffFamilyTrainingRequired(value) {
        if (!tracker.value || tracker.value.status !== 'In Progress') return
        const previous = tracker.value
        tracker.value = { ...previous, staff_family_training_required: value }
        try {
            tracker.value =
                await EdVisitTrackerService.updateStaffFamilyTrainingRequired(
                    previous.tracker_id,
                    value,
                )
        } catch (e) {
            tracker.value = previous
            throw e
        }
    }

    function openAddEducationRecord() {
        editingEducationRecord.value = null
        educationModalOpen.value = true
    }

    function openEditEducationRecord(record) {
        editingEducationRecord.value = record
        educationModalOpen.value = true
    }

    function closeEducationModal() {
        educationModalOpen.value = false
        editingEducationRecord.value = null
    }

    async function submitEducationRecord(fields) {
        if (!tracker.value || tracker.value.status !== 'In Progress') return
        const trackerId = tracker.value.tracker_id
        const editingId = editingEducationRecord.value?.id
        const saved = editingId
            ? await EdVisitTrackerService.updateEducationRecord(
                  trackerId,
                  editingId,
                  fields,
              )
            : await EdVisitTrackerService.createEducationRecord(
                  trackerId,
                  fields,
              )

        const records = tracker.value.education_records ?? []
        tracker.value = {
            ...tracker.value,
            education_records: editingId
                ? records.map((r) => (r.id === saved.id ? saved : r))
                : [saved, ...records],
        }
        closeEducationModal()
    }

    // Optimistic update, then the server's whole-tracker response (same as setStaffFamilyTrainingRequired).
    async function setWasDiagnosisMade(value) {
        if (!tracker.value || tracker.value.status !== 'In Progress') return
        const previous = tracker.value
        tracker.value = { ...previous, was_diagnosis_made: value }
        try {
            tracker.value = await EdVisitTrackerService.updateWasDiagnosisMade(
                previous.tracker_id,
                value,
            )
        } catch (e) {
            tracker.value = previous
            throw e
        }
    }

    function openAddDiagnosis() {
        editingDiagnosis.value = null
        diagnosisModalOpen.value = true
    }

    function openEditDiagnosis(diagnosis) {
        editingDiagnosis.value = diagnosis
        diagnosisModalOpen.value = true
    }

    function closeDiagnosisModal() {
        diagnosisModalOpen.value = false
        editingDiagnosis.value = null
    }

    async function submitDiagnosis(fields) {
        if (!tracker.value || tracker.value.status !== 'In Progress') return
        const trackerId = tracker.value.tracker_id
        const editingId = editingDiagnosis.value?.id
        const saved = editingId
            ? await EdVisitTrackerService.updateDiagnosis(trackerId, editingId, fields)
            : await EdVisitTrackerService.createDiagnosis(trackerId, fields)

        const diagnoses = tracker.value.diagnoses ?? []
        tracker.value = {
            ...tracker.value,
            diagnoses: editingId
                ? diagnoses.map((d) => (d.id === saved.id ? saved : d))
                : [...diagnoses, saved],
        }
        closeDiagnosisModal()
    }

    // Loaded by EdSickPlanTab on mount (not on tracker open): nothing else reads `tracker.sickPlan`.
    async function loadSickPlan() {
        if (!tracker.value) return
        tracker.value = {
            ...tracker.value,
            sickPlan: await EdVisitTrackerService.getSickPlan(tracker.value.tracker_id),
        }
    }

    function openEditSickPlanItem(item) {
        editingSickPlanItem.value = item
        sickPlanModalOpen.value = true
    }

    function closeSickPlanModal() {
        sickPlanModalOpen.value = false
        editingSickPlanItem.value = null
    }

    async function submitSickPlanItem(fields) {
        if (!tracker.value || !editingSickPlanItem.value) return
        const sickPlan = await EdVisitTrackerService.updateSickPlanItem(
            tracker.value.tracker_id,
            editingSickPlanItem.value.id,
            fields,
        )
        tracker.value = { ...tracker.value, sickPlan }
        await loadTrackerNotes()
        closeSickPlanModal()
    }

    return {
        sickPlanModalOpen,
        editingSickPlanItem,
        loadSickPlan,
        openEditSickPlanItem,
        closeSickPlanModal,
        submitSickPlanItem,
        educationModalOpen,
        editingEducationRecord,
        setStaffFamilyTrainingRequired,
        openAddEducationRecord,
        openEditEducationRecord,
        closeEducationModal,
        submitEducationRecord,
        diagnosisModalOpen,
        editingDiagnosis,
        setWasDiagnosisMade,
        openAddDiagnosis,
        openEditDiagnosis,
        closeDiagnosisModal,
        submitDiagnosis,
        contactModalOpen,
        editingContact,
        openAddContact,
        openEditContact,
        closeContactModal,
        submitContact,
        isModalOpen,
        justCreated,
        wizardStep,
        assignees,
        clientSearchResults,
        clientSearchLoading,
        clientSearchPerformed,
        selectedClient,
        activeTrackerConflict,
        tracker,
        formErrors,
        trackerNotes,
        noteModalOpen,
        noteModalTrackerId,
        openCreateNote,
        openCreateNoteForTracker,
        closeNoteModal,
        submitNote,
        outcomeError,
        outcomeMissingFields,
        followUpError,
        openCreateModal,
        closeModal,
        hasUnsavedChanges,
        clientProfileConfirmOpen,
        setUnsaved,
        openClientProfile,
        cancelClientProfile,
        confirmClientProfile,
        searchClients,
        selectClient,
        loadAssignees,
        searchAnotherClient,
        openExistingTracker,
        loadTrackerById,
        submitTracker,
        clearFormError,
        autoSaveField,
        submitInfo,
        loadTrackerNotes,
        submitReadyForReview,
        submitReadyForNextSteps,
        submitCompleted,
        submitClosed,
        submitFollowUp,
    }
})
