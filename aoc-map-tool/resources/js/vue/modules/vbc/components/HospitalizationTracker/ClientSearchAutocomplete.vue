<template>
    <div class="client-search">
        <ui-input
            v-if="store.wizardStep === 'search'"
            v-model="query"
            label="Client Name"
            type="text"
            placeholder="Start typing a client name..."
            @input="onInput"
        />
        <div v-else class="client-search__selected">
            <ui-input
                label="Client Name"
                type="text"
                :model-value="selectedDisplayValue"
                disabled
            />
            <!-- Until the tracker is created the choice is still reversible;
                 afterwards the tracker belongs to that client and can't move. -->
            <button
                v-if="canChangeClient"
                type="button"
                class="client-search__clear"
                title="Choose a different client"
                @click="store.searchAnotherClient()"
            >
                <i class="fa fa-times" aria-hidden="true"></i>
            </button>
        </div>

        <!-- Says why nothing is happening yet, instead of leaving the field
             looking unresponsive until the third character. -->
        <ul v-if="hintVisible" class="client-search__results">
            <li class="client-search__result client-search__result--empty">
                ( keep typing — {{ CLIENT_SEARCH_MIN_LENGTH }} characters
                minimum )
            </li>
        </ul>

        <ul v-else-if="resultsVisible" class="client-search__results">
            <li
                v-for="client in store.clientSearchResults"
                :key="client.patient_id"
                class="client-search__result"
                @click="selectClient(client)"
            >
                {{ client.patient_name }}, ID {{ client.client_id }}
            </li>

            <!-- Typing a name that matches nobody has to say so, otherwise the
                 dropdown just silently stays shut and looks broken. Not
                 selectable: only a real client can become the selection. -->
            <li
                v-if="!store.clientSearchResults.length"
                class="client-search__result client-search__result--empty"
            >
                ( no clients found )
            </li>
        </ul>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { debounce } from 'lodash'
import { UiInput } from '@angels-of-care/ui'
import {
    useHospitalizationTracker,
    CLIENT_SEARCH_MIN_LENGTH,
} from '@/modules/vbc/store/hospitalizationTracker.store.js'

const store = useHospitalizationTracker()
const query = ref('')

const canChangeClient = computed(() => store.wizardStep === 'confirmed')

// The client is `selectedClient` while the record is being created, and the
// tracker itself once it exists — opening a saved tracker never goes through the
// search, so falling back to it is what keeps the field filled in.
const selectedDisplayValue = computed(() => {
    const client = store.selectedClient ?? store.tracker
    if (!client?.patient_name) return ''

    return `${client.patient_name}, ID ${client.client_id}`
})

const resultsVisible = computed(
    () =>
        store.wizardStep === 'search' &&
        store.clientSearchPerformed &&
        !store.clientSearchLoading,
)

const hintVisible = computed(() => {
    const length = query.value.trim().length

    return (
        store.wizardStep === 'search' &&
        length > 0 &&
        length < CLIENT_SEARCH_MIN_LENGTH
    )
})

// Only a listed client can ever be "selected" (AC8/AC9) — the raw text
// input never itself becomes the value the rest of the form reads.
const onInput = debounce(() => {
    store.searchClients(query.value)
}, 300)

function selectClient(client) {
    query.value = ''
    store.selectClient(client)
}
</script>

<style scoped>
.client-search {
    position: relative;
}
.client-search__selected {
    position: relative;
}
/* Sits inside the field, vertically centred against the input (not its label). */
.client-search__clear {
    position: absolute;
    right: 8px;
    bottom: 9px;
    padding: 0 4px;
    border: none;
    background: none;
    color: #6c757d;
    line-height: 1;
    cursor: pointer;
}
.client-search__clear:hover {
    color: #212529;
}
/* Client names plus their account id are longer than the field is wide, so the
   list sizes to its content rather than wrapping every entry onto two lines. */
.client-search__results {
    position: absolute;
    z-index: 10;
    top: 100%;
    left: 0;
    min-width: 100%;
    width: max-content;
    max-width: 480px;
    overflow-x: hidden;
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
.client-search__result {
    padding: 7px 14px;
    font-size: 15px;
    white-space: nowrap;
    cursor: pointer;
}
.client-search__result:hover {
    background: #f1f5f9;
}
.client-search__result--empty {
    color: #6c757d;
    cursor: default;
}
.client-search__result--empty:hover {
    background: none;
}
</style>
