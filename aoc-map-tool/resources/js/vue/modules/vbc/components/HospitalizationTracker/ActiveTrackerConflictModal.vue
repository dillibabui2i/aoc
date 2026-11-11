<template>
    <ui-modal
        :model-value="store.wizardStep === 'conflict'"
        :title="isEd ? 'ED Visit Tracker Active' : 'Active Tracker Found'"
        css-class="vbc-modal tracker-conflict-modal"
        :static="true"
        :show-close="false"
        @update:model-value="store.searchAnotherClient"
    >
        <p v-if="isEd">
            This client already has an active ED Visit tracker. A
            Hospitalization cannot be created.
        </p>
        <p v-else>An active tracker already exists for the selected client:</p>

        <p class="tracker-conflict__client">
            Name: {{ store.selectedClient?.patient_name }}<br />
            Client ID: {{ store.selectedClient?.client_id }}
        </p>

        <p v-if="!isEd">What would you like to do?</p>

        <template #footer>
            <ui-button v-if="!isEd" @click="store.openExistingTracker"
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
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

const store = useHospitalizationTracker()

// An active ED tracker blocks Hospitalization creation outright: no "open it" option (this viewer only handles IP).
const isEd = computed(
    () => store.activeTrackerConflict?.encounter_type === 'ED',
)
</script>

<style scoped>
.tracker-conflict__client {
    margin: 16px 0;
}
</style>

<style>
/* Teleported to <body>, so not scopeable. Palette, buttons and typography come
   from modules/vbc/styles/vbc.scss via `.vbc-modal`; only this dialog's own
   geometry is here. */
.tracker-conflict-modal .modal-dialog {
    max-width: 720px;
}

/* Both actions are equally valid next steps, so neither is de-emphasised. */
.tracker-conflict-modal .modal-footer {
    justify-content: center;
    gap: 24px;
    border-top: none;
}
</style>
