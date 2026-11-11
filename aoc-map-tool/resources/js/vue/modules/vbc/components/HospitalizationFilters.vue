<template>
    <div class="vbc-filters">

        <div class="vbc-filters__row">

            <ui-select v-model="values.export_type">
                <option value="">Select Export Type</option>
                <option v-for="opt in options.exportTypes" :key="opt.value" :value="opt.value">
                    {{ opt.text }}
                </option>
            </ui-select>

            <ui-select v-for="item in filters"
                       :key="item.key"
                       :multiple="true"
                       :disabled="isDisabled(item)"
                       :placeholder="item.label"
                       v-model="values[item.key]">
                <option v-for="opt in optionsFor(item)" :key="opt.value" :value="opt.value">
                    {{ opt.text }}
                </option>
            </ui-select>

            <div class="vbc-filters__date-range">
                <ui-date-timepicker
                    v-model="values.admission_date_from"
                    mode="date"
                    label="From"
                    placeholder="Admission date"
                />
                <ui-date-timepicker
                    v-model="values.admission_date_to"
                    mode="date"
                    label="To"
                    placeholder="Admission date"
                    :min-date="values.admission_date_from"
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
import { onMounted, watch } from 'vue'
import { useAllHospitalizationFiltersStore } from '@/modules/vbc/store/filters.store.js'
import { storeToRefs } from 'pinia'
import { UiSelect, UiButton, UiDateTimepicker } from '@angels-of-care/ui';

const emit = defineEmits(['search', 'clear'])

const filters = [
    {key: 'state', opts: 'states', label: 'Select State'},
    {key: 'branch_ids', opts: 'branches', label: 'Select Branch'},
    {key: 'region_ids', opts: 'regions', label: 'Select Region'},
    {key: 'assignee_ids', opts: 'assignees', label: 'Select Assignee'},
    {key: 'statuses', opts: 'statuses', label: 'Select Status'},
    {key: 'levels', opts: 'levels', label: 'Select Level'},
    {key: 'facility_names', opts: 'facilityNames', label: 'Select Facility Name'},
];
const filtersStore = useAllHospitalizationFiltersStore()
const { options, values } = storeToRefs(filtersStore)

// Branch/Region are narrowed to the selected State(s) client-side — filter-data already returns the full lists.
const STATE_DEPENDENT_KEYS = ['branch_ids', 'region_ids']

function selectedStates() {
    const selected = values.value.state
    return Array.isArray(selected) ? selected : [selected].filter(Boolean)
}

function optionsFor(item) {
    const opts = options.value[item.opts] ?? []
    if (!STATE_DEPENDENT_KEYS.includes(item.key)) {
        return opts
    }

    const states = selectedStates()
    if (states.length === 0) {
        return item.key === 'region_ids' ? [] : opts
    }

    return opts.filter((opt) => states.includes(opt.state))
}

// Region is blocked until a State with at least one region is selected.
function isDisabled(item) {
    if (item.key !== 'region_ids') {
        return false
    }

    return selectedStates().length === 0 || optionsFor(item).length === 0
}

watch(
    () => [values.value.state, options.value.regions],
    () => {
        if (isDisabled({ key: 'region_ids', opts: 'regions' })) {
            values.value.region_ids = ''
        }
    }
)

function getFilteredData() {
    emit('search', { ...values.value })
}

function onClear() {
    filtersStore.reset()
    emit('clear')
}

onMounted(() => filtersStore.fetchOptions())
</script>
