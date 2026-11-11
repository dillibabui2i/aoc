<template>
  <span :class="['ui-spinner', `ui-spinner--${size}`]" role="status" aria-label="Loading"/>
</template>

<script setup>
defineProps({
  size: {type: String, default: 'md'}, // sm | md | lg
})
</script>

<style lang="scss" scoped>
@use '../../styles/tokens' as *;

.ui-spinner {
  display: inline-block;
  border: 2px solid currentColor;
  border-top-color: transparent;
  border-radius: $radius-full;
  animation: ui-spin 0.6s linear infinite;
  flex-shrink: 0;

  &--sm {
    width: 12px;
    height: 12px;
  }

  &--md {
    width: 16px;
    height: 16px;
  }

  &--lg {
    width: 20px;
    height: 20px;
  }
}

@keyframes ui-spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
