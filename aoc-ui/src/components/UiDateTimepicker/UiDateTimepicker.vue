<template>
  <div class="ui-datepicker-wrap">
    <div :class="['ui-datepicker__row', { 'ui-datepicker__row--error': error || requiredError }]">
      <span v-if="label" class="ui-datepicker__label">{{ label }}<span v-if="required" class="ui-datepicker__required">&nbsp;*</span></span>
      <input
          ref="inputRef"
          type="text"
          class="ui-datepicker__input"
          :placeholder="placeholder"
          :disabled="disabled"
      />
    </div>
    <span v-if="error || requiredError" class="ui-datepicker__error">{{ error || requiredError }}</span>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.min.css'

const props = defineProps({
  modelValue: {type: String, default: ''},
  mode: {type: String, default: 'date'}, // 'date' | 'datetime' | 'time'
  timeFormat: {type: String, default: '24'}, // '12' | '24'
  placeholder: {type: String, default: ''},
  label: {type: String, default: null},
  disabled: {type: Boolean, default: false},
  minDate: {type: String, default: null},
  maxDate: {type: String, default: null},
  error: {type: String, default: null},
  required: {type: Boolean, default: false},
})

const emit = defineEmits(['update:modelValue'])

const requiredError = computed(() => {
  return props.required && !props.modelValue
      ? `${props.label || 'This field'} is required.`
      : null
})

const inputRef = ref(null)
let fp = null

function getConfigs() {
  const is12h = props.timeFormat === '12'
  return {
    date: {
      enableTime:  false,
      dateFormat:  'm/d/Y',
    },
    datetime: {
      enableTime:  true,
      dateFormat:  is12h ? 'm/d/Y G:i K' : 'm/d/Y H:i',
      time_24hr:   !is12h,
    },
    time: {
      enableTime:  true,
      noCalendar:  true,
      dateFormat:  is12h ? 'G:i K' : 'H:i',
      time_24hr:   !is12h,
    },
  }
}

onMounted(() => {
  const configs = getConfigs()
  fp = flatpickr(inputRef.value, {
    ...configs[props.mode],
    minDate: props.minDate || null,
    maxDate: props.maxDate || null,
    defaultDate: props.modelValue || null,
    onChange: ([date]) => {
      if (!date) return
      emit('update:modelValue', fp.formatDate(date, configs[props.mode].dateFormat))
    },
  })
})

watch(() => props.modelValue, (val) => {
  if (fp && val !== fp.input.value) {
    fp.setDate(val, false)
  }
})

watch(() => props.minDate, (val) => {
  fp?.set('minDate', val || null)
})

watch(() => props.maxDate, (val) => {
  fp?.set('maxDate', val || null)
})


onBeforeUnmount(() => {
  fp?.destroy()
})
</script>

<style lang="scss" scoped>
@use '../../styles/tokens' as *;

.ui-datepicker-wrap {
  display: flex;
  flex-direction: column;
  gap: $space-1;
}

.ui-datepicker__row {
  display: flex;
  align-items: center;
  border: 1px solid #ced4da;
  border-radius: 4px;
  overflow: hidden;
  height: 35px;
  background: #fff;
}

.ui-datepicker__label {
  padding: 0 8px;
  font-size: 13px;
  color: #475569;
  background: #f8f9fa;
  border-right: 1px solid #ced4da;
  height: 100%;
  display: flex;
  align-items: center;
  white-space: nowrap;
  flex-shrink: 0;
}

.ui-datepicker__input {
  border: none;
  outline: none;
  font-size: 13px;
  padding: 0 8px;
  height: 100%;
  color: #1e293b;
  background: #fff;
  min-width: 120px;
  width: 100%;
}

.ui-datepicker__input:disabled {
  background: #f8f9fa;
  cursor: not-allowed;
  opacity: 0.6;
}

.ui-datepicker__row--error {
  border-color: $color-danger;
}

.ui-datepicker__required {
  color: $color-danger;
}

.ui-datepicker__error {
  font-size: $text-xs;
  color: $color-danger;
  font-family: $font-sans;
}
</style>
