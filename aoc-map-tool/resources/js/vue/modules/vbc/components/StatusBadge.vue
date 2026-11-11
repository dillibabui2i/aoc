<template>
    <span class="status-badge" :class="{ 'status-badge--inactive': status === 'Inactive' }">{{ status }}</span>
</template>

<script setup>
defineProps({
    status: { type: String, required: true },
})
</script>

<style scoped lang="scss">
@use '../styles/tokens' as *;

.status-badge {
    display: inline-block;
    background: $badge-active-bg;
    color: $badge-active-text;
    border: 1px solid transparent;
    border-radius: 12px;
    padding: 1px 12px;
    font-size: $text-sm;

    &--inactive {
        background: $badge-inactive-bg;
        color: $badge-inactive-text;
        border-color: $badge-inactive-border;
    }
}
</style>
