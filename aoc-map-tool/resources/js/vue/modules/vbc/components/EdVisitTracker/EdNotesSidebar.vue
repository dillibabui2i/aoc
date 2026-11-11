<template>
    <NotesSidebar :notes="notes" />
</template>

<script setup>
// PD-5800: the ED Visit Activity Log renders exactly like the Hospitalization Record's — same shared
// notes endpoint and `commented_at` / `author_name` payload — so it is the same component.
import NotesSidebar from '@/modules/vbc/components/HospitalizationTracker/NotesSidebar.vue'

defineProps({
    notes: { type: Array, default: () => [] }, // [{ id, notes, author_name, commented_at }]
})
</script>
