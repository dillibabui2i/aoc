<template>
    <div class="current-info-field">
        <ui-input
            v-if="field.type === 'text'"
            :label="label"
            :model-value="asText(modelValue)"
            :disabled="disabled"
            :error="error"
            @update:model-value="emit('update:modelValue', $event)"
        />

        <ui-textarea
            v-else-if="field.type === 'textarea'"
            :label="label"
            :model-value="asText(modelValue)"
            :disabled="disabled"
            :error="error"
            @update:model-value="emit('update:modelValue', $event)"
        />

        <ui-select
            v-else-if="field.type === 'select' || field.type === 'multiselect'"
            :label="label"
            :multiple="field.type === 'multiselect'"
            :model-value="
                field.type === 'multiselect'
                    ? asList(modelValue)
                    : asText(modelValue)
            "
            :disabled="disabled"
            :error="error"
            @update:model-value="emit('update:modelValue', $event)"
        >
            <option v-if="field.type !== 'multiselect'" value="">
                Select...
            </option>
            <option
                v-for="opt in field.options"
                :key="opt.value"
                :value="opt.value"
            >
                {{ opt.label }}
            </option>
        </ui-select>

        <div
            v-else-if="field.type === 'toggle'"
            class="current-info-field__toggle"
        >
            <ToggleSwitch
                :model-value="!!modelValue"
                :disabled="disabled"
                @update:model-value="emit('update:modelValue', $event)"
            />
            <label>{{ field.label }}</label>
        </div>

        <div v-else-if="field.type === 'radio'" class="current-info-field__radio">
            <span class="current-info-field__radio-label">{{ label }}</span>
            <label
                v-for="opt in field.options"
                :key="opt.value"
                class="current-info-field__radio-option"
            >
                <input
                    type="radio"
                    :name="field.key"
                    :value="opt.value"
                    :checked="asText(modelValue) === opt.value"
                    :disabled="disabled"
                    @change="emit('update:modelValue', opt.value)"
                />
                {{ opt.label }}
            </label>
        </div>

        <ui-date-timepicker
            v-else
            :label="label"
            :title="field.shortLabel ? field.label : undefined"
            :mode="field.type"
            time-format="12"
            :model-value="asText(modelValue)"
            :error="error"
            :disabled="disabled"
            @update:model-value="emit('update:modelValue', $event)"
        />
    </div>
</template>

<script setup>
import { computed } from 'vue'
import {
    UiInput,
    UiSelect,
    UITextArea as UiTextarea,
    UiDateTimepicker,
} from '@angels-of-care/ui'
import ToggleSwitch from './ToggleSwitch.vue'

const props = defineProps({
    field: { type: Object, required: true }, // { key, label, type, options, optional } — type: text/textarea/select/multiselect/toggle/radio/date/datetime
    modelValue: { type: [String, Number, Boolean, Array], default: '' },
    error: { type: String, default: null },
    disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

// A toggle labels itself next to the switch; everything else labels the input,
// and marks itself required the way the header form does.
const label = computed(() => {
    const text = props.field.shortLabel || props.field.label

    return props.field.optional ? text : `${text} *`
})

// The design system types `modelValue` as a String; the tracker carries real
// numbers and nulls for some of these.
function asText(value) {
    return value === null || value === undefined ? '' : String(value)
}

// A multi-select insists on an array — a field that has never been answered comes
// back as null.
function asList(value) {
    return Array.isArray(value) ? value : []
}
</script>

<style scoped lang="scss">
/* This field sits low in a panel that scrolls, and the design system positions the
   dropdown below the trigger — down there that means it lands outside the visible
   area and adds to the scrollable height instead. Opening upward keeps it within
   what is already on screen: an element positioned above its container's top is
   clipped rather than scrolled to, so the height is capped to what reliably fits
   above the trigger even on a short screen. Scoped to this field on purpose — the
   filter bars are not inside a scrolling panel and open downward correctly. */
/* Prefixed with the class on purpose: a bare `:deep()` compiles to
   `[data-v-x] .ui-multiselect__dropdown`, which ties on specificity with the
   design system's own scoped `.ui-multiselect__dropdown[data-v-y]` and would only
   win by bundle order. */
.current-info-field :deep(.ui-multiselect__dropdown) {
    top: auto;
    bottom: calc(100% + 4px);
    max-height: 200px;
}

.current-info-field__toggle {
    display: flex;
    align-items: center;
    gap: 8px;
}

.current-info-field__radio {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.current-info-field__radio-label {
    font-size: 12px;
}
.current-info-field__radio-option {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-right: 12px;
    font-weight: normal;
}
</style>
