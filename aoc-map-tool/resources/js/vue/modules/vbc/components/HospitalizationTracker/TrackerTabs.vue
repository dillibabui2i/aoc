<template>
    <div class="tracker-tabs">
        <div class="tracker-tabs__bar" role="tablist">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                role="tab"
                class="tracker-tabs__tab"
                :class="{ 'tracker-tabs__tab--active': tab.key === modelValue }"
                :aria-selected="tab.key === modelValue"
                @click="$emit('update:modelValue', tab.key)"
            >
                {{ tab.label }}
            </button>
        </div>

        <div
            v-if="activeSubTabs.length"
            class="tracker-tabs__bar tracker-tabs__bar--sub"
            role="tablist"
        >
            <button
                v-for="sub in activeSubTabs"
                :key="sub.key"
                type="button"
                role="tab"
                class="tracker-tabs__tab"
                :class="{
                    'tracker-tabs__tab--active': sub.key === activeSubTabKey,
                }"
                :aria-selected="sub.key === activeSubTabKey"
                @click="activeSubTabKey = sub.key"
            >
                {{ sub.label }}
            </button>
        </div>

        <div class="tracker-tabs__content">
            <slot :name="contentSlotKey" />
        </div>
    </div>
</template>

<script setup>
// Supports one optional level of sub-tabs per top-level tab entry
// (`tabs: [{ key, label, subTabs?: [{ key, label }] }]`), used by the
// "In Progress"/"Closed-after-In-Progress" nested tab layout (AC8). A tab
// with no `subTabs` behaves exactly as before — fully backward compatible.
import { ref, computed, watch } from 'vue'

const props = defineProps({
    tabs: { type: Array, required: true }, // [{ key, label, subTabs? }]
    modelValue: { type: String, required: true },
})
defineEmits(['update:modelValue'])

const activeTab = computed(() =>
    props.tabs.find((tab) => tab.key === props.modelValue),
)
const activeSubTabs = computed(() => activeTab.value?.subTabs ?? [])

const activeSubTabKey = ref(activeSubTabs.value[0]?.key ?? null)

watch(activeSubTabs, (subs) => {
    activeSubTabKey.value = subs[0]?.key ?? null
})

const contentSlotKey = computed(() => activeSubTabKey.value ?? props.modelValue)
</script>

<style scoped lang="scss">
@use '@/modules/vbc/styles/tokens' as *;

.tracker-tabs {
    display: flex;
    flex-direction: column;
}

/* Solid blue buttons in a thin frame, per the design — not the underline style
   the rest of the app uses. */
.tracker-tabs__bar {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    padding: 3px;
    margin-bottom: 10px;
}
.tracker-tabs__bar--sub {
    margin-top: -8px;
}

.tracker-tabs__tab {
    padding: 7px 14px;
    background: $tab-bg;
    border: 1px solid $tab-border;
    border-radius: 2px;
    font-family: $font;
    font-size: $text-sm;
    font-weight: 500;
    color: $tab-text;
    cursor: pointer;
}
.tracker-tabs__tab:hover {
    background: $primary-hover;
    border-color: $primary-hover-border;
}

/* Yellow rather than a heavier weight alone: the tabs are all the same blue, so
   colour is what makes the current one findable at a glance. */
.tracker-tabs__tab--active {
    color: $tab-text-active;
    font-weight: 700;
}
</style>
