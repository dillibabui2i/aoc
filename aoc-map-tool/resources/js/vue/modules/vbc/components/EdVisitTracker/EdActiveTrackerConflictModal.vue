<template>
    <ui-modal
        :model-value="store.wizardStep === 'conflict'"
        :title="isIp ? 'Hospitalization Tracker Active' : 'Active Tracker Found'"
        css-class="vbc-modal ed-tracker-conflict-modal"
        :static="true"
        :show-close="false"
        @update:model-value="store.searchAnotherClient"
    >
        <p v-if="isIp">
            This client already has an active Hospitalization (IP) tracker. An
            ED Visit cannot be created.
        </p>
        <p v-else>An active tracker already exists for the selected client:</p>

        <p class="ed-tracker-conflict__client">
            Name: {{ store.selectedClient?.patient_name }}<br />
            Client ID: {{ store.selectedClient?.client_id }}
        </p>

        <p v-if="!isIp">What would you like to do?</p>

        <template #footer>
            <ui-button v-if="!isIp" @click="store.openExistingTracker"
                >Open the active tracker</ui-button
            >
            <ui-button @click="store.searchAnotherClient"
                >Search another client</ui-button
            >
        </template>
    </ui-modal>
</template>

<script setup>
import { computed } from 'vue'
import { UiModal, UiButton } from '@angels-of-care/ui'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'

const store = useEdVisitTracker()

// An active IP tracker blocks ED creation outright: no "open it" option (it isn't an ED tracker).
const isIp = computed(
    () => store.activeTrackerConflict?.encounter_type === 'IP',
)
</script>

<style scoped>
.ed-tracker-conflict__client {
    margin: 16px 0;
}
</style>

<style>
/* Teleported to <body>, so not scopeable — see ActiveTrackerConflictModal.vue's equivalent rule. */
.ed-tracker-conflict-modal .modal-dialog {
    max-width: 720px;
}
.ed-tracker-conflict-modal .modal-footer {
    justify-content: center;
    gap: 24px;
    border-top: none;
}
</style>
