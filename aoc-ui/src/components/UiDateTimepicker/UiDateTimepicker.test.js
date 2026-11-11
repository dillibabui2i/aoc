import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import UiDateTimepicker from './UiDateTimepicker.vue'

function setPickerDate(wrapper, date) {
  const fp = wrapper.find('input').element._flatpickr
  fp.setDate(date, true)
}

describe('UiDateTimepicker', () => {
  it('zero-pads a single-digit hour in 12h datetime mode', async () => {
    const wrapper = mount(UiDateTimepicker, { props: { mode: 'datetime', timeFormat: '12' } })
    setPickerDate(wrapper, new Date(2026, 8, 23, 17, 11))
    const emitted = wrapper.emitted('update:modelValue').at(-1)[0]
    expect(emitted).toBe('09/23/2026 05:11 PM')
    expect(emitted).toMatch(/\d{2}:\d{2} [AP]M$/)
  })

  it('leaves an already double-digit hour unchanged in 12h datetime mode', async () => {
    const wrapper = mount(UiDateTimepicker, { props: { mode: 'datetime', timeFormat: '12' } })
    setPickerDate(wrapper, new Date(2026, 8, 23, 23, 5))
    const emitted = wrapper.emitted('update:modelValue').at(-1)[0]
    expect(emitted).toBe('09/23/2026 11:05 PM')
    expect(emitted).toMatch(/\d{2}:\d{2} [AP]M$/)
  })

  it('zero-pads a single-digit hour in 12h time mode', async () => {
    const wrapper = mount(UiDateTimepicker, { props: { mode: 'time', timeFormat: '12' } })
    setPickerDate(wrapper, new Date(2026, 8, 23, 5, 11))
    const emitted = wrapper.emitted('update:modelValue').at(-1)[0]
    expect(emitted).toBe('05:11 AM')
    expect(emitted).toMatch(/^\d{2}:\d{2} [AP]M$/)
  })
})
