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
                :key="client.clientId"
                class="hospitalized-client-search__result"
                @click="select(client)"
            >
                {{ client.fullName }}, ID {{ client.clientId }}
            </li>
        </ul>
    </div>
</template>

<script setup>
// Follow-up creation (AC4/AC5): offers any client with a non-Closed
// hospitalization tracker (broader than Care Coordination Activities'
// "In Progress"-only search — design.md Decision 2). Self-contained
// rather than reused from that story's own HospitalizedClientSearch.vue,
// since it calls a different service method with a different filter.
import { ref } from 'vue'
import { debounce } from 'lodash'
import { UiInput } from '@angels-of-care/ui'
import { HospitalizationTrackerService } from '@/modules/vbc/services/hospitalizationTracker.service.js'

const emit = defineEmits(['select'])

const query = ref('')
const results = ref([])

const onInput = debounce(async () => {
    results.value =
        await HospitalizationTrackerService.searchHospitalizedClientsForFollowUp(
            query.value,
        )
}, 300)

function select(client) {
    query.value = ''
    results.value = []
    emit('select', client)
}
</script>

<style scoped>
.hospitalized-client-search {
    position: relative;
}
.hospitalized-client-search__results {
    position: absolute;
    z-index: 10;
    top: 100%;
    left: 0;
    right: 0;
    margin: 2px 0 0;
    padding: 4px 0;
    list-style: none;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    max-height: 220px;
    overflow-y: auto;
}
.hospitalized-client-search__result {
    padding: 6px 12px;
    font-size: 13px;
    cursor: pointer;
}
.hospitalized-client-search__result:hover {
    background: #f1f5f9;
}
</style>
