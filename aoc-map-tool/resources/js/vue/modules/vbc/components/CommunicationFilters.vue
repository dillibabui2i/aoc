<template>
    <div class="vbc-filters">
        <div class="vbc-filters__row">
            <a
                title="Add Follow-up"
                href="javascript:void(0)"
                class="vbc-filters__add-btn"
                @click="followUpStore.openCreate(() => store.fetchAll())"
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

            <ui-button @click="getFilteredData">Search</ui-button>
            <ui-button variant="primary" @click="onClear">Clear All</ui-button>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { UiSelect, UiButton } from '@angels-of-care/ui'
import { useCommunicationsFiltersStore } from '@/modules/vbc/store/Dashboard/communicationFilters.store.js'
import { useCommunication } from '@/modules/vbc/store/Dashboard/communication.store.js'
import { useFollowUp } from '@/modules/vbc/store/followUp.store.js'
import { useStateDependentOptions } from '@/modules/vbc/composables/useStateDependentOptions.js'

const emit = defineEmits(['search', 'clear'])

const store = useCommunication()
const followUpStore = useFollowUp()

const filters = [
    { key: 'state', opts: 'states', label: 'Select State' },
    { key: 'branch', opts: 'branches', label: 'Select Branch' },
    { key: 'region', opts: 'regions', label: 'Select Region' },
    { key: 'assignee', opts: 'assignees', label: 'Select Assignee' },
    { key: 'status', opts: 'statuses', label: 'Select Status' },
]

const filtersStore = useCommunicationsFiltersStore()
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
</script>
