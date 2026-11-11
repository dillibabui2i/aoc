<template>
  <teleport to="body">
    <div v-if="modelValue" class="modal-backdrop fade show"></div>

    <div
        v-if="modelValue"
        class="modal fade show"
        :class="cssClass"
        role="dialog"
        :aria-labelledby="`${id}-title`"
        aria-modal="true"
        style="display: block"
        @mousedown.self="onBackdropClick"
    >
      <div class="modal-dialog modal-dialog-centered" :class="sizeClass" role="document">
        <div class="modal-content shadow">

          <div class="modal-header">
            <h5 class="modal-title" :id="`${id}-title`">{{ title }}</h5>

            <div v-if="$slots.extraHeader" class="extra-header-cnt">
              <slot name="extraHeader"/>
            </div>

            <button
                v-if="showClose"
                type="button"
                class="close"
                aria-label="Close"
                @click="close"
            >
              <span aria-hidden="true">&times;</span>
            </button>
          </div>

          <div class="modal-body">
            <slot/>
          </div>

          <div v-if="$slots.footer" class="modal-footer">
            <slot name="footer"/>
          </div>

        </div>
      </div>
    </div>
  </teleport>
</template>

<script setup>
import { watch } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title:      { type: String,  required: true },
  size:       { type: String,  default: null },   // 'w60' | 'w70' | 'w80' | 'modal-lg' | 'modal-sm'
  cssClass:   { type: String,  default: null },
  showClose:  { type: Boolean, default: true },
  static:     { type: Boolean, default: false },  // static backdrop
})

const emit = defineEmits(['update:modelValue'])

const id = `modal-${Math.random().toString(36).slice(2, 7)}`

const sizeClass = props.size ?? ''

function close() {
  emit('update:modelValue', false)
}

function onBackdropClick() {
  if (!props.static) close()
}

watch(() => props.modelValue, (val) => {
  document.body.style.overflow = val ? 'hidden' : ''
})
</script>

<style scoped>
.modal-content {
  padding: 15px;
}
.extra-header-cnt {
  display: flex;
  flex: auto;
  align-items: center;
}
.modal-header {
  border-bottom: 1px solid #ddd;
  padding-bottom: 5px;
  align-items: center;
}
.modal-footer {
  border-top: 0;
}
.w60 { max-width: 60% !important; }
.w70 { max-width: 70% !important; }
.w80 { max-width: 80% !important; }
</style>
