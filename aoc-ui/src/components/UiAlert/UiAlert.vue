<template>
  <div :class="['ui-alert', `ui-alert--${variant}`]" role="alert">
    <span class="ui-alert__message">{{ message }}</span>
    <div v-if="$slots.default" class="ui-alert__actions">
      <slot/>
    </div>
  </div>
</template>

<script setup>
defineProps({
  variant: {type: String, default: 'error'}, // error | warning | success | info
  message: {type: String, required: true},
})
</script>

<style lang="scss" scoped>
@use '../../styles/tokens' as *;

.ui-alert {
  display: flex;
  align-items: center;
  gap: $space-2-5;
  padding: $space-3 $space-4;
  border-radius: $radius-md;
  border: 1px solid transparent;
  font-size: $text-sm;
  font-family: $font-sans;

  &--error {
    background: $color-danger-50;
    border-color: $color-danger-200;
    color: $color-danger-700;
  }

  &--warning {
    background: $color-warning-100;
    border-color: #f59e0b;
    color: $color-warning-700;
  }

  &--success {
    background: $color-success-100;
    border-color: $color-success;
    color: $color-success-700;
  }

  &--info {
    background: $color-primary-100;
    border-color: $color-primary;
    color: $color-primary-700;
  }
}

.ui-alert__message {
  flex: 1;
}

.ui-alert__actions {
  display: flex;
  align-items: center;
  gap: $space-2;
}
</style>