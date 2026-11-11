import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import UiButton from './UiButton.vue'

describe('UiButton', () => {
  it('renders slot content', () => {
    const wrapper = mount(UiButton, { slots: { default: 'Click me' } })
    expect(wrapper.text()).toBe('Click me')
  })

  it('applies variant and size classes', () => {
    const wrapper = mount(UiButton, { props: { variant: 'danger', size: 'lg' } })
    expect(wrapper.classes()).toContain('ui-btn--danger')
    expect(wrapper.classes()).toContain('ui-btn--lg')
  })

  it('defaults to primary/md variant', () => {
    const wrapper = mount(UiButton)
    expect(wrapper.classes()).toContain('ui-btn--primary')
    expect(wrapper.classes()).toContain('ui-btn--md')
  })

  it('is disabled when disabled prop is true', () => {
    const wrapper = mount(UiButton, { props: { disabled: true } })
    expect(wrapper.attributes('disabled')).toBeDefined()
  })

  it('is disabled and shows a spinner when loading', () => {
    const wrapper = mount(UiButton, { props: { loading: true } })
    expect(wrapper.attributes('disabled')).toBeDefined()
    expect(wrapper.attributes('aria-busy')).toBe('true')
    expect(wrapper.classes()).toContain('ui-btn--loading')
    expect(wrapper.find('.ui-spinner').exists()).toBe(true)
  })

  it('defaults to type="button"', () => {
    const wrapper = mount(UiButton)
    expect(wrapper.attributes('type')).toBe('button')
  })
})
