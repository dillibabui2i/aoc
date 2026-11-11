<template>
  <button
      :type="type"
      :disabled="disabled || loading"
      :aria-busy="loading || undefined"
      :class="[
      'ui-btn',
      `ui-btn--${variant}`,
      `ui-btn--${size}`,
      { 'ui-btn--loading': loading },
    ]"
      v-bind="$attrs"
  >
    <UiSpinner v-if="loading" size="sm"/>
    <slot/>
  </button>
</template>

<script setup>
import UiSpinner from '../UiSpinner/UiSpinner.vue'

defineProps({
  variant: {type: String, default: 'primary'}, // primary | secondary | danger | ghost
  size: {type: String, default: 'md'},       // sm | md | lg
  type: {type: String, default: 'button'},
  loading: {type: Boolean, default: false},
  disabled: {type: Boolean, default: false},
})
</script>

<style lang="scss" scoped>
@use '../../styles/tokens' as *;

.ui-btn {
  display: inline-flex;
  align-items: center;
  gap: $space-1-5;
  font-weight: 500;
  border-radius: $radius-md;
  border: 1px solid transparent;
  cursor: pointer;
  transition: background $transition-fast, opacity $transition-fast;
  white-space: nowrap;
  font-family: $font-sans;

  &:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  &:focus-visible {
    outline: 2px solid $color-primary;
    outline-offset: 2px;
  }

  &--sm {
    padding: $space-1 $space-2-5;
    font-size: $text-xs;
  }

  &--md {
    padding: 7px $space-4;
    font-size: $text-sm;
  }

  &--lg {
    padding: $space-2-5 $space-5-5;
    font-size: $text-base;
  }

  &--primary {
    background: $color-primary;
    color: $color-white;
    border-color: $color-primary;

    &:hover:not(:disabled) {
      background: $color-primary-700;
      border-color: $color-primary-700;
    }
  }

  &--secondary {
    background: $color-gray-100;
    color: $color-gray-700;
    border-color: $color-gray-200;

    &:hover:not(:disabled) {
      background: $color-gray-200;
    }
  }

  &--danger {
    background: $color-danger;
    color: $color-white;
    border-color: $color-danger;

    &:hover:not(:disabled) {
      background: $color-danger-700;
      border-color: $color-danger-700;
    }
  }

  &--ghost {
    background: transparent;
    color: $color-primary;

    &:hover:not(:disabled) {
      background: $color-primary-50;
    }
  }
}
</style>