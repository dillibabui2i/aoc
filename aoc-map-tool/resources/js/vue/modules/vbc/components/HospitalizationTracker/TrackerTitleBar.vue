<template>
    <div class="tracker-title-bar">
        <div class="tracker-title-bar__text">
            <span>{{ titleText }}</span>
            <template v-if="isOpen">
                <i
                    v-if="isNotesEligible(store.tracker?.status)"
                    class="fa fa-sticky-note-o tracker-title-bar__icon tracker-title-bar__icon--clickable"
                    aria-hidden="true"
                    title="Notes"
                    @click="store.openCreateNote()"
                ></i>
                <i
                    class="fa fa-user tracker-title-bar__icon tracker-title-bar__icon--clickable"
                    aria-hidden="true"
                    title="Client Profile"
                    @click="store.openClientProfile()"
                ></i>
            </template>
        </div>
        <span class="tracker-title-bar__status">{{ statusLabel }}</span>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'
import { isNotesEligible } from '@/modules/vbc/services/hospitalizationTracker.service.js'

const store = useHospitalizationTracker()

const isOpen = computed(() => store.wizardStep === 'open')

const titleText = computed(() => {
    if (!isOpen.value || !store.tracker) return 'Hospitalization Record'
    const name = store.tracker.patient_name || ''
    const level = store.tracker.level_label || ''
    return `Hospitalization Record \\ ${name} \\ ${level}`
})

const statusLabel = computed(() => store.tracker?.status ?? 'New')
</script>

<style scoped lang="scss">
@use '@/modules/vbc/styles/tokens' as *;

.tracker-title-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
}
.tracker-title-bar__text {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: $text-title;
    font-weight: 600;
    color: $text;
}
.tracker-title-bar__icon {
    font-size: 18px;
    color: #495057;
}
.tracker-title-bar__icon--clickable {
    cursor: pointer;
}
.tracker-title-bar__status {
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
