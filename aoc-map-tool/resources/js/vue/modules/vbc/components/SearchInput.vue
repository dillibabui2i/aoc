<template>
    <div class="vbc-search-input">
        <ui-input
            type="text"
            placeholder="Search"
            v-model="localValue"
        />
        <button
            v-if="localValue"
            type="button"
            class="vbc-search-input__clear"
            title="Clear search"
            @click="clear"
        >&times;</button>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { UiInput } from '@angels-of-care/ui'
import { debounce } from 'lodash'

const props = defineProps({
    modelValue: { type: String, default: '' },
    delay: { type: Number, default: 1500 },
})

const emit = defineEmits(['update:modelValue', 'search'])

const emitSearch = debounce(() => emit('search'), props.delay)

const localValue = computed({
    get: () => props.modelValue,
    set: (value) => {
        emit('update:modelValue', value)
        emitSearch()
    },
})

function clear() {
    emitSearch.cancel()
    emit('update:modelValue', '')
    emit('search')
}
</script>

<style scoped lang="scss">
@use '../styles/tokens' as *;

.vbc-search-input {
    position: relative;
    display: inline-flex;
}

.vbc-search-input__clear {
    position: absolute;
    top: 50%;
    right: 8px;
    transform: translateY(-50%);
    border: none;
    background: none;
    padding: 0;
    line-height: 1;
    font-size: 18px;
    color: $text-muted;
    cursor: pointer;

    &:hover {
        color: $text;
    }
}
</style>
