<template>
    <div class="contacts-tab">
        <ui-button
            v-if="store.tracker?.status === 'In Progress'"
            class="contacts-tab__add"
            @click="store.openAddContact()"
        >
            Add Contact
        </ui-button>

        <ui-table
            :columns="columns"
            :rows="sortedContacts"
            :total="contacts.length"
            :page="1"
            :per-page="contacts.length || 1"
            :sort-by="sortBy"
            :sort-dir="sortDir"
            @update:sort-by="sortBy = $event"
            @update:sort-dir="sortDir = $event"
        >
            <template #cell-phoneNumber="{ row }">
                {{ formatUsPhoneDisplay(row.phoneNumber) }}
            </template>
            <template #cell-action="{ row }">
                <a
                    href="javascript:void(0)"
                    @click="store.openEditContact(row)"
                >
                    <i class="fa fa-calendar" aria-hidden="true"></i>
                </a>
            </template>
        </ui-table>

        <EdContactDetailsModal />
    </div>
</template>

<script setup>
// ED Visit Contacts tab (PD-3655, AC2/AC5/AC6) — ED copy of HospitalizationTracker/ContactsTab.vue. "Add Contact" only shows while the
// tracker is In Progress; the calendar icon always opens the modal, which
// itself decides editable vs. read-only (AC5/AC6).
import { computed, ref } from 'vue'
import { UiTable, UiButton } from '@angels-of-care/ui'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'
import { formatUsPhoneDisplay } from '@/modules/vbc/services/hospitalizationTracker.service.js'
import EdContactDetailsModal from './EdContactDetailsModal.vue'

const store = useEdVisitTracker()

const contacts = computed(() => store.tracker?.contacts ?? [])

const columns = [
    { key: 'contactName', label: 'Contact Name', sortable: true },
    { key: 'relationship', label: 'Relationship', sortable: true },
    { key: 'phoneNumber', label: 'Phone Number', sortable: true },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'action', label: 'Action' },
]

// The whole list already lives in `tracker.contacts` (no separate paginated fetch — see the tab-switch
// discussion), so sorting happens client-side against it, same as CareCoordinationTab.vue's sortedItems.
const sortBy = ref(null)
const sortDir = ref('asc')

const sortedContacts = computed(() => {
    if (!sortBy.value) return contacts.value

    const dir = sortDir.value === 'desc' ? -1 : 1

    return [...contacts.value].sort((a, b) => {
        const valueA = (a[sortBy.value] ?? '').toString().toLowerCase()
        const valueB = (b[sortBy.value] ?? '').toString().toLowerCase()

        if (valueA < valueB) return -1 * dir
        if (valueA > valueB) return 1 * dir
        return 0
    })
})
</script>

<style scoped>
.contacts-tab__add {
    margin-bottom: 12px;
}
</style>
