import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import CurrentHospitalizationInfoTab from '@/modules/vbc/components/HospitalizationTracker/CurrentHospitalizationInfoTab.vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'
import { CURRENT_INFO_FIELDS } from '@/modules/vbc/config/hospitalizationTrackerFields.js'

const POST_DISCHARGE_FIELD_COUNT = CURRENT_INFO_FIELDS.filter(
    (f) => f.postDischargeOnly,
).length

describe('CurrentHospitalizationInfoTab', () => {
    let pinia

    beforeEach(() => {
        pinia = createTestingPinia({ stubActions: false, createSpy: vi.fn })
    })

    function mountComponent() {
        return mount(CurrentHospitalizationInfoTab, {
            global: { plugins: [pinia] },
        })
    }

    it('hides post-discharge fields before the tracker has been discharged', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.tracker = { tracker_id: 11123, status: 'In Progress' }
        await wrapper.vm.$nextTick()

        expect(wrapper.findAll('.current-info-tab__field')).toHaveLength(
            CURRENT_INFO_FIELDS.length - POST_DISCHARGE_FIELD_COUNT,
        )
    })

    it('renders every applicable field once Discharged (toggle off)', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.tracker = {
            tracker_id: 11123,
            status: 'Discharged',
            first_billable_shift_completed: false,
        }
        await wrapper.vm.$nextTick()

        // All post-discharge fields except the conditional date.
        expect(wrapper.findAll('.current-info-tab__field')).toHaveLength(
            CURRENT_INFO_FIELDS.length - 1,
        )
    })

    it('shows First billable shift date once its toggle is on', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.tracker = {
            tracker_id: 11123,
            status: 'Discharged',
            first_billable_shift_completed: true,
        }
        await wrapper.vm.$nextTick()

        expect(wrapper.findAll('.current-info-tab__field')).toHaveLength(
            CURRENT_INFO_FIELDS.length,
        )
    })
})
