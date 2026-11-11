<template>
    <div class="vbc-filters">
        <div class="vbc-filters__row">
            <label class="vbc-filters__me-mode">
                <input v-model="values.me_mode" type="checkbox" />
                Me Mode
            </label>

            <ui-select
                v-for="item in filters"
                :key="item.key"
                v-model="values[item.key]"
                :multiple="true"
                :placeholder="item.label"
            >
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

            <ui-button @click="getFilteredData">Search</ui-button>
            <ui-button variant="primary" @click="onClear">Clear All</ui-button>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { UiSelect, UiButton, UiDateTimepicker } from '@angels-of-care/ui'
import { useImmunizationBucketFiltersStore } from '@/modules/vbc/store/Dashboard/immunizationBucketFilters.store.js'
import { useStateDependentOptions } from '@/modules/vbc/composables/useStateDependentOptions.js'

const emit = defineEmits(['search', 'clear'])

const filters = [
    { key: 'vaccine_cvx_codes', opts: 'vaccines', label: 'Select Vaccine Name' },
    { key: 'state', opts: 'states', label: 'Select State' },
    { key: 'branch_ids', opts: 'branches', label: 'Select Branch' },
    { key: 'outcomes', opts: 'outcomes', label: 'Select Outcome' },
    { key: 'payers', opts: 'payers', label: 'Select Payer' },
]

const filtersStore = useImmunizationBucketFiltersStore()
const { options, values } = storeToRefs(filtersStore)

// This bucket has no Region filter; `regionKey` only has to be a key no item uses.
const { optionsFor } = useStateDependentOptions(values, options, {
    stateKey: 'state',
    branchKey: 'branch_ids',
    regionKey: 'region',
})

function getFilteredData() {
    emit('search', { ...values.value })
}

function onClear() {
    filtersStore.reset()
    emit('clear')
}

onMounted(() => filtersStore.fetchOptions())
</script>
