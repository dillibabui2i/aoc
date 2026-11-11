<template>
    <div class="vbc-filters">
        <div class="vbc-filters__row">
            <!-- Opens the "ED Visit Record" modal (PD-3983 AC1) — its own module/store, not the
                 Hospitalization Record form: that form always opens an IP encounter
                 (HospitalizationWriteRepository::createEncounterForPatient), so a record created
                 from here would never show up in this ED-only bucket. -->
            <a
                title="New ED Visit Record"
                href="javascript:void(0)"
                class="vbc-filters__add-btn"
                @click="edTrackerStore.openCreateModal()"
                ><i class="fa fa-plus-circle"></i
            ></a>

            <label class="vbc-filters__me-mode">
                <input v-model="values.me_mode" type="checkbox" />
                Me Mode
            </label>

            <ui-select
                v-for="item in filters"
                :key="item.key"
                v-model="values[item.key]"
                :multiple="true"
                :disabled="isDisabled(item)"
                :placeholder="item.label"
            >
                <option
                    v-for="opt in optionsFor(item)"
                    :key="opt.value"
                    :value="opt.value"
                >
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
import { storeToRefs } from 'pinia'
import { UiSelect, UiButton, UiDateTimepicker } from '@angels-of-care/ui'
import { useEdVisitsFiltersStore } from '@/modules/vbc/store/Dashboard/edVisitsFilters.store.js'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'
import { useStateDependentOptions } from '@/modules/vbc/composables/useStateDependentOptions.js'

const emit = defineEmits(['search', 'clear'])

const edTrackerStore = useEdVisitTracker()

const filters = [
    { key: 'state', opts: 'states', label: 'Select State' },
    { key: 'branch', opts: 'branches', label: 'Select Branch' },
    { key: 'region', opts: 'regions', label: 'Select Region' },
    { key: 'assignee', opts: 'assignees', label: 'Select Assignee' },
    { key: 'status', opts: 'statuses', label: 'Select Status' },
]

const filtersStore = useEdVisitsFiltersStore()
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

watch(
    () => values.value.date_from,
    (from) => {
        if (values.value.date_to && from > values.value.date_to) {
            values.value.date_to = ''
        }
    },
)
</script>
