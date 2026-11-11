<template>
    <ui-modal
        :model-value="store.isModalOpen"
        title=""
        :size="store.wizardStep === 'open' ? 'w80' : 'w60'"
        :css-class="
            store.wizardStep === 'open'
                ? 'vbc-modal tracker-modal tracker-modal--open'
                : 'vbc-modal tracker-modal tracker-modal--form'
        "
        @update:model-value="store.closeModal"
    >
        <template #extraHeader>
            <TrackerTitleBar />
        </template>

        <TrackerInfoGrid v-if="store.wizardStep !== 'open'" />

        <div v-else class="tracker-modal__open">
            <div class="tracker-modal__main">
                <TrackerInfoGrid />
                <ui-alert
                    v-if="store.tracker?.is_transfer"
                    variant="info"
                    :message="`This patient was first admitted by the ${store.tracker.first_facility_name}. Check the transfers history tab for the original admission info.`"
                    class="tracker-modal__transfer-note"
                />
                <OutcomeSubmitBar />
                <TrackerTabs v-model="activeTab" :tabs="tabs">
                    <template #currentInfo>
                        <CurrentHospitalizationInfoTab />
                    </template>
                    <template #history>
                        <HospitalizationHistoryTab />
                    </template>
                    <template #contacts>
                        <ContactsTab />
                    </template>
                    <template #diagnosis>
                        <DiagnosisTab />
                    </template>
                    <template #sickPlan>
                        <SickPlanTab />
                    </template>
                    <template #careCoordination>
                        <CareCoordinationTab />
                    </template>
                    <template #education>
                        <EducationTab />
                    </template>
                    <template #followUp>
                        <FollowUpTab />
                    </template>
                </TrackerTabs>
            </div>
            <NotesSidebar :notes="store.trackerNotes" />
        </div>
    </ui-modal>

    <ActiveTrackerConflictModal />
    <UnsavedChangesModal
        :model-value="store.clientProfileConfirmOpen"
        @confirm="store.confirmClientProfile"
        @cancel="store.cancelClientProfile"
    />
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { UiModal, UiAlert } from '@angels-of-care/ui'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'
import TrackerTitleBar from './TrackerTitleBar.vue'
import TrackerInfoGrid from './TrackerInfoGrid.vue'
import ActiveTrackerConflictModal from './ActiveTrackerConflictModal.vue'
import UnsavedChangesModal from '@/modules/vbc/components/UnsavedChangesModal.vue'
import OutcomeSubmitBar from './OutcomeSubmitBar.vue'
import TrackerTabs from './TrackerTabs.vue'
import CurrentHospitalizationInfoTab from './CurrentHospitalizationInfoTab.vue'
import HospitalizationHistoryTab from './HospitalizationHistoryTab.vue'
import ContactsTab from './ContactsTab.vue'
import DiagnosisTab from './DiagnosisTab.vue'
import SickPlanTab from './SickPlanTab.vue'
import EducationTab from './EducationTab.vue'
import CareCoordinationTab from './CareCoordinationTab.vue'
import FollowUpTab from './FollowUpTab.vue'
import NotesSidebar from './NotesSidebar.vue'

const store = useHospitalizationTracker()

// Flat 4-tab layout: before any "In Progress" transition (AC3's Open state,
// and a tracker closed directly from Open — Figma's "Alternate Path -
// Closed"). Nested layout: once the tracker has been "In Progress" (AC8),
// including after a subsequent Closed transition (design.md Decision 2).
const FLAT_TABS = [
    { key: 'currentInfo', label: 'Current Hospitalization Info' },
    { key: 'history', label: 'Hospitalization History' },
    { key: 'sickPlan', label: 'Sick Plan' },
    { key: 'followUp', label: 'Follow-up' },
]
const NESTED_TABS = [
    {
        key: 'hospitalizationInfo',
        label: 'Hospitalization Info',
        subTabs: [
            { key: 'currentInfo', label: 'Current Hospitalization Info' },
            { key: 'history', label: 'Hospitalization History' },
        ],
    },
    { key: 'contacts', label: 'Contacts' },
    { key: 'diagnosis', label: 'Diagnosis' },
    { key: 'sickPlan', label: 'Sick Plan' },
    { key: 'careCoordination', label: 'Care Coordination' },
    { key: 'education', label: 'Education' },
    { key: 'followUp', label: 'Follow-up' },
]

// A tracker that has since moved past In Progress (e.g. Closed) no longer
// carries that in its `status`, and the real backend has no separate flag for
// it — so this is derived from the Activity Log entry the In Progress
// transition itself writes ("Status changed to 'In Progress' by ..."), which
// is already loaded into `trackerNotes` and persists across reloads.
// `tracker.wasInProgress` covers the still-mocked Discharge/etc lifecycle,
// which sets that flag directly rather than going through real notes.
const everWasInProgress = computed(() =>
    store.trackerNotes.some((note) =>
        note.notes?.includes("Status changed to 'In Progress'"),
    ),
)

const useNestedTabs = computed(
    () =>
        store.tracker?.status === 'In Progress' ||
        everWasInProgress.value ||
        !!store.tracker?.wasInProgress,
)
const tabs = computed(() => (useNestedTabs.value ? NESTED_TABS : FLAT_TABS))

const activeTab = ref('currentInfo')
watch(useNestedTabs, (nested) => {
    activeTab.value = nested ? 'hospitalizationInfo' : 'currentInfo'
})
</script>

<style scoped>
.tracker-modal__transfer-note {
    margin: 12px 0;
}

/* Two columns, each its own independent scroll region bounded by the fixed-height
   modal body (see .tracker-modal--open .modal-body below): the form scrolls as
   ONE unit — info grid, outcome fields and tabs together — so a status
   (Discharge, Completed, Ready for Next Steps, Ready for Review) adding fields
   lower down just grows the scroll area instead of squeezing the tab panel's
   own separate scroll down to nothing. Notes gets its own scroll (NotesSidebar.vue)
   since its list only grows and must not push the form around. */
.tracker-modal__open {
    display: flex;
    gap: 16px;
    flex: 1;
    min-height: 0;
}
.tracker-modal__main {
    flex: 1;
    min-width: 0;
    min-height: 0;
    overflow-y: auto;
    padding-right: 8px;
}
</style>

<style>
/* UiModal teleports to <body>, so this modal's own layout rules can't be scoped.
   Anything shared (palette, buttons, field stacking, read-only fields) lives in
   modules/vbc/styles/vbc.scss under `.vbc-modal` — only geometry specific to this
   modal belongs here. */
.tracker-modal .modal-dialog {
    max-height: 90vh;
}

/* Before the tracker exists there is only the form, so the dialog itself scrolls
   if it doesn't fit, and it hugs the form instead of stretching across a large
   monitor. */
.tracker-modal--form .modal-content {
    max-height: 90vh;
    overflow-y: auto;
}
.modal.tracker-modal--form .modal-dialog {
    max-width: min(60%, 1240px) !important;
}

/* Once open, the dialog is a fixed-height panel: the body itself doesn't
   scroll — it hands off a fixed height to its two columns, each of which
   scrolls independently (.tracker-modal__main above, .notes-sidebar). */
.tracker-modal--open .modal-content {
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.tracker-modal--open .modal-body {
    flex: 1;
    min-height: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.modal.tracker-modal--open .modal-dialog {
    max-width: min(80%, 1440px) !important;
}
</style>
