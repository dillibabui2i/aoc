<template>
    <div class="vbc-filters">
        <div class="vbc-filters__row">
            <a
                title="Add Immunization Schedule"
                href="javascript:void(0)"
                class="vbc-filters__add-btn"
                @click="store.openAdd()"
            ><i class="fa fa-plus-circle"></i></a>

            <ui-select v-model="store.filters.vaccine_group_code" @update:model-value="onGroupChange">
                <option value="">Select Vaccine Group</option>
                <option v-for="opt in groupOptions" :key="opt.value" :value="opt.value">
                    {{ opt.text }}
                </option>
            </ui-select>

            <ui-select v-model="store.filters.vaccine_cvx_code">
                <option value="">Select Vaccine Name</option>
                <option v-for="opt in vaccineOptions" :key="opt.value" :value="opt.value">
                    {{ opt.text }}
                </option>
            </ui-select>

            <ui-select v-model="store.filters.states" :multiple="true" placeholder="Select State">
                <option v-for="opt in stateOptions" :key="opt.value" :value="opt.value">
                    {{ opt.text }}
                </option>
            </ui-select>

            <ui-select v-model="store.filters.status">
                <option value="">Select Status</option>
                <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                    {{ opt.text }}
                </option>
            </ui-select>

            <ui-button @click="emit('search')">Search</ui-button>
            <ui-button @click="emit('clear')">Clear All</ui-button>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { UiSelect, UiButton } from '@angels-of-care/ui'
import { toOptionList } from '@/modules/vbc/services/filters.service.js'
import { useImmunizationSchedule } from '@/modules/vbc/store/immunizationSchedule.store.js'

const emit = defineEmits(['search', 'clear'])

const store = useImmunizationSchedule()

const groupOptions = computed(() => toOptionList(store.options?.vaccineCategories))
const stateOptions = computed(() => toOptionList(store.options?.states))
const statusOptions = computed(() => toOptionList(store.options?.statuses))

// Vaccine Name is narrowed to the selected group (vaccines carry their `category`), like in the Add/Edit modal.
const vaccineOptions = computed(() => {
    const group = store.filters.vaccine_group_code
    const vaccines = store.options?.vaccines ?? []

    return toOptionList(
        group === '' ? vaccines : vaccines.filter((v) => String(v.category) === String(group)),
    )
})

// A vaccine of another group must not stay selected.
function onGroupChange() {
    store.filters.vaccine_cvx_code = ''
}

onMounted(() => store.loadOptions())
</script>
