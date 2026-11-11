<template>
    <ui-modal
        :model-value="store.noteModalOpen"
        title="Create Notes"
        size="w60"
        @update:model-value="store.closeNoteModal"
    >
        <ui-textarea
            v-model="noteText"
            label="Notes"
            :error="hasError ? 'This field is required.' : null"
        />

        <template #footer>
            <ui-button variant="secondary" @click="store.closeNoteModal"
                >Close</ui-button
            >
            <ui-button @click="submit">Submit</ui-button>
        </template>
    </ui-modal>
</template>

<script setup>
// Create Notes modal (AC5-AC10), reachable from both the bucket's Action
// column and the tracker header's title bar. Intentionally has no
// user-tagging control (AC13). Blank-submit validation uses `UITextArea`'s
// native `error` prop, unlike `UiInput`'s `has-error`/`:deep()` workaround
// used elsewhere in this tracker.
import { ref, watch } from 'vue'
import { UiModal, UITextArea as UiTextarea, UiButton } from '@angels-of-care/ui'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'

const store = useEdVisitTracker()

const noteText = ref('')
const hasError = ref(false)

// Resets the form whenever the modal is (re)opened.
watch(
    () => store.noteModalOpen,
    (open) => {
        if (!open) return
        noteText.value = ''
        hasError.value = false
    },
)

async function submit() {
    if (!noteText.value.trim()) {
        hasError.value = true
        return
    }
    await store.submitNote(noteText.value)
}
</script>
