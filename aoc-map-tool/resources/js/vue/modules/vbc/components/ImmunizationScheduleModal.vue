<template>
    <ui-modal
        :model-value="store.modalOpen"
        :title="
            store.editing
                ? 'Edit Immunization Schedule'
                : 'Add Immunization Schedule'
        "
        css-class="vbc-modal immunization-schedule-modal"
        @update:model-value="store.closeModal"
    >
        <div class="schedule-form">
            <div class="schedule-form__row">
                <ui-select
                    v-model="form.cvx_group_code"
                    label="Vaccine Category"
                    :error="
                        errors.category ? 'Vaccine Category is required.' : null
                    "
                    @update:model-value="onCategoryChange"
                >
                    <option value="">- Select -</option>
                    <option
                        v-for="opt in categoryOptions"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.text }}
                    </option>
                </ui-select>
                <ui-select
                    v-model="form.state"
                    label="State"
                    :error="
                        errors.state
                            ? 'State is required.'
                            : serverMessage('state')
                    "
                >
                    <option value="">- Select -</option>
                    <option
                        v-for="opt in stateOptions"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.text }}
                    </option>
                </ui-select>
            </div>

            <ui-select
                v-model="form.vaccine_cvx_code"
                label="Vaccine Name"
                :disabled="!form.cvx_group_code"
                :error="
                    errors.vaccine
                        ? 'Vaccine Name is required.'
                        : serverMessage('vaccine_cvx_code')
                "
            >
                <option value="">- Select -</option>
                <option
                    v-for="opt in vaccineOptions"
                    :key="opt.value"
                    :value="opt.value"
                >
                    {{ opt.text }}
                </option>
            </ui-select>

            <div class="schedule-form__row schedule-form__row--halves">
                <ui-select
                    v-model="form.recommended_for"
                    label="Recommended for"
                    :error="
                        errors.recommendedFor
                            ? 'Recommended For is required.'
                            : serverMessage('recommended_for')
                    "
                >
                    <option value="">- Select -</option>
                    <option
                        v-for="opt in recommendedForOptions"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </ui-select>
                <ui-select
                    v-model="form.frequency"
                    label="Frequency"
                    :error="
                        errors.frequency
                            ? 'Frequency is required.'
                            : serverMessage('frequency')
                    "
                >
                    <option value="">- Select -</option>
                    <option
                        v-for="opt in frequencyOptions"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </ui-select>
            </div>

            <section class="schedule-form__targets">
                <h6 class="schedule-form__targets-title">Dose Age Targets</h6>
                <p class="schedule-form__hint">
                    Enter the recommended age or age range for each dose in the
                    series. The system will automatically generate one tracking
                    record per dose target for every eligible active client.
                </p>
                <ui-button size="sm" @click="addTarget"
                    >Add dose target</ui-button
                >

                <div class="schedule-form__target-list">
                    <DoseTargetRow
                        v-for="(row, index) in form.dose_age_targets"
                        :key="row.key"
                        v-model="form.dose_age_targets[index]"
                        :units="unitOptions"
                        :errors="rowErrors[index] ?? {}"
                        @remove="removeTarget(index)"
                    />
                </div>

                <p
                    v-for="message in targetMessages"
                    :key="message"
                    class="schedule-form__error"
                >
                    {{ message }}
                </p>
            </section>
        </div>

        <template #footer>
            <label class="schedule-form__active">
                Active
                <ToggleSwitch v-model="form.is_active" />
            </label>
            <ui-button :loading="store.saving" @click="submit"
                >Submit</ui-button
            >
            <ui-button @click="store.closeModal">Close</ui-button>
        </template>
    </ui-modal>
</template>

<script setup>
// Add / Edit Immunization Schedule (one modal, one set of rules). Required fields and the per-row value
// rules are checked here for instant red borders; what needs the whole set or the database — duplicate /
// overlapping / contained targets, one template per vaccine and state — comes back from the backend as 422
// and is shown in place (see StoreImmunizationScheduleRequest / ValidDoseAgeTargets). The form is never
// cleared on a failed save.
import { computed, reactive, watch } from 'vue'
import { UiModal, UiSelect, UiButton } from '@angels-of-care/ui'
import { useImmunizationSchedule } from '@/modules/vbc/store/immunizationSchedule.store.js'
import DoseTargetRow from '@/modules/vbc/components/DoseTargetRow.vue'
import ToggleSwitch from '@/modules/vbc/components/HospitalizationTracker/ToggleSwitch.vue'

// Mirrors ImmunizationDoseAgeUnitEnum on the backend; the dropdowns' option lists come from the backend.
const MONTH = 'MONTH'
const MAX_MONTHS = 23
const MIN_YEARS = 2

const store = useImmunizationSchedule()

let nextKey = 0

function blankTarget() {
    return {
        key: ++nextKey,
        from_age: '',
        from_age_unit: unitOptions.value[0]?.value ?? MONTH,
        to_range: false,
        to_age: '',
        to_age_unit: '',
    }
}

function blankForm() {
    return {
        cvx_group_code: '',
        state: '',
        vaccine_cvx_code: '',
        recommended_for: '',
        frequency: '',
        // Active defaults to ON on Add (Figma).
        is_active: true,
        dose_age_targets: [],
    }
}

const form = reactive(blankForm())
const errors = reactive({
    category: false,
    state: false,
    vaccine: false,
    recommendedFor: false,
    frequency: false,
    targets: false,
})
const rowErrors = reactive([])

const categoryOptions = computed(() => store.options?.vaccineCategories ?? [])
const stateOptions = computed(() => store.options?.states ?? [])
const recommendedForOptions = computed(
    () => store.options?.recommendedFor ?? [],
)
const frequencyOptions = computed(() => store.options?.frequencies ?? [])
const unitOptions = computed(() => store.options?.doseAgeUnits ?? [])
const vaccineOptions = computed(() =>
    (store.options?.vaccines ?? []).filter(
        (v) => String(v.category) === String(form.cvx_group_code),
    ),
)

// The vaccine list depends on the category: a vaccine of another category must not stay selected.
function onCategoryChange() {
    form.vaccine_cvx_code = ''
}

function addTarget() {
    form.dose_age_targets.push(blankTarget())
    errors.targets = false
}

function removeTarget(index) {
    form.dose_age_targets.splice(index, 1)
    rowErrors.splice(index, 1)
}

function fillFrom(schedule) {
    Object.assign(form, blankForm())
    if (!schedule) return

    Object.assign(form, {
        cvx_group_code: schedule.cvx_group_code ?? '',
        // A federal (NULL-state) template has to get a state before it can be saved.
        state: schedule.state ?? '',
        vaccine_cvx_code: schedule.vaccine_cvx_code ?? '',
        recommended_for: schedule.recommended_for ?? '',
        // A legacy frequency the modal no longer offers comes back as-is and has to be re-picked.
        frequency: schedule.frequency ?? '',
        is_active: schedule.is_active,
        dose_age_targets: schedule.dose_age_targets.map((t) => ({
            key: ++nextKey,
            from_age: String(t.from_age ?? ''),
            from_age_unit: t.from_age_unit ?? MONTH,
            to_range: t.to_range,
            to_age: t.to_age === null ? '' : String(t.to_age),
            to_age_unit: t.to_age_unit ?? '',
        })),
    })
}

function resetErrors() {
    Object.keys(errors).forEach((key) => (errors[key] = false))
    rowErrors.splice(0)
}

watch(
    () => store.modalOpen,
    async (open) => {
        if (!open) return
        resetErrors()
        fillFrom(store.editing)
        await store.loadOptions()
    },
)

// What the backend said describes the values that were sent; once the user changes anything it is stale
// (e.g. an overlap message for a row that has since been rewritten), so it goes away until the next Submit.
watch(
    form,
    () => {
        if (Object.keys(store.serverErrors).length) store.serverErrors = {}
    },
    { deep: true },
)

// Same limits as ValidDoseAgeTargets: months up to 23, years 2 and up, and the end must be above the start.
function ageError(value, unit, label, allowZero) {
    if (value === '') return `${label} is required.`
    const age = Number(value)
    if (!allowZero && age === 0) return `${label} must be greater than 0.`
    if (unit === MONTH) {
        return age > MAX_MONTHS
            ? `${label} in months must be ${MAX_MONTHS} or lower.`
            : null
    }
    return age < MIN_YEARS
        ? `${label} in years must be ${MIN_YEARS} or higher.`
        : null
}

function validateRow(row) {
    const result = {
        from_age: ageError(
            row.from_age,
            row.from_age_unit,
            'Start value',
            true,
        ),
        from_age_unit: row.from_age_unit ? null : 'Start unit is required.',
        to_age: null,
        to_age_unit: null,
    }
    if (!row.to_range) return result

    result.to_age = ageError(row.to_age, row.to_age_unit, 'End value', false)
    result.to_age_unit = !row.to_age_unit
        ? 'End unit is required.'
        : row.to_age_unit !== row.from_age_unit
          ? 'Start and end units must match.'
          : null
    if (
        !result.to_age &&
        !result.to_age_unit &&
        row.from_age !== '' &&
        Number(row.to_age) <= Number(row.from_age)
    ) {
        result.to_age = 'End value must be greater than the start value.'
    }
    return result
}

function validate() {
    errors.category = !form.cvx_group_code
    errors.state = !form.state
    errors.vaccine = !form.vaccine_cvx_code
    errors.recommendedFor = !form.recommended_for
    errors.frequency = !form.frequency
    errors.targets = form.dose_age_targets.length === 0

    rowErrors.splice(
        0,
        rowErrors.length,
        ...form.dose_age_targets.map(validateRow),
    )
    const rowsInvalid = rowErrors.some((r) => Object.values(r).some(Boolean))

    return !(
        errors.category ||
        errors.state ||
        errors.vaccine ||
        errors.recommendedFor ||
        errors.frequency ||
        errors.targets ||
        rowsInvalid
    )
}

const serverMessage = (key) => store.serverErrors[key]?.[0] ?? null

// Everything the backend said about Dose Age Targets (whole-set rules and row-level ones), plus the local
// "at least one" message.
const targetMessages = computed(() => {
    const messages = Object.entries(store.serverErrors)
        .filter(([key]) => key.startsWith('dose_age_targets'))
        .flatMap(([, list]) => list)
    if (errors.targets)
        messages.unshift('At least one Dose Age Target is required.')
    return [...new Set(messages)]
})

function toPayload() {
    return {
        vaccine_cvx_code: Number(form.vaccine_cvx_code),
        state: form.state,
        recommended_for: form.recommended_for,
        frequency: form.frequency,
        is_active: form.is_active,
        dose_age_targets: form.dose_age_targets.map((t) => ({
            from_age: Number(t.from_age),
            from_age_unit: t.from_age_unit,
            to_range: t.to_range,
            to_age: t.to_range ? Number(t.to_age) : null,
            to_age_unit: t.to_range ? t.to_age_unit : null,
        })),
    }
}

async function submit() {
    if (!validate()) return
    await store.saveSchedule(toPayload())
}
</script>

<style scoped lang="scss">
@use '../styles/tokens' as *;

.schedule-form {
    display: flex;
    flex-direction: column;
    gap: 12px;

    &__row {
        display: grid;
        grid-template-columns: 1fr 160px;
        gap: 12px;
    }

    &__row--halves {
        grid-template-columns: 1fr 1fr;
    }

    &__targets {
        display: flex;
        flex-direction: column;
        gap: 8px;
        align-items: flex-start;
        min-height: 160px;
    }

    // Roughly five rows (46px each) — more doses scroll inside the list, so the fields above and the footer
    // stay on screen.
    &__target-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        width: 100%;
        max-height: 250px;
        overflow-y: auto;
        padding-right: 8px;
    }

    &__targets-title {
        margin: 0;
        font-weight: 700;
    }

    &__hint {
        margin: 0;
        font-size: $text-sm;
        color: $text-muted;
    }

    &__error {
        margin: 0;
        font-size: $text-sm;
        color: $danger;
    }

    &__active {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin: 0 auto 0 0;
        font-size: $text-base;
    }
}
</style>
