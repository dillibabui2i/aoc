<template>
    <div class="hospitalized-client-search">
        <ui-input
            v-model="query"
            label="Hospitalized Client Name"
            type="text"
            placeholder="Search for Hospitalized Client"
            @input="onInput"
        />

        <ul v-if="results.length" class="hospitalized-client-search__results">
            <li
                v-for="client in results"
                :key="client.patient_id"
                class="hospitalized-client-search__result"
                @click="select(client)"
            >
                {{ client.full_name }}, ID {{ client.patient_account }} -
                Hospitalization Tracker ID # {{ client.hospitalization_tracker_id }}
            </li>
        </ul>
    </div>
</template>

<script setup>
// Care Coordination creation, stage 1 (AC4/AC5): only offers clients with an
// "active" hospitalization tracker (broader than "In Progress" — see
// HospitalizationTrackerStatusEnum::activeCareCoordinationStatuses() on the
// backend). Self-contained rather than a reuse of ClientSearchAutocomplete.vue,
// which is tightly coupled to hospitalizationTracker.store.js and doesn't show
// a tracker id per result.
import { ref } from 'vue'
import { debounce } from 'lodash'
import { UiInput } from '@angels-of-care/ui'
import { careCoordinationActivitiesService } from '@/modules/vbc/services/Dashboard/careCoordinationActivities.service.js'

const emit = defineEmits(['select'])

const query = ref('')
const results = ref([])

const onInput = debounce(async () => {
    results.value = await careCoordinationActivitiesService.searchEligibleClients(
        query.value,
    )
}, 300)

function select(client) {
    query.value = ''
    results.value = []
    emit('select', client)
}
</script>

<style scoped lang="scss">
@use '@/modules/vbc/styles/tokens' as *;

/* Rendered in normal flow, not `position: absolute` over the input: this is the only content on the
   modal's "search" stage (the coordination form isn't mounted yet), so there's nothing for it to float
   over. Floating it used to rely on `.modal-body`'s own height to contain it for visual purposes only —
   but a global legacy rule (`.modal-body { overflow: auto }` in resources/css/extra_style.css, applied to
   every Bootstrap modal on the site) clips any absolutely-positioned child to `.modal-body`'s *natural*
   (shrink-to-fit) height, which is just the search input's height since an absolutely-positioned child
   doesn't count toward it — so the whole list was rendered but only visible through a cramped scrollbar.
   Normal flow avoids the clip entirely and lets the modal grow to fit the list, matching the Figma
   reference layout. */
.hospitalized-client-search__results {
    margin: 4px 0 0;
    padding: 0;
    list-style: none;
    border-top: 1px solid $border;
    max-height: 220px;
    overflow-y: auto;
}
.hospitalized-client-search__result {
    padding: 8px 4px;
    font-size: $text-sm;
    cursor: pointer;
    border-bottom: 1px solid $border;
}
.hospitalized-client-search__result:hover {
    background: $surface-muted;
}
</style>
