<template>
    <div class="vbc-filters">
        <div class="vbc-filters__row">

            <a
                title="New Hospitalization Record"
                href="javascript:void(0)"
                class="vbc-filters__add-btn"
                @click="trackerStore.openCreateModal()"
            ><i class="fa fa-plus-circle"></i></a>

            <label class="vbc-filters__me-mode">
                <input type="checkbox" v-model="values.me_mode" />
                Me Mode
            </label>

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

            <!-- Date range -->
            <ui-date-timepicker
                v-model="values.date_from"
                mode="date"
                label="From"
                placeholder="Admission date"
            />
            <ui-date-timepicker
                v-model="values.date_to"
                mode="date"
                label="To"
                placeholder="Admission date"
                :min-date="values.date_from"
            />

            <ui-button @click="getFilteredData">Search</ui-button>
            <ui-button variant="primary" @click="onClear">Clear All</ui-button>
        </div>
    </div>
</template>

<script setup>
import { onMounted, watch } from 'vue'
import { useHospitalizationFiltersStore } from '@/modules/vbc/store/Dashboard/hospitalizationFilters.store.js'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'
import { useStateDependentOptions } from '@/modules/vbc/composables/useStateDependentOptions.js'
import { storeToRefs } from 'pinia'
import { UiSelect, UiButton, UiDateTimepicker } from '@angels-of-care/ui'

const emit = defineEmits(['search', 'clear'])

const trackerStore = useHospitalizationTracker()

const filters = [
    { key: 'state',    opts: 'states',    label: 'Select State'    },
    { key: 'branch',   opts: 'branches',  label: 'Select Branch'   },
    { key: 'region',   opts: 'regions',   label: 'Select Region'   },
    { key: 'assignee', opts: 'assignees', label: 'Select Assignee' },
    { key: 'status',   opts: 'statuses',  label: 'Select Status'   },
]

const filtersStore = useHospitalizationFiltersStore()
const { options, values } = storeToRefs(filtersStore)

const { optionsFor, isDisabled } = useStateDependentOptions(values, options, {
    stateKey: 'state',
    branchKey: 'branch',
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

watch(() => values.value.date_from, (from) => {
    if (values.value.date_to && from > values.value.date_to) {
        values.value.date_to = ''
    }
})
</script>
