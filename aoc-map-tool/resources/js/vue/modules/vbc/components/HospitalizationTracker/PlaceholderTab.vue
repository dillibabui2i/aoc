<template>
    <div class="placeholder-tab">
        <p>{{ label }} will be available in a future update.</p>
    </div>
</template>

<script setup>
defineProps({
    label: { type: String, required: true },
})
</script>

<style scoped>
.placeholder-tab {
    padding: 24px 0;
    text-align: center;
    color: #94a3b8;
    font-size: 13px;
}
</style>
