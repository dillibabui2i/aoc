<template>
    <div class="dose-age-chips">
        <span v-for="label in labels" :key="label" class="dose-age-chips__chip">{{ label }}</span>
    </div>
</template>

<script setup>
defineProps({
    labels: { type: Array, default: () => [] },
})
</script>

<style scoped lang="scss">
@use '../styles/tokens' as *;

.dose-age-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;

    &__chip {
        background: $chip-bg;
        color: $chip-text;
        border-radius: 12px;
        padding: 1px 9px;
        font-size: $text-sm;
        white-space: nowrap;
    }
}
</style>
