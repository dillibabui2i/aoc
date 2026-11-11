<template>
  <div :class="['ui-input-wrap', { 'ui-input-wrap--error': error || internalError || requiredError }]">
    <label v-if="label" :for="inputId" class="ui-input__label">
      {{ label }}<span v-if="required" class="ui-input__required">&nbsp;*</span>
    </label>
    <input
        :id="inputId"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :type="nativeType"
        class="ui-input"
        v-bind="$attrs"
        @input="onInput"
        @blur="onBlur"
    />
    <span v-if="error || internalError || requiredError" class="ui-input__error">{{ error || internalError || requiredError }}</span>
  </div>
</template>

<script setup>
import { ref, computed, useId } from 'vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  modelValue:  { type: String,  default: '' },
  label:       { type: String,  default: null },
  placeholder: { type: String,  default: '' },
  type:        { type: String,  default: 'text' },
  disabled:    { type: Boolean, default: false },
  error:       { type: String,  default: null },
  required:    { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const inputId = useId()
const internalError = ref(null)

const nativeType = computed(() => ['phone', 'email'].includes(props.type) ? 'text' : props.type)

const requiredError = computed(() => {
  return props.required && !props.modelValue.trim()
      ? `${props.label || 'This field'} is required.`
      : null
})

// `value` here is always the mask's own previous output plus whatever the user just typed/deleted —
// it already carries the literal "+1" prefix the mask itself inserts. Stripping that prefix first
// (rather than just `\D`-stripping the whole string) matters: "+1"'s "1" is a digit character, so
// counting it as if the user typed it corrupts every edit by one digit (extra/duplicated digits,
// worse on backspace) — see UiInput.test.js's "does not corrupt digits" regression tests.
function significantDigits(value) {
  const withoutMaskPrefix = value.startsWith('+1') ? value.slice(2) : value
  const digits = withoutMaskPrefix.replace(/\D/g, '')
  return (digits.length >= 11 ? digits.replace(/^1/, '') : digits).slice(0, 10)
}

function applyPhoneMask(value) {
  const digits = significantDigits(value)
  // A cleared field must come back to a real empty string, not the "+1 " stub: that stub is
  // non-empty, so a `required_without` pairing (e.g. phone-or-email) sees it as "filled" and a
  // consumer's own digit-format check (or the backend's, if it slips through) rejects it as an
  // invalid phone number the user never actually typed.
  if (!digits) return ''
  if (digits.length <= 3) return `+1 (${digits}`
  if (digits.length <= 6) return `+1 (${digits.slice(0, 3)}) ${digits.slice(3)}`
  return `+1 (${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`
}

// Maps a count of significant digits to the caret offset right after the last of them in the
// mask's own output shape ("+1 (XXX) XXX-XXXX") — mirrors `applyPhoneMask`'s branches so the caret
// lands in the same spot the mask just built, instead of always snapping to the end of the string.
function caretPositionForDigitCount(digitCount) {
  if (digitCount <= 0) return 3
  if (digitCount <= 3) return 4 + digitCount
  if (digitCount <= 6) return 6 + digitCount
  return 7 + digitCount
}

function onInput(e) {
  let value = e.target.value

  if (props.type === 'phone') {
    const digitsBeforeCursor = significantDigits(value.slice(0, e.target.selectionStart)).length
    value = applyPhoneMask(value)
    e.target.value = value
    const caretDigits = Math.min(digitsBeforeCursor, significantDigits(value).length)
    const caretPos = caretPositionForDigitCount(caretDigits)
    e.target.setSelectionRange(caretPos, caretPos)
  }

  internalError.value = null
  emit('update:modelValue', value)
}

function onBlur() {
  if (props.type === 'email') {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    internalError.value = props.modelValue && !re.test(props.modelValue)
        ? 'Invalid email address'
        : null
  }

  if (props.type === 'phone') {
    const digits = significantDigits(props.modelValue)
    internalError.value = digits.length > 0 && digits.length < 10
        ? 'Enter a valid phone number'
        : null
  }
}
</script>

<style lang="scss" scoped>
@use '../../styles/tokens' as *;

.ui-input-wrap {
  display: flex;
  flex-direction: column;
  gap: $space-1;
}

.ui-input__label {
  font-size: $text-xs;
  font-weight: 500;
  color: $color-gray-700;
  font-family: $font-sans;
}

.ui-input__required {
  color: $color-danger;
}

.ui-input {
  padding: 7px $space-3;
  padding-right: $space-8;
  border: 1px solid $color-gray-200;
  border-radius: $radius-md;
  font-size: $text-sm;
  font-family: $font-sans;
  color: $color-gray-800;
  background-color: $color-white;
  background-repeat: no-repeat;
  background-position: right $space-3 center;
  outline: none;
  transition: border-color $transition-fast;

  .ui-input-wrap--error & { border-color: $color-danger; }
}

.ui-input__error {
  font-size: $text-xs;
  color: $color-danger;
  font-family: $font-sans;
}
</style>