import { watch } from 'vue'

// Narrows Branch/Region option lists to the selected State(s), and disables/clears Region until a
// State with at least one matching region is picked — same behavior as the All Hospitalizations page
// (components/HospitalizationFilters.vue). `values`/`options` are the filter store's refs; `stateKey`/
// `branchKey`/`regionKey` are the keys those filters live under in `values`/the `filters` config array.
export function useStateDependentOptions(values, options, { stateKey, branchKey, regionKey }) {
    const dependentKeys = [branchKey, regionKey]

    function selectedStates() {
        const selected = values.value[stateKey]
        return Array.isArray(selected) ? selected : [selected].filter(Boolean)
    }

    function optionsFor(item) {
        const opts = options.value[item.opts] ?? []
        if (!dependentKeys.includes(item.key)) {
            return opts
        }

        const states = selectedStates()
        if (states.length === 0) {
            return item.key === regionKey ? [] : opts
        }

        return opts.filter((opt) => states.includes(opt.state))
    }

    function isDisabled(item) {
        if (item.key !== regionKey) {
            return false
        }

        return selectedStates().length === 0 || optionsFor(item).length === 0
    }

    watch(
        () => [values.value[stateKey], options.value.regions],
        () => {
            if (isDisabled({ key: regionKey, opts: 'regions' })) {
                values.value[regionKey] = []
            }
        }
    )

    return { optionsFor, isDisabled }
}
