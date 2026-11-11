<template>
    <div class="vbc-filters">

        <div class="vbc-filters__row">

            <ui-select v-for="item in filters"
                       :key="item.key"
                       :multiple="true"
                       :placeholder="item.label"
                       v-model="values[item.key]">
                <option v-for="opt in optionsFor(item)" :key="opt.value" :value="opt.value">
                    {{ opt.text }}
                </option>
            </ui-select>

            <div class="vbc-filters__date-range">
                <ui-date-timepicker
                    v-model="values.earliest_date_from"
                    mode="date"
                    label="From"
                    placeholder="Earliest date"
                />
                <ui-date-timepicker
                    v-model="values.earliest_date_to"
                    mode="date"
                    label="To"
                    placeholder="Earliest date"
                    :min-date="values.earliest_date_from"
                />
            </div>

            <ui-button @click="getFilteredData">
                Search
            </ui-button>
            <ui-button @click="onClear">
                Clear All
            </ui-button>

        </div>

    </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAllImmunizationFiltersStore } from '@/modules/vbc/store/immunizationFilters.store.js'
import { storeToRefs } from 'pinia'
import { UiSelect, UiButton, UiDateTimepicker } from '@angels-of-care/ui';

const emit = defineEmits(['search', 'clear'])

const filters = [
    {key: 'vaccine_cvx_codes', opts: 'vaccines', label: 'Select Vaccine Name'},
    {key: 'state', opts: 'states', label: 'Select State'},
    {key: 'branch_ids', opts: 'branches', label: 'Select Branch'},
    {key: 'statuses', opts: 'statuses', label: 'Select Status'},
    {key: 'payers', opts: 'payers', label: 'Select Payer'},
];
const filtersStore = useAllImmunizationFiltersStore()
const { options, values } = storeToRefs(filtersStore)

function selectedStates() {
    const selected = values.value.state
    return Array.isArray(selected) ? selected : [selected].filter(Boolean)
}

// Branches are narrowed to the selected State(s) client-side — the shared filters payload returns the full list.
function optionsFor(item) {
    const opts = options.value[item.opts] ?? []
    const states = selectedStates()
    if (item.key !== 'branch_ids' || states.length === 0) {
        return opts
    }

    return opts.filter((opt) => states.includes(opt.state))
}

function getFilteredData() {
    emit('search', { ...values.value })
}

function onClear() {
    filtersStore.reset()
    emit('clear')
}

onMounted(() => filtersStore.fetchOptions())
</script>
