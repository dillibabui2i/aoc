<template>
    <div class="ed-tracker-title-bar">
        <div class="ed-tracker-title-bar__text">
            <span>{{ titleText }}</span>
            <template v-if="isOpen">
                <i
                    v-if="isEdNotesEligible(store.tracker?.status)"
                    class="fa fa-sticky-note-o ed-tracker-title-bar__icon ed-tracker-title-bar__icon--clickable"
                    aria-hidden="true"
                    title="Notes"
                    @click="store.openCreateNote()"
                ></i>
                <i
                    class="fa fa-user ed-tracker-title-bar__icon ed-tracker-title-bar__icon--clickable"
                    aria-hidden="true"
                    title="Client Profile"
                    @click="store.openClientProfile()"
                ></i>
            </template>
        </div>
        <span class="ed-tracker-title-bar__status">{{ statusLabel }}</span>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'
import { isEdNotesEligible } from '@/modules/vbc/services/edVisitTracker.service.js'

const store = useEdVisitTracker()

const isOpen = computed(() => store.wizardStep === 'open')

// AC1: modal titled "ED Visit Record" (Requirements/Stakeholder direction — overrides AC1's literal
// "ED Visit" text).
const titleText = computed(() => {
    if (!isOpen.value || !store.tracker) return 'ED Visit Record'
    const name = store.tracker.patient_name || ''
    const level = store.tracker.level_label || ''
    return `ED Visit Record \\ ${name} \\ ${level}`
})

// AC2: "New" before a client is confirmed; the real tracker status afterwards (AC7: "In Progress").
const statusLabel = computed(() => store.tracker?.status ?? 'New')
</script>

<style scoped lang="scss">
@use '@/modules/vbc/styles/tokens' as *;

.ed-tracker-title-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
}
.ed-tracker-title-bar__text {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: $text-title;
    font-weight: 600;
    color: $text;
}
.ed-tracker-title-bar__icon {
    font-size: 18px;
    color: #495057;
}
.ed-tracker-title-bar__icon--clickable {
    cursor: pointer;
}
.ed-tracker-title-bar__status {
    flex: 0 0 auto;
    min-width: 130px;
    padding: 9px 16px;
    border-radius: 0;
    background: $primary;
    border: 1px solid $primary-border;
    color: #fff;
    font-family: $font;
    font-size: $text-base;
    font-weight: 400;
    text-align: center;
}
</style>
