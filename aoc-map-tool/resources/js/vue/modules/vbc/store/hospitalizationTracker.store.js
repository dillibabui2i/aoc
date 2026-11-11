import { defineStore } from 'pinia'
import { ref, computed, nextTick } from 'vue'
import {
    HospitalizationTrackerService,
    CURRENT_USER,
} from '@/modules/vbc/services/hospitalizationTracker.service.js'

// The autocomplete only queries once the term is this long: shorter terms make
// the backend scan every current admission for a list nobody can use, so it
// rejects them (HospitalizationClientSearchDTO::MIN_SEARCH_LENGTH — keep in sync).
export const CLIENT_SEARCH_MIN_LENGTH = 3

// Header fields the create endpoint accepts alongside patient_id — mirrors
// HospitalizationTrackerFieldEnum on the backend.
const CREATABLE_FIELDS = [
    'assigned_to_id',
    'level',
    'high_utilizer',
    'backup_g_tube',
    'backup_trach',
]

export const useHospitalizationTracker = defineStore(
    'hospitalizationTracker',
    () => {
        const isModalOpen = ref(false)

        // Set once `submitTracker()` succeeds, so the bucket section can tell "just created a
        // tracker, then closed the modal" apart from "opened an existing tracker to view it" and
        // only refresh its list/filters in the former case.
        const justCreated = ref(false)

        // 'search' -> 'conflict' | 'confirmed' -> 'open'
        const wizardStep = ref('search')

        const clientSearchResults = ref([])
        const clientSearchLoading = ref(false)
        // Distinguishes "no search run yet" from "searched and found nobody", so
        // the autocomplete can say the latter instead of just staying shut.
        const clientSearchPerformed = ref(false)

        // "Assigned To" options: users holding the Intensive Case Management
        // permission. Loaded once per modal open, since the list is small and
        // rarely changes.
        const assignees = ref([])

        const selectedClient = ref(null)
        const activeTrackerConflict = ref(null) // { tracker_id }
        const tracker = ref(null)
        const outcomeError = ref(null)

        // The tracker's Activity Log as the server has it.
        const trackerNotes = ref([])

        // The hospitalizations the current tracker was transferred from, nearest first.
        const transferHistory = ref([])

        // Per-field messages from a 422, keyed by field name. Client-side
        // validation catches the empty required fields before we get here, so
        // this is the safety net for anything only the server can judge.
        const formErrors = ref({})

        const contactModalOpen = ref(false)
        const editingContact = ref(null) // null => creating a new contact

        const diagnosisModalOpen = ref(false)
        const editingDiagnosis = ref(null) // null => creating a new diagnosis

        const sickPlanModalOpen = ref(false)
        const editingSickPlanItem = ref(null) // null => creating a new item

        const educationModalOpen = ref(false)
        const editingEducationRecord = ref(null) // null => creating a new record

        // Note creation (AC5-AC10). `noteModalTrackerId` is tracked
        // independently of `tracker` so the bucket's entry point can open
        // the modal for a tracker without loading it into the modal
        // (design.md Decision 1).
        const noteModalOpen = ref(false)
        const noteModalTrackerId = ref(null)

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
            justCreated.value = false
            wizardStep.value = 'search'
            clientSearchResults.value = []
            clientSearchPerformed.value = false
            selectedClient.value = null
            activeTrackerConflict.value = null
            tracker.value = null
            transferHistory.value = []
            outcomeError.value = null
            formErrors.value = {}
            trackerNotes.value = []
            contactModalOpen.value = false
            editingContact.value = null
            diagnosisModalOpen.value = false
            editingDiagnosis.value = null
            sickPlanModalOpen.value = false
            editingSickPlanItem.value = null
            educationModalOpen.value = false
            editingEducationRecord.value = null
            noteModalOpen.value = false
            noteModalTrackerId.value = null
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

            // Nothing is looked up below the threshold, and the dropdown stays
            // shut rather than reporting "no clients found" for a term that was
            // never searched for.
            if (term.length < CLIENT_SEARCH_MIN_LENGTH) {
                clientSearchResults.value = []
                clientSearchPerformed.value = false

                return
            }

            clientSearchLoading.value = true
            try {
                clientSearchResults.value =
                    await HospitalizationTrackerService.searchClients(term)
                clientSearchPerformed.value = true
            } finally {
                clientSearchLoading.value = false
            }
        }

        async function selectClient(client) {
            clientSearchResults.value = []
            clientSearchPerformed.value = false

            // The autocomplete only carries enough to identify the client; the
            // values the form pre-fills with come back from this call, together
            // with whether the client already has a tracker in flight.
            const profile = await HospitalizationTrackerService.getClient(
                client.patient_id,
            )
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
            assignees.value = await HospitalizationTrackerService.getAssignees()
        }

        function searchAnotherClient() {
            clientSearchResults.value = []
            clientSearchPerformed.value = false
            selectedClient.value = null
            activeTrackerConflict.value = null
            wizardStep.value = 'search'
        }

        async function openExistingTracker() {
            const trackerId = activeTrackerConflict.value?.tracker_id
            if (!trackerId) return

            const existing =
                await HospitalizationTrackerService.getTrackerById(trackerId)

            // The active tracker blocking creation may be either kind (AC5/AC18 check for "any
            // active tracker of any kind"), but this viewer only handles IP ones; the backend now
            // 404s a mismatched encounter_type (PD-3983 follow-up), which getTrackerById already
            // turns into `null`. A `null` here means the conflicting tracker is actually an ED
            // encounter — bail out rather than opening a blank viewer. No cross-store hand-off to
            // edVisitTracker.store.js like the reverse case has: that store already imports this
            // one, and importing it back here would be circular.
            if (!existing) return

            tracker.value = existing
            activeTrackerConflict.value = null
            wizardStep.value = 'open'
            await loadTrackerNotes()
        }

        // Only the editable header fields are sent; everything else on the form
        // is read-only client data the server already has.
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
                tracker.value =
                    await HospitalizationTrackerService.createTracker(payload)
            } catch (error) {
                formErrors.value = validationErrors(error)
                if (!Object.keys(formErrors.value).length) throw error

                return
            }
            wizardStep.value = 'open'
            justCreated.value = true
            await loadTrackerNotes()
        }

        // { field: ['msg', ...] } -> { field: 'msg' }; empty for anything that
        // isn't a validation failure, so those keep bubbling up.
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

        async function loadTrackerById(trackerId) {
            reset()
            loadAssignees()
            tracker.value =
                await HospitalizationTrackerService.getTrackerById(trackerId)
            wizardStep.value = 'open'
            isModalOpen.value = true
            await loadTrackerNotes()
        }

        // Re-fetches the currently loaded tracker and reassigns it, without
        // touching `wizardStep`/`isModalOpen`. Used by `FollowUpTab.vue` so
        // it reflects follow-up changes made through the separate
        // `followUp.store.js` (design.md Decision 5 of the Hospitalization
        // Follow-ups story).
        async function refreshTracker() {
            if (!tracker.value) return
            tracker.value = await HospitalizationTrackerService.getTrackerById(
                tracker.value.tracker_id,
            )
        }

        // Auto-save path (AC31): persists immediately, called on every change
        // of a field not flagged "Log Updates?".
        // Returns whether the value reached the server, so the caller can confirm
        // it to the user (there is no Save button on this path — without a
        // confirmation an auto-saved field looks exactly like an unsaved one).
        async function autoSaveField(key, value) {
            if (!tracker.value) return false
            tracker.value[key] = value
            clearFormError(key)
            try {
                await HospitalizationTrackerService.saveField(
                    tracker.value.tracker_id,
                    key,
                    value,
                )
            } catch (error) {
                // The server reports the failure against "value"; the form shows
                // it against the field the user was actually editing.
                const errors = validationErrors(error)
                if (!Object.keys(errors).length) throw error
                formErrors.value = {
                    ...formErrors.value,
                    [key]: errors.value ?? Object.values(errors)[0],
                }

                return false
            }

            // Some header fields are logged too (High Utilizer), so the panel has
            // to catch up after a single-field save as well.
            await loadTrackerNotes()

            return true
        }

        // Current Hospitalization Info is committed as a whole (its fields carry
        // required rules that only make sense checked together), so there is no
        // per-field save on that section — the tab validates and calls this once.
        // The Activity Log entries are written server-side, for the fields the
        // ticket marks as logged.
        async function submitCurrentInfo(values) {
            if (!tracker.value) return false

            formErrors.value = {}
            try {
                tracker.value =
                    await HospitalizationTrackerService.saveCurrentInfo(
                        tracker.value.tracker_id,
                        values,
                    )
            } catch (error) {
                formErrors.value = validationErrors(error)
                if (!Object.keys(formErrors.value).length) throw error

                return false
            }

            await loadTrackerNotes()

            return true
        }

        // Read back after every write, so the panel shows what the server
        // actually recorded rather than a locally guessed entry.
        async function loadTrackerNotes() {
            if (!tracker.value) return
            trackerNotes.value =
                await HospitalizationTrackerService.getTrackerNotes(
                    tracker.value.tracker_id,
                )
        }

        // Sick Plan is fetched separately from the tracker (it belongs to the
        // client, not the tracker — AC5/AC12) and merged onto `tracker.value`
        // so SickPlanTab.vue/SickPlanDetailsModal.vue keep reading
        // `tracker.sickPlan` the same way they read `tracker.contacts`.
        // Deliberately NOT called from openExistingTracker/submitTracker/loadTrackerById above: nothing
        // outside SickPlanTab.vue reads `tracker.sickPlan`, so preloading it on every tracker open would
        // be a wasted request whenever the user never opens that tab. SickPlanTab.vue's own `onMounted`
        // calls this instead, exactly when it's actually needed.
        async function loadSickPlan() {
            if (!tracker.value) return
            tracker.value = {
                ...tracker.value,
                sickPlan: await HospitalizationTrackerService.getSickPlan(
                    tracker.value.tracker_id,
                ),
            }
        }

        // Outcome transition (AC6/AC13): validates the Closed+Reason
        // requirement (AC12), persists via the real endpoint (the two-line
        // Activity Log note is written server-side), reloads the notes
        // panel, and — only once the service call succeeds — closes the
        // modal for a Closed outcome (AC20); the "All Hospitalizations"
        // bucket already excludes Closed trackers on its next load (AC21).
        async function submitOutcome({ outcome, reason, fields, notes }) {
            if (!tracker.value) return

            if (outcome === 'Closed' && !reason) {
                outcomeError.value = 'This field is required.'
                return
            }
            outcomeError.value = null

            try {
                tracker.value =
                    await HospitalizationTrackerService.updateOutcomeStatus(
                        tracker.value.tracker_id,
                        { outcome, reason, fields, notes },
                    )
            } catch (error) {
                outcomeError.value =
                    error?.response?.data?.message ??
                    'Unable to update outcome.'

                return
            }

            await loadTrackerNotes()

            // A transfer leaves the modal open: the response is the new tracker, which
            // is now the one on screen (the closed one moves to Hospitalization History).
            if (outcome === 'Closed' && reason !== 'Transfer') {
                closeModal()
            }
        }

        // Transfer: Closed + "Transfer" on the current tracker, plus the Current Hospitalization
        // Info of the hospitalization the patient moves to and the free-text reason.
        async function submitTransfer({ fields, notes }) {
            await submitOutcome({
                outcome: 'Closed',
                reason: 'Transfer',
                fields,
                notes,
            })
        }

        // "Hospitalization History" tab: the hospitalizations this one was transferred from.
        async function loadTransferHistory() {
            if (!tracker.value) return
            transferHistory.value =
                await HospitalizationTrackerService.getTrackerHistory(
                    tracker.value.tracker_id,
                )
        }

        // Discharge lifecycle. Each action trusts its input — required-field
        // (red-border) validation happens in the calling Fields component
        // (design.md Decision 2), not here. The Activity Log entry is
        // written server-side (HospitalizationTrackerOutcomeService), so
        // these just reload the notes panel afterwards, same as
        // `submitOutcome` below.

        async function applyOutcome(trackerId, payload) {
            outcomeError.value = null

            try {
                tracker.value =
                    await HospitalizationTrackerService.updateOutcome(
                        trackerId,
                        payload,
                    )
            } catch (error) {
                outcomeError.value =
                    error?.response?.data?.message ??
                    'Unable to update outcome.'

                return
            }

            await loadTrackerNotes()
        }

        async function submitDischarge(fields) {
            if (!tracker.value) return
            await applyOutcome(tracker.value.tracker_id, {
                outcome: 'Discharge',
                fields: {
                    expected_discharge_at: fields.expectedDischargeDate,
                    discharged_at: fields.dischargeDate,
                    first_billable_shift_completed:
                        fields.firstBillableShiftCompleted,
                    first_billable_shift_at: fields.firstBillableShiftDate,
                },
                notes: fields.dischargeNotes,
            })
        }

        async function submitReadyForReview({ reviewer, notes }) {
            if (!tracker.value) return
            await applyOutcome(tracker.value.tracker_id, {
                outcome: 'Ready for Review',
                assigneeId: reviewer,
                notes,
            })
        }

        async function submitReadyForNextSteps({ nextStepsBy, notes }) {
            if (!tracker.value) return
            await applyOutcome(tracker.value.tracker_id, {
                outcome: 'Ready for Next Steps',
                assigneeId: nextStepsBy,
                notes,
            })
        }

        async function submitCompleted({ completedBy, notes }) {
            if (!tracker.value) return
            await applyOutcome(tracker.value.tracker_id, {
                outcome: 'Completed',
                assigneeId: completedBy,
                notes,
            })
        }

        // Contacts (AC5-AC11). One modal handles create/edit/read-only;
        // `editingContact` doubles as both "which contact is being edited"
        // and "are we creating" (null), mirroring the existing
        // `activeTrackerConflict`/`wizardStep` "store owns modal lifecycle"
        // convention.
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

        async function submitContact(fields) {
            if (!tracker.value) return
            tracker.value = editingContact.value
                ? await HospitalizationTrackerService.updateContact(
                      tracker.value.tracker_id,
                      editingContact.value.id,
                      fields,
                  )
                : await HospitalizationTrackerService.createContact(
                      tracker.value.tracker_id,
                      fields,
                  )
            closeContactModal()
        }

        // Diagnosis (AC9-AC15). One modal handles create/edit/read-only;
        // `editingDiagnosis` doubles as both "which diagnosis is being
        // edited" and "are we creating" (null), mirroring the Contacts
        // convention exactly.
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
            if (!tracker.value) return
            tracker.value = editingDiagnosis.value
                ? await HospitalizationTrackerService.updateDiagnosis(
                      tracker.value.tracker_id,
                      editingDiagnosis.value.id,
                      fields,
                  )
                : await HospitalizationTrackerService.createDiagnosis(
                      tracker.value.tracker_id,
                      fields,
                  )
            closeDiagnosisModal()
        }

        // "Was the diagnosis made?" toggle (AC4/AC6). Not routed through autoSaveField: that endpoint
        // only accepts header-form fields, and this one belongs to the Diagnosis tab — see
        // HospitalizationTrackerDiagnosisService::setWasDiagnosisMade() on the PHP side. Optimistic
        // update first, same as autoSaveField, so the toggle doesn't visually lag the request.
        async function setWasDiagnosisMade(value) {
            if (!tracker.value) return
            tracker.value.was_diagnosis_made = value
            tracker.value = await HospitalizationTrackerService.updateWasDiagnosisMade(
                tracker.value.tracker_id,
                value,
            )
        }

        // "Staff / Family Training/Education required?" toggle. Not routed through autoSaveField:
        // that endpoint only accepts header-form fields, and this one belongs to the Education tab —
        // see HospitalizationTrackerTrainingService::setStaffFamilyTrainingRequired() on the PHP side.
        // Mirrors setWasDiagnosisMade exactly: optimistic update first, so the toggle doesn't visually
        // lag the request.
        async function setStaffFamilyTrainingRequired(value) {
            if (!tracker.value) return
            tracker.value.staff_family_training_required = value
            tracker.value = await HospitalizationTrackerService.updateStaffFamilyTrainingRequired(
                tracker.value.tracker_id,
                value,
            )
        }

        // Sick Plan (AC7-AC9 of that story). The Sick Plan's items are fixed
        // (one per catalog entry, created blank when the tracker becomes
        // "Open" — AC3); the calendar icon only ever edits an existing item.
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
            const sickPlan = await HospitalizationTrackerService.updateSickPlanItem(
                tracker.value.tracker_id,
                editingSickPlanItem.value.id,
                fields,
            )
            tracker.value = { ...tracker.value, sickPlan }
            await loadTrackerNotes()
            closeSickPlanModal()
        }

        // Education (Training/Education records). One modal handles
        // create/edit/read-only; `editingEducationRecord` doubles as both
        // "which record is being edited" and "are we creating" (null),
        // mirroring the Contacts/Diagnosis/Sick Plan convention exactly.
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
            if (!tracker.value) return
            tracker.value = editingEducationRecord.value
                ? await HospitalizationTrackerService.updateEducationRecord(
                      tracker.value.tracker_id,
                      editingEducationRecord.value.id,
                      fields,
                  )
                : await HospitalizationTrackerService.createEducationRecord(
                      tracker.value.tracker_id,
                      fields,
                  )
            closeEducationModal()
        }

        // Notes (AC5-AC10). `openCreateNote` is used from within an
        // already-open tracker (the title bar icon); `openCreateNoteForTracker`
        // is used from the bucket, which never loads the full tracker
        // (design.md Decision 1).
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
            const notes = await HospitalizationTrackerService.createNote(
                trackerId,
                trimmed,
            )
            if (tracker.value?.tracker_id === trackerId) {
                trackerNotes.value = notes
            }
            closeNoteModal()
            return true
        }

        return {
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
            outcomeError,
            formErrors,
            trackerNotes,
            contactModalOpen,
            editingContact,
            diagnosisModalOpen,
            editingDiagnosis,
            sickPlanModalOpen,
            editingSickPlanItem,
            educationModalOpen,
            editingEducationRecord,
            noteModalOpen,
            noteModalTrackerId,
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
            submitTracker,
            loadTrackerById,
            refreshTracker,
            autoSaveField,
            clearFormError,
            submitCurrentInfo,
            submitOutcome,
            submitTransfer,
            loadTransferHistory,
            transferHistory,
            submitDischarge,
            submitReadyForReview,
            submitReadyForNextSteps,
            submitCompleted,
            openAddContact,
            openEditContact,
            closeContactModal,
            submitContact,
            openAddDiagnosis,
            openEditDiagnosis,
            closeDiagnosisModal,
            submitDiagnosis,
            setWasDiagnosisMade,
            loadSickPlan,
            openEditSickPlanItem,
            closeSickPlanModal,
            submitSickPlanItem,
            openAddEducationRecord,
            openEditEducationRecord,
            closeEducationModal,
            submitEducationRecord,
            setStaffFamilyTrainingRequired,
            openCreateNote,
            openCreateNoteForTracker,
            closeNoteModal,
            submitNote,
        }
    },
)
