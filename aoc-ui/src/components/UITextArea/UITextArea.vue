<template>
  <div :class="['ui-textarea-wrap', { 'ui-textarea-wrap--error': error || requiredError }]">
    <label v-if="label" :for="textareaId" class="ui-textarea__label">
      {{ label }}<span v-if="required" class="ui-textarea__required">&nbsp;*</span>
    </label>
    <textarea
        :id="textareaId"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :maxlength="maxLength"
        :rows="rows"
        class="ui-textarea"
        v-bind="$attrs"
        @input="$emit('update:modelValue', $event.target.value)"
    />
    <div class="ui-textarea__footer">
      <span v-if="error || requiredError" class="ui-textarea__error">{{ error || requiredError }}</span>
      <span class="ui-textarea__counter" :class="{ 'ui-textarea__counter--limit': isAtLimit }">
        {{ modelValue.length }} / {{ maxLength }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed, useId } from 'vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  modelValue: { type: String,  default: '' },
  label:      { type: String,  default: null },
  placeholder:{ type: String,  default: '' },
  disabled:   { type: Boolean, default: false },
  error:      { type: String,  default: null },
  maxLength:  { type: Number,  default: 500 },
  rows:       { type: Number,  default: 4 },
  required:   { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])

const textareaId = useId()
const isAtLimit  = computed(() => props.modelValue.length >= props.maxLength)

const requiredError = computed(() => {
  return props.required && !props.modelValue.trim()
      ? `${props.label || 'This field'} is required.`
      : null
})
</script>

<style lang="scss" scoped>
@use '../../styles/tokens' as *;

.ui-textarea-wrap {
  display: flex;
  flex-direction: column;
  gap: $space-1;
}

.ui-textarea__label {
  font-size: $text-xs;
  font-weight: 500;
  color: $color-gray-700;
  font-family: $font-sans;
}

.ui-textarea__required {
  color: $color-danger;
}

.ui-textarea {
  padding: 7px $space-3;
  border: 1px solid $color-gray-200;
  border-radius: $radius-md;
  font-size: $text-sm;
  font-family: $font-sans;
  color: $color-gray-800;
  background: $color-white;
  outline: none;
  transition: border-color $transition-fast;
  width: 100%;
  box-sizing: border-box;
  resize: vertical;

  &::placeholder { color: $color-gray-400; }
  &:focus { border-color: $color-primary; }
  &:disabled { background: $color-gray-50; cursor: not-allowed; opacity: 0.6; }

  .ui-textarea-wrap--error & { border-color: $color-danger; }
}

.ui-textarea__footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.ui-textarea__error {
  font-size: $text-xs;
  color: $color-danger;
  font-family: $font-sans;
}

.ui-textarea__counter {
  font-size: $text-xs;
  color: $color-gray-400;
  font-family: $font-sans;
  margin-left: auto;

  &--limit { color: $color-danger; }
}
</style>