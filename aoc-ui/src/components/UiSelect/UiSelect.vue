<template>
  <div :class="['ui-select-wrap', { 'ui-select-wrap--error': error || requiredError }]" ref="wrapRef">
    <label v-if="label" :for="selectId" class="ui-select__label">
      {{ label }}<span v-if="required" class="ui-select__required">&nbsp;*</span>
    </label>

    <!-- Regular select -->
    <select
        v-if="!multiple"
        :id="selectId"
        :value="modelValue"
        :disabled="disabled"
        class="ui-select"
        v-bind="$attrs"
        @change="$emit('update:modelValue', $event.target.value)"
    >
      <slot/>
    </select>

    <!-- Multiselect -->
    <div v-else class="ui-multiselect" :class="{ 'ui-multiselect--open': isOpen, 'ui-multiselect--disabled': disabled }">
      <button
          type="button"
          class="ui-multiselect__trigger"
          :disabled="disabled"
          @click="toggle"
      >
        <span class="ui-multiselect__value">{{ triggerLabel }}</span>
        <span class="ui-multiselect__arrow">▾</span>
      </button>

      <div v-if="isOpen" class="ui-multiselect__dropdown">
        <!-- Select all -->
        <label class="ui-multiselect__option">
          <input
              type="checkbox"
              :checked="isAllSelected"
              :indeterminate.prop="isIndeterminate"
              @change="toggleAll"
          />
          <span>Select all</span>
        </label>

        <!-- Slot options parsed into checkboxes -->
        <label
            v-for="opt in parsedOptions"
            :key="opt.value"
            class="ui-multiselect__option"
            :class="{ 'ui-multiselect__option--selected': modelValue.includes(opt.value) }"
        >
          <input
              type="checkbox"
              :value="opt.value"
              :checked="modelValue.includes(opt.value)"
              @change="toggleOption(opt.value)"
          />
          <span>{{ opt.text }}</span>
        </label>
      </div>
    </div>

    <div v-if="multiple" style="display:none" ref="slotRef">
      <slot/>
    </div>

    <span v-if="error || requiredError" class="ui-select__error">{{ error || requiredError }}</span>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, useId, watch } from 'vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  modelValue:  { type: [String, Array], default: '' },
  label:       { type: String,  default: null },
  disabled:    { type: Boolean, default: false },
  error:       { type: String,  default: null },
  multiple:    { type: Boolean, default: false },
  placeholder: { type: String,  default: 'Select...' },
  required:    { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const selectId  = useId()
const isOpen    = ref(false)
const wrapRef   = ref(null)
const slotRef   = ref(null)
const parsedOptions = ref([])

function parseOptions() {
  if (!slotRef.value) return
  const opts = slotRef.value.querySelectorAll('option')
  parsedOptions.value = Array.from(opts)
      .filter(o => o.value !== '')
      .map(o => ({ value: o.value, text: o.textContent.trim() }))
}

onMounted(() => {
  if (props.multiple) parseOptions()
})

watch(() => slotRef.value, () => {
  if (props.multiple) parseOptions()
})

const requiredError = computed(() => {
  const isEmpty = Array.isArray(props.modelValue) ? props.modelValue.length === 0 : !props.modelValue
  return props.required && isEmpty
      ? `${props.label || 'This field'} is required.`
      : null
})

const isAllSelected   = computed(() => parsedOptions.value.length > 0 && props.modelValue.length === parsedOptions.value.length)
const isIndeterminate = computed(() => props.modelValue.length > 0 && props.modelValue.length < parsedOptions.value.length)

const triggerLabel = computed(() => {
  if (!props.modelValue.length) return props.placeholder
  if (isAllSelected.value) return 'All selected'
  if (props.modelValue.length === 1) {
    const opt = parsedOptions.value.find(o => o.value === props.modelValue[0])
    return opt?.text ?? props.modelValue[0]
  }
  return `${props.modelValue.length} selected`
})

function toggle() {
  if (!props.disabled) {
    parseOptions()
    isOpen.value = !isOpen.value
  }
}

function toggleOption(value) {
  const current = [...props.modelValue]
  const idx = current.indexOf(value)
  if (idx === -1) current.push(value)
  else current.splice(idx, 1)
  emit('update:modelValue', current)
}

function toggleAll() {
  if (isAllSelected.value) {
    emit('update:modelValue', [])
  } else {
    emit('update:modelValue', parsedOptions.value.map(o => o.value))
  }
}

function onClickOutside(e) {
  if (wrapRef.value && !wrapRef.value.contains(e.target)) {
    isOpen.value = false
  }
}

onMounted(() => document.addEventListener('click', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside))
</script>

<style lang="scss" scoped>
@use '../../styles/tokens' as *;

.ui-select-wrap {
  display: flex;
  flex-direction: column;
  gap: $space-1;
  position: relative;
}

.ui-select__label {
  font-size: $text-xs;
  font-weight: 500;
  color: $color-gray-700;
  font-family: $font-sans;
}

.ui-select__required {
  color: $color-danger;
}

.ui-select {
  padding: 7px $space-3;
  border: 1px solid $color-gray-200;
  border-radius: $radius-md;
  font-size: $text-sm;
  font-family: $font-sans;
  color: $color-gray-800;
  background: $color-white;
  outline: none;
  cursor: pointer;
  transition: border-color $transition-fast;
  width: 100%;
  box-sizing: border-box;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right $space-3 center;
  padding-right: $space-8;

  &:focus { border-color: $color-primary; }
  &:disabled { background-color: $color-gray-50; cursor: not-allowed; opacity: 0.6; }

  .ui-select-wrap--error & { border-color: $color-danger; }
}

.ui-select__error {
  font-size: $text-xs;
  color: $color-danger;
  font-family: $font-sans;
}

.ui-multiselect {
  position: relative;
  &--disabled { pointer-events: none; }
}

.ui-multiselect__trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  min-width: 160px;
  padding: 7px $space-3;
  border: 1px solid $color-gray-200;
  border-radius: $radius-md;
  font-size: $text-sm;
  font-family: $font-sans;
  color: $color-gray-800;
  background: $color-white;
  cursor: pointer;
  outline: none;
  text-align: left;
  transition: border-color $transition-fast;
  box-sizing: border-box;

  .ui-multiselect--open & { border-color: $color-primary; }
  &:disabled { background-color: $color-gray-50; }
}

.ui-multiselect__value {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.ui-multiselect__arrow {
  margin-left: $space-2;
  color: $color-gray-400;
  font-size: 12px;
  flex-shrink: 0;
}

.ui-multiselect__dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  min-width: 100%;
  background: $color-white;
  border: 1px solid $color-gray-200;
  border-radius: $radius-md;
  box-shadow: 0 4px 12px rgba(0,0,0,.08);
  z-index: 100;
  max-height: 220px;
  overflow-y: auto;
  padding: $space-1 0;
}

.ui-multiselect__option {
  display: flex;
  align-items: center;
  gap: $space-2;
  padding: $space-1-5 $space-3;
  font-size: $text-sm;
  font-family: $font-sans;
  color: $color-gray-800;
  cursor: pointer;

  &:hover { background: $color-gray-50; }
  &--selected { background: $color-gray-50; }

  input[type="checkbox"] { cursor: pointer; flex-shrink: 0; }
}
</style>
