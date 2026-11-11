import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import OutcomeSubmitBar from '@/modules/vbc/components/HospitalizationTracker/OutcomeSubmitBar.vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

// `admission_at` is stored as `M/D/YYYY h:mm AM/PM` (12h clock).
function pastDateTime(hoursAgo) {
    const d = new Date(Date.now() - hoursAgo * 60 * 60 * 1000)
    const hours = d.getHours() % 12 || 12
    const meridiem = d.getHours() < 12 ? 'AM' : 'PM'
    return `${d.getMonth() + 1}/${d.getDate()}/${d.getFullYear()} ${hours}:${String(d.getMinutes()).padStart(2, '0')} ${meridiem}`
}

describe('OutcomeSubmitBar', () => {
    // Sets the tracker via Pinia's `initialState`, so it's already in place
    // at mount time — avoids relying on a post-mount reactivity tick.
    function mountComponent(tracker) {
        const pinia = createTestingPinia({
            stubActions: false,
            createSpy: vi.fn,
            initialState: { hospitalizationTracker: { tracker } },
        })
        const wrapper = mount(OutcomeSubmitBar, {
            global: { plugins: [pinia] },
        })
        const store = useHospitalizationTracker()
        return { wrapper, store }
    }

    it('shows In Progress/Closed options for an Open tracker', () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            status: 'Open',
        })
        const options = wrapper.findAll('option').map((o) => o.text())
        expect(options).toEqual(['In Progress', 'Closed'])
    })

    it('shows Discharge/Closed options for an In Progress tracker', () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            status: 'In Progress',
        })
        const options = wrapper.findAll('option').map((o) => o.text())
        expect(options).toEqual(['Discharge', 'Closed'])
    })

    it('renders nothing once the tracker is Closed', () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            status: 'Closed',
        })
        expect(wrapper.find('.outcome-bar').exists()).toBe(false)
    })

    it('shows the Reason field only once "Closed" is selected', async () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            status: 'Open',
        })
        expect(wrapper.findAll('select')).toHaveLength(1)

        await wrapper.find('select').setValue('Closed')

        const selects = wrapper.findAll('select')
        expect(selects).toHaveLength(2)
        const reasonOptions = selects[1].findAll('option').map((o) => o.text())
        expect(reasonOptions).toEqual([
            'ED Visit',
            'Opened by mistake',
            'Transfer',
        ])
    })

    it('calls store.submitOutcome with the selected outcome and reason', async () => {
        const { wrapper, store } = mountComponent({
            tracker_id: 1,
            status: 'Open',
        })

        await wrapper.find('select').setValue('Closed')
        await wrapper.findAll('select')[1].setValue('ED Visit')
        await wrapper.find('button').trigger('click')

        expect(store.submitOutcome).toHaveBeenCalledWith({
            outcome: 'Closed',
            reason: 'ED Visit',
        })
    })

    it('renders DischargeFields instead of the generic Submit when Discharge is selected', async () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            status: 'In Progress',
        })

        await wrapper.find('select').setValue('Discharge')

        expect(wrapper.find('.discharge-fields').exists()).toBe(true)
        expect(wrapper.find('.outcome-bar__controls button').exists()).toBe(
            false,
        )
    })

    it('shows Ready for Review option and fields for a Discharged tracker', async () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            status: 'Discharged',
        })
        const options = wrapper.findAll('option').map((o) => o.text())
        expect(options).toEqual(['Ready for Review'])

        await wrapper.find('select').setValue('Ready for Review')
        expect(wrapper.find('.review-fields').exists()).toBe(true)
    })

    it('shows Ready for Next Steps option and fields for a Ready for Review tracker', async () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            status: 'Ready for Review',
        })
        const options = wrapper.findAll('option').map((o) => o.text())
        expect(options).toEqual(['Ready for Next Steps'])

        await wrapper.find('select').setValue('Ready for Next Steps')
        expect(wrapper.find('.next-steps-fields').exists()).toBe(true)
    })

    it('shows Completed option and fields for a Ready for Next Steps tracker', async () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            status: 'Ready for Next Steps',
        })
        const options = wrapper.findAll('option').map((o) => o.text())
        expect(options).toEqual(['Completed'])

        await wrapper.find('select').setValue('Completed')
        expect(wrapper.find('.completed-fields').exists()).toBe(true)
    })

    it('renders nothing once the tracker is Completed', () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            status: 'Completed',
        })
        expect(wrapper.find('.outcome-bar').exists()).toBe(false)
    })

    it('renders TransferFields instead of the generic Submit when Closed + Transfer is selected', async () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            status: 'Open',
        })

        await wrapper.find('select').setValue('Closed')
        await wrapper.findAll('select')[1].setValue('Transfer')

        expect(wrapper.find('.transfer-fields').exists()).toBe(true)
        expect(wrapper.find('.outcome-bar__controls button').exists()).toBe(
            false,
        )
    })

    it('shows the admission-age banner when the admission date is more than 24h old', () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            status: 'Open',
            admission_at: pastDateTime(30),
        })
        expect(wrapper.find('.outcome-bar__banner').exists()).toBe(true)
    })

    it('hides the admission-age banner when the admission date is within 24h', () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            status: 'Open',
            admission_at: pastDateTime(2),
        })
        expect(wrapper.find('.outcome-bar__banner').exists()).toBe(false)
    })

    it('hides the admission-age banner once the tracker is In Progress, even if old', () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            status: 'In Progress',
            admission_at: pastDateTime(30),
        })
        expect(wrapper.find('.outcome-bar__banner').exists()).toBe(false)
    })
})
