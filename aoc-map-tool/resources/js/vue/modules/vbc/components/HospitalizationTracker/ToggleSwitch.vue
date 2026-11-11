<template>
    <label
        class="toggle-switch"
        :class="{ 'toggle-switch--disabled': disabled }"
    >
        <input
            type="checkbox"
            class="toggle-switch__input"
            :checked="modelValue"
            :disabled="disabled"
            @change="$emit('update:modelValue', $event.target.checked)"
        />
        <span class="toggle-switch__track">
            <span class="toggle-switch__thumb"></span>
        </span>
    </label>
</template>

<script setup>
defineProps({
    modelValue: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
})
defineEmits(['update:modelValue'])
</script>

<style scoped>
.toggle-switch {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
}
.toggle-switch--disabled {
    cursor: not-allowed;
    opacity: 0.6;
}
.toggle-switch__input {
    position: absolute;
    opacity: 0;
    width: 1px;
    height: 1px;
}
.toggle-switch__track {
    display: inline-block;
    width: 36px;
    height: 20px;
    border-radius: 999px;
    background: #cbd5e1;
    position: relative;
    transition: background 0.15s ease;
}
.toggle-switch__input:checked + .toggle-switch__track {
    background: #2563eb;
}
.toggle-switch__thumb {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #fff;
    transition: transform 0.15s ease;
}
.toggle-switch__input:checked + .toggle-switch__track .toggle-switch__thumb {
    transform: translateX(16px);
}
</style>
