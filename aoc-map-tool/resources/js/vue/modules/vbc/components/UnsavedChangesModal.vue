<template>
    <ui-modal
        :model-value="modelValue"
        title="Unsaved Changes"
        css-class="vbc-modal unsaved-changes-modal"
        :static="true"
        :show-close="false"
        @update:model-value="emit('cancel')"
    >
        <p>
            You have unsaved changes. If you open the client profile, they will
            be lost. Continue?
        </p>

        <template #footer>
            <ui-button @click="emit('cancel')">Cancel</ui-button>
            <ui-button @click="emit('confirm')">Continue</ui-button>
        </template>
    </ui-modal>
</template>

<script setup>
import { UiModal, UiButton } from '@angels-of-care/ui'

defineProps({
    modelValue: { type: Boolean, default: false },
})

const emit = defineEmits(['confirm', 'cancel'])
</script>

<style>
/* Teleported to <body>, so not scopeable. Palette and buttons come from
   modules/vbc/styles/vbc.scss via `.vbc-modal`; only the geometry is here. */
.unsaved-changes-modal .modal-dialog {
    max-width: 560px;
}
.unsaved-changes-modal .modal-footer {
    justify-content: center;
    gap: 24px;
    border-top: none;
}
</style>
