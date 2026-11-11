<template>
    <div class="immunization-schedule">
        <div class="immunization-schedule__title">
            <h1 class="h3 mb-2 text-gray-800">Manage Immunization Schedule</h1>
            <!-- Export is implemented by PD-4547; the icon stays visible and inert until then -->
            <button type="button" class="immunization-schedule__icon-btn" title="Export Data">
                <i class="fa fa-download"></i>
            </button>
        </div>

        <ImmunizationScheduleFilters @search="store.applyFilters" @clear="store.resetFilters" />

        <div v-if="store.error">{{ store.error }}</div>
        <ui-table
            v-else
            :columns="columns"
            :rows="store.list"
            :loading="store.loading"
            :total="store.total"
            :page="store.page"
            :per-page="store.perPage"
            :sort-by="store.sortBy"
            :sort-dir="store.sortDir"
            :show-per-page="false"
            @update:page="store.setPage"
            @update:sort-by="store.setSortBy"
            @update:sort-dir="store.setSortDir"
        >
            <template #toolbar-search>
                <label class="immunization-schedule__per-page">
                    Show
                    <select :value="store.perPage" @change="store.setPerPage(Number($event.target.value))">
                        <option v-for="n in PER_PAGE_OPTIONS" :key="n" :value="n">{{ n }}</option>
                    </select>
                    Entries
                </label>
                <SearchInput v-model="store.filters.search" @search="store.applyFilters" />
            </template>

            <template #cell-dose_age_targets="{ row }">
                <DoseAgeChips :labels="row.dose_age_targets" />
            </template>

            <template #cell-state="{ row }">
                <span class="immunization-schedule__state">{{ row.state }}</span>
            </template>

            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>

            <template #cell-actions="{ row }">
                <button
                    type="button"
                    class="immunization-schedule__icon-btn"
                    title="Edit Immunization Schedule"
                    @click="store.openEdit(row.id)"
                >
                    <i class="fa fa-calendar"></i>
                </button>
            </template>
        </ui-table>

        <ImmunizationScheduleModal />
    </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { UiTable } from '@angels-of-care/ui'
import { useImmunizationSchedule, PER_PAGE_OPTIONS } from '@/modules/vbc/store/immunizationSchedule.store.js'
import DoseAgeChips from '@/modules/vbc/components/DoseAgeChips.vue'
import StatusBadge from '@/modules/vbc/components/StatusBadge.vue'
import SearchInput from '@/modules/vbc/components/SearchInput.vue'
import ImmunizationScheduleFilters from '@/modules/vbc/components/ImmunizationScheduleFilters.vue'
import ImmunizationScheduleModal from '@/modules/vbc/components/ImmunizationScheduleModal.vue'

const store = useImmunizationSchedule()

const columns = [
    { key: 'vaccine_category', label: 'Vaccine Category', sortable: true },
    { key: 'vaccine_name', label: 'Vaccine Name', sortable: true },
    { key: 'dose_age_targets', label: 'Dose Age Targets', sortable: false },
    { key: 'state', label: 'States', sortable: false },
    { key: 'status', label: 'Status', sortable: false },
    { key: 'actions', label: 'Action', sortable: false },
]

onMounted(() => store.fetchAll())
</script>

<style scoped lang="scss">
@use '../styles/tokens' as *;

.immunization-schedule {
    font-family: $font;
    text-align: left;

    &__title {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    &__icon-btn {
        background: none;
        border: 0;
        padding: 0;
        color: $primary;
        cursor: pointer;
        line-height: 1;

        .fa {
            font-size: 22px;
        }
    }

    &__per-page {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin: 0;
        font-size: $text-base;

        select {
            height: $control-height;
            border: 1px solid $border;
            border-radius: 4px;
            padding: 0 8px;
            background: $surface;
        }
    }

    &__state {
        display: inline-block;
        background: $chip-bg;
        color: $chip-text;
        border-radius: 12px;
        padding: 1px 9px;
        font-size: $text-sm;
    }
}
</style>
