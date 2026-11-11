<template>
    <div class="dose-target">
        <div class="dose-target__row">
            <button
                type="button"
                class="dose-target__delete"
                title="Delete dose target"
                @click="emit('remove')"
            >
                <i class="fa fa-trash"></i>
            </button>

            <div class="dose-target__value">
                <ui-input
                    :model-value="row.from_age"
                    :error="errors.from_age"
                    inputmode="numeric"
                    aria-label="Start value"
                    @beforeinput="blockNonDigits"
                    @update:model-value="
                        (v) => update({ from_age: digitsOnly(v) })
                    "
                />
            </div>
            <div class="dose-target__unit">
                <ui-select
                    :model-value="row.from_age_unit"
                    :error="errors.from_age_unit"
                    aria-label="Start unit"
                    @update:model-value="(v) => update({ from_age_unit: v })"
                >
                    <option
                        v-for="unit in units"
                        :key="unit.value"
                        :value="unit.value"
                    >
                        {{ unit.label }}
                    </option>
                </ui-select>
            </div>

            <label class="dose-target__to">
                <input
                    type="checkbox"
                    :checked="row.to_range"
                    @change="(e) => toggleRange(e.target.checked)"
                />
                to
            </label>

            <div class="dose-target__value">
                <ui-input
                    :model-value="row.to_age"
                    :disabled="!row.to_range"
                    :error="errors.to_age"
                    inputmode="numeric"
                    aria-label="End value"
                    @beforeinput="blockNonDigits"
                    @update:model-value="
                        (v) => update({ to_age: digitsOnly(v) })
                    "
                />
            </div>
            <div class="dose-target__unit">
                <ui-select
                    :model-value="row.to_age_unit"
                    disabled
                    :error="errors.to_age_unit"
                    aria-label="End unit"
                >
                    <option value="">- None -</option>
                    <option
                        v-for="unit in units"
                        :key="unit.value"
                        :value="unit.value"
                    >
                        {{ unit.label }}
                    </option>
                </ui-select>
            </div>
        </div>

        <p v-if="message" class="dose-target__message">{{ message }}</p>
    </div>
</template>

<script setup>
// One "Dose Age Target" row of the Add/Edit Immunization Schedule modal. `row` is a model so the row
// replaces itself on every edit instead of mutating a prop. Per-field `errors` only paint the borders; the
// text of the first one is shown once under the row, because the fields are too narrow to carry it.
import { computed } from 'vue'
import { UiInput, UiSelect } from '@angels-of-care/ui'

const props = defineProps({
    units: { type: Array, required: true },
    errors: { type: Object, default: () => ({}) },
})
const emit = defineEmits(['remove'])
const row = defineModel({ type: Object, required: true })

// UiInput only shows its `error` text when it is non-empty; the borders are what matters here.
const message = computed(
    () => Object.values(props.errors).find(Boolean) ?? null,
)

// The end unit must equal the start unit, so it is never picked: while "to" is ticked it mirrors the start
// unit (the select is disabled), and unticking drops the end values — they are ignored on save anyway.
function update(patch) {
    const next = { ...row.value, ...patch }
    if (next.to_range) next.to_age_unit = next.from_age_unit
    row.value = next
}

function toggleRange(checked) {
    update(
        checked
            ? { to_range: true }
            : { to_range: false, to_age: '', to_age_unit: '' },
    )
}

const digitsOnly = (value) => value.replace(/\D/g, '')

function blockNonDigits(event) {
    if (event.data && /\D/.test(event.data)) event.preventDefault()
}
</script>

<style scoped lang="scss">
@use '../styles/tokens' as *;

.dose-target {
    &__row {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    &__delete {
        background: none;
        border: 0;
        padding: 0 4px;
        color: $danger;
        cursor: pointer;
    }

    &__value {
        width: 72px;
        flex: none;
    }

    &__unit {
        width: 130px;
        flex: none;
    }

    &__to {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin: 0;
        font-size: $text-sm;
    }

    // Borders still go red; the text moves to `&__message`.
    .dose-target__row :deep(.ui-input__error),
    .dose-target__row :deep(.ui-select__error) {
        display: none;
    }

    &__message {
        margin: 2px 0 0 28px;
        font-size: $text-sm;
        color: $danger;
    }
}
</style>
