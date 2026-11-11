<template>
    <div class="icd-code-search">
        <ui-input
            v-model="query"
            label="Diagnosis Code"
            type="text"
            placeholder="Start typing an ICD-10 code or description..."
            :error="error"
            @input="onInput"
            @focus="focused = true"
            @blur="onBlur"
        />

        <ul v-if="hintVisible" class="icd-code-search__results">
            <li class="icd-code-search__result icd-code-search__result--empty">
                ( keep typing — {{ MIN_SEARCH_LENGTH }} characters minimum )
            </li>
        </ul>

        <ul v-else-if="resultsVisible" class="icd-code-search__results">
            <li v-if="loading" class="icd-code-search__result icd-code-search__result--empty">
                ( searching... )
            </li>
            <template v-else>
                <li
                    v-for="result in results"
                    :key="result.code"
                    class="icd-code-search__result"
                    @mousedown.prevent="selectResult(result)"
                >
                    {{ result.code }} — {{ result.description }}
                </li>
                <li
                    v-if="!results.length"
                    class="icd-code-search__result icd-code-search__result--empty"
                >
                    ( no matching ICD-10 codes found )
                </li>
            </template>
        </ul>
    </div>
</template>

<script setup>
// ICD-10 typeahead (PD-3498's technical spec) against the NLM ClinicalTables search API, proxied through
// our own backend (hospitalization.diagnosis-codes.search). Modeled on ClientSearchAutocomplete.vue:
// debounced 300ms, same 3-character minimum shown as a hint rather than leaving the field looking
// unresponsive.
import { ref, computed, watch } from 'vue'
import { debounce } from 'lodash'
import { UiInput } from '@angels-of-care/ui'
import { HospitalizationTrackerService } from '@/modules/vbc/services/hospitalizationTracker.service.js'

const MIN_SEARCH_LENGTH = 3

const props = defineProps({
    code: { type: String, default: '' },
    description: { type: String, default: '' },
    error: { type: String, default: null },
})

const emit = defineEmits(['update:code', 'update:description'])

const query = ref(
    props.code ? `${props.code} — ${props.description}` : '',
)
const results = ref([])
const loading = ref(false)
const searchPerformed = ref(false)
const focused = ref(false)

// This component is mounted once (DiagnosisDetailsModal.vue isn't re-created per open), so `query`'s
// initial value above only ever applies to the very first time the modal is opened. Every subsequent
// prefill — most importantly opening "Edit" on an existing diagnosis — sets `code`/`description` from
// outside (DiagnosisDetailsModal's `watch` on the modal opening), so `query` must resync to match
// whenever that happens, or the input would show blank text despite the form already holding a value.
watch(
    () => props.code,
    (code) => {
        query.value = code ? `${code} — ${props.description}` : ''
    },
)

const hintVisible = computed(() => {
    const length = query.value.trim().length

    return focused.value && length > 0 && length < MIN_SEARCH_LENGTH
})

const resultsVisible = computed(
    () => focused.value && searchPerformed.value && query.value.trim().length >= MIN_SEARCH_LENGTH,
)

const onInput = debounce(async () => {
    // Typing again after a selection means the selection no longer matches — clear it rather than
    // silently keeping a stale code/description pair the visible text doesn't agree with.
    emit('update:code', '')
    emit('update:description', '')

    const term = query.value.trim()
    if (term.length < MIN_SEARCH_LENGTH) {
        searchPerformed.value = false
        results.value = []
        return
    }

    loading.value = true
    searchPerformed.value = true
    try {
        results.value = await HospitalizationTrackerService.searchDiagnosisCodes(term)
    } finally {
        loading.value = false
    }
}, 300)

function selectResult(result) {
    query.value = `${result.code} — ${result.description}`
    searchPerformed.value = false
    emit('update:code', result.code)
    emit('update:description', result.description)
}

function onBlur() {
    // Delayed so a click on a result (mousedown, above) fires before the list disappears.
    window.setTimeout(() => {
        focused.value = false
    }, 150)
}
</script>

<style scoped lang="scss">
@use '@/modules/vbc/styles/tokens' as *;

.icd-code-search {
    position: relative;
}
.icd-code-search__results {
    position: absolute;
    z-index: 10;
    top: 100%;
    left: 0;
    width: 100%;
    margin: 2px 0 0;
    padding: 4px 0;
    list-style: none;
    background: #fff;
    border: 1px solid $border;
    border-radius: 4px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    max-height: 220px;
    overflow-y: auto;
}
.icd-code-search__result {
    padding: 7px 14px;
    font-size: $text-base;
    cursor: pointer;
}
.icd-code-search__result:hover {
    background: $surface-muted;
}
.icd-code-search__result--empty {
    color: $text-muted;
    cursor: default;
}
.icd-code-search__result--empty:hover {
    background: none;
}
</style>
