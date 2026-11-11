<template>
    <ui-modal
        :model-value="store.sickPlanModalOpen"
        title="Sick Plan Details"
        size="w60"
        @update:model-value="store.closeSickPlanModal"
    >
        <div v-if="isReadOnly" class="sick-plan-details__readonly">
            <div class="sick-plan-details__row">
                <div>
                    <strong>Sick Plan Type</strong>
                    <p>
                        {{ typeLabel(store.editingSickPlanItem?.sickPlanType) }}
                    </p>
                </div>
                <div>
                    <strong>Completed?</strong>
                    <p>
                        {{
                            store.editingSickPlanItem?.completed ? 'Yes' : 'No'
                        }}
                    </p>
                </div>
                <div>
                    <strong>Completed by</strong>
                    <p>{{ store.editingSickPlanItem?.completedBy }}</p>
                </div>
            </div>
            <div class="sick-plan-details__row">
                <div>
                    <strong>Creation Date</strong>
                    <p>{{ store.editingSickPlanItem?.creationDate }}</p>
                </div>
                <div>
                    <strong>Author</strong>
                    <p>{{ store.editingSickPlanItem?.author }}</p>
                </div>
            </div>
            <div>
                <strong>Sick Plan Notes</strong>
                <p>{{ store.editingSickPlanItem?.notes }}</p>
            </div>
        </div>

        <div v-else class="sick-plan-details__form">
            <div class="sick-plan-details__row">
                <ui-select
                    v-model="form.sickPlanType"
                    label="Sick Plan Type"
                    placeholder="Select..."
                    disabled
                    :error="
                        errors.sickPlanType ? 'This field is required.' : null
                    "
                >
                    <option value="">Select...</option>
                    <option
                        v-for="opt in SICK_PLAN_TYPE_OPTIONS"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </ui-select>
                <div class="sick-plan-details__toggle">
                    <ToggleSwitch v-model="form.completed" />
                    <label>Completed?</label>
                </div>
            </div>

            <div class="sick-plan-details__row">
                <ui-input
                    label="Creation Date"
                    :model-value="form.creationDate"
                    disabled
                />
                <ui-input label="Author" :model-value="form.author" disabled />
            </div>

            <ui-textarea v-model="form.notes" label="Sick Plan Notes" />
        </div>

        <template #footer>
            <ui-button variant="secondary" @click="store.closeSickPlanModal"
                >Close</ui-button
            >
            <ui-button v-if="!isReadOnly" @click="submit">Submit</ui-button>
        </template>
    </ui-modal>
</template>

<script setup>
// Sick Plan item creation/editing and read-only display once the tracker
// leaves "In Progress" (AC7-AC9 of that story). Mirrors
// ContactDetailsModal.vue/DiagnosisDetailsModal.vue's one-modal, three-mode
// pattern exactly. Creation Date/Author are system-set (never validated);
// "Completed by" is auto-set to the current user when "Completed?" is
// toggled on (design.md Decision 5), not a user-editable field.
import { reactive, computed, watch } from 'vue'
import {
    UiModal,
    UiInput,
    UiSelect,
    UITextArea as UiTextarea,
    UiButton,
} from '@angels-of-care/ui'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'
import { CURRENT_USER } from '@/modules/vbc/services/hospitalizationTracker.service.js'
import { SICK_PLAN_TYPE_OPTIONS } from '@/modules/vbc/config/hospitalizationTrackerFields.js'
import ToggleSwitch from './ToggleSwitch.vue'

const store = useHospitalizationTracker()

const isReadOnly = computed(() => store.tracker?.status !== 'In Progress')

function typeLabel(value) {
    return SICK_PLAN_TYPE_OPTIONS.find((opt) => opt.value === value)?.label
}

function blankForm() {
    return {
        sickPlanType: '',
        completed: false,
        notes: '',
        creationDate: new Date().toLocaleString('en-US'),
        author: CURRENT_USER,
    }
}

const form = reactive(blankForm())

const errors = reactive({
    sickPlanType: false,
})

// Re-fills the form whenever the modal is (re)opened, for both the
// create (blank, system-set Creation Date/Author) and edit (prefilled
// from `editingSickPlanItem`) cases.
watch(
    () => store.sickPlanModalOpen,
    (open) => {
        if (!open) return
        // `notes` comes back `null` (not `''`) for a still-blank item (AC3) — UiTextArea's
        // `v-model` always calls `.length` on it, which throws on `null`.
        Object.assign(form, blankForm(), store.editingSickPlanItem ?? {}, {
            notes: store.editingSickPlanItem?.notes ?? '',
        })
        errors.sickPlanType = false
    },
)

function validate() {
    errors.sickPlanType = !form.sickPlanType
    return !errors.sickPlanType
}

function submit() {
    if (!validate()) return
    store.submitSickPlanItem({
        ...form,
        completedBy: form.completed ? CURRENT_USER : null,
    })
}
</script>

<style scoped>
.sick-plan-details__form,
.sick-plan-details__readonly {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.sick-plan-details__row {
    display: flex;
    gap: 12px;
    align-items: center;
}
.sick-plan-details__row > * {
    flex: 1;
}
.sick-plan-details__toggle {
    display: flex;
    align-items: center;
    gap: 8px;
}
.sick-plan-details__readonly p {
    margin: 2px 0 0;
}
</style>
