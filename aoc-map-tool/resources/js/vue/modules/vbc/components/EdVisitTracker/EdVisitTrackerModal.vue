<template>
    <ui-modal
        :model-value="store.isModalOpen"
        title=""
        :size="store.wizardStep === 'open' ? 'w80' : 'w60'"
        :css-class="
            store.wizardStep === 'open'
                ? 'vbc-modal ed-tracker-modal ed-tracker-modal--open'
                : 'vbc-modal ed-tracker-modal ed-tracker-modal--form'
        "
        @update:model-value="store.closeModal"
    >
        <template #extraHeader>
            <EdVisitTrackerTitleBar />
        </template>

        <EdTrackerInfoGrid v-if="store.wizardStep !== 'open'" />

        <div v-else class="ed-tracker-modal__open">
            <div class="ed-tracker-modal__main">
                <EdTrackerInfoGrid />

                <!-- PD-4069: the real Outcome workflow (In Progress -> Ready for Review -> Ready for
                     Next Steps -> Complete, Close reachable from any of the first three). -->
                <EdOutcomeSubmitBar />

                <!-- AC9: the tabs. History stays a placeholder — its content is owned by
                     AOC-3482, same as the Hospitalization Record form defers it. The activity
                     log lives in the Notes panel on the right. -->
                <TrackerTabs v-model="activeTab" :tabs="TABS">
                    <template #hospitalizationInfo>
                        <EdHospitalizationInfoTab />
                    </template>
                    <template #history>
                        <PlaceholderTab label="Hospitalization/Transfers History" />
                    </template>
                    <template #sickPlan>
                        <EdSickPlanTab />
                    </template>
                    <template #contacts>
                        <EdContactsTab />
                    </template>
                    <template #followUp>
                        <EdFollowUpTab />
                    </template>
                    <template #education>
                        <EdEducationTab />
                    </template>
                    <template #diagnosis>
                        <EdDiagnosisTab />
                    </template>
                    <template #careCoordination>
                        <EdCareCoordinationTab />
                    </template>
                </TrackerTabs>
            </div>
            <EdNotesSidebar :notes="store.trackerNotes" />
        </div>
    </ui-modal>

    <EdActiveTrackerConflictModal />
    <UnsavedChangesModal
        :model-value="store.clientProfileConfirmOpen"
        @confirm="store.confirmClientProfile"
        @cancel="store.cancelClientProfile"
    />
    <EdCreateNoteModal />
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { UiModal } from '@angels-of-care/ui'
import {
    useEdVisitTracker,
    ED_FOLLOW_UP_OPTIONS,
} from '@/modules/vbc/store/edVisitTracker.store.js'
import EdVisitTrackerTitleBar from './EdVisitTrackerTitleBar.vue'
import EdTrackerInfoGrid from './EdTrackerInfoGrid.vue'
import EdActiveTrackerConflictModal from './EdActiveTrackerConflictModal.vue'
import UnsavedChangesModal from '@/modules/vbc/components/UnsavedChangesModal.vue'
import EdCreateNoteModal from './EdCreateNoteModal.vue'
import EdHospitalizationInfoTab from './EdHospitalizationInfoTab.vue'
import EdFollowUpTab from './EdFollowUpTab.vue'
import EdContactsTab from './EdContactsTab.vue'
import EdSickPlanTab from './EdSickPlanTab.vue'
import EdEducationTab from './EdEducationTab.vue'
import EdDiagnosisTab from './EdDiagnosisTab.vue'
import EdCareCoordinationTab from './EdCareCoordinationTab.vue'
import EdOutcomeSubmitBar from './EdOutcomeSubmitBar.vue'
import TrackerTabs from '@/modules/vbc/components/HospitalizationTracker/TrackerTabs.vue'
import PlaceholderTab from '@/modules/vbc/components/HospitalizationTracker/PlaceholderTab.vue'
import EdNotesSidebar from './EdNotesSidebar.vue'

const store = useEdVisitTracker()

// Flat tab layout (AC9) — unlike the Hospitalization Record form, an ED Visit tracker never grows a
// nested tab set. Follow-up (PD-4073) is only offered while the ED Visit is not Completed/Closed
// (AC11/AC12) and its follow-up workflow is still open (not Completed / Third Attempt, AC3/AC9).
const BASE_TABS = [
    { key: 'hospitalizationInfo', label: 'Hospitalization Info' },
    { key: 'history', label: 'Hospitalization/Transfers History' },
    { key: 'sickPlan', label: 'Sick Plan' },
    { key: 'contacts', label: 'Contacts' },
]
const FOLLOW_UP_TAB = { key: 'followUp', label: 'Follow-up' }
// PD-3969: only for users holding "Manage ED Visits" — the dashboard Blade sets the flag (the Care
// Coordination endpoints 403 without it).
const CARE_COORDINATION_TAB = { key: 'careCoordination', label: 'Care Coordination' }
// PD-3973: Education tab, same "Manage ED Visits" gate.
const EDUCATION_TAB = { key: 'education', label: 'Education' }
// PD-3970: Diagnosis tab, same "Manage ED Visits" gate.
const DIAGNOSIS_TAB = { key: 'diagnosis', label: 'Diagnosis' }
const canManageEdVisits = Boolean(window.vbcCanManageEdVisits)

const showFollowUpTab = computed(
    () =>
        !['Completed', 'Closed'].includes(store.tracker?.status) &&
        Boolean(ED_FOLLOW_UP_OPTIONS[store.tracker?.ed_follow_up_status]),
)

const TABS = computed(() => [
    ...BASE_TABS,
    ...(showFollowUpTab.value ? [FOLLOW_UP_TAB] : []),
    ...(canManageEdVisits ? [EDUCATION_TAB, DIAGNOSIS_TAB, CARE_COORDINATION_TAB] : []),
])

const activeTab = ref('hospitalizationInfo')

// PD-5800: a transition blocked by empty Hospitalization Info fields brings that tab to the front, where
// the fields are highlighted.
watch(
    () => store.outcomeMissingFields,
    (keys) => {
        if (keys.length) activeTab.value = 'hospitalizationInfo'
    },
)

// The tab disappears under the user once the workflow ends — fall back to the first tab.
watch(showFollowUpTab, (visible) => {
    if (!visible && activeTab.value === 'followUp') {
        activeTab.value = 'hospitalizationInfo'
    }
})
</script>

<style scoped>
/* Two columns, mirroring HospitalizationTrackerModal.vue's own layout: the form (info grid, Outcome bar,
   tabs) scrolls as one unit on the left; Notes gets its own independent scroll on the right (its list
   only grows and must not push the form around). */
.ed-tracker-modal__open {
    display: flex;
    gap: 16px;
    flex: 1;
    min-height: 0;
}
.ed-tracker-modal__main {
    flex: 1;
    min-width: 0;
    min-height: 0;
    overflow-y: auto;
    padding-right: 8px;
}
</style>

<style>
/* UiModal teleports to <body>, so this modal's own layout rules can't be scoped — same reasoning as
   HospitalizationTrackerModal.vue's equivalent block. */
.ed-tracker-modal .modal-dialog {
    max-height: 90vh;
}
.ed-tracker-modal--form .modal-content {
    max-height: 90vh;
    overflow-y: auto;
}
.modal.ed-tracker-modal--form .modal-dialog {
    max-width: min(60%, 1240px) !important;
}
/* Once open, the dialog is a fixed-height panel, same reasoning as HospitalizationTrackerModal.vue: the
   body hands off a fixed height to its two columns instead of scrolling itself. */
.ed-tracker-modal--open .modal-content {
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.ed-tracker-modal--open .modal-body {
    flex: 1;
    min-height: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.modal.ed-tracker-modal--open .modal-dialog {
    max-width: min(80%, 1440px) !important;
}
</style>
