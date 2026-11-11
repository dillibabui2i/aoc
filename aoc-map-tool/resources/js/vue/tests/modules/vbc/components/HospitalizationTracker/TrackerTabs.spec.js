import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import TrackerTabs from '@/modules/vbc/components/HospitalizationTracker/TrackerTabs.vue'

const TABS = [
    { key: 'a', label: 'Tab A' },
    { key: 'b', label: 'Tab B' },
]

describe('TrackerTabs', () => {
    it('marks the active tab and emits update:modelValue on click', async () => {
        const wrapper = mount(TrackerTabs, {
            props: { tabs: TABS, modelValue: 'a' },
        })
        const buttons = wrapper.findAll('button')

        expect(buttons[0].classes()).toContain('tracker-tabs__tab--active')
        expect(buttons[1].classes()).not.toContain('tracker-tabs__tab--active')

        await buttons[1].trigger('click')

        expect(wrapper.emitted('update:modelValue')).toEqual([['b']])
    })

    it('renders the slot matching the active tab key', () => {
        const wrapper = mount(TrackerTabs, {
            props: { tabs: TABS, modelValue: 'a' },
            slots: { a: '<p>Content A</p>', b: '<p>Content B</p>' },
        })

        expect(wrapper.text()).toContain('Content A')
        expect(wrapper.text()).not.toContain('Content B')
    })

    it('does not render a sub-tab bar for tabs without subTabs', () => {
        const wrapper = mount(TrackerTabs, {
            props: { tabs: TABS, modelValue: 'a' },
        })

        expect(wrapper.find('.tracker-tabs__bar--sub').exists()).toBe(false)
    })
})

const NESTED_TABS = [
    {
        key: 'parent',
        label: 'Parent',
        subTabs: [
            { key: 'child1', label: 'Child 1' },
            { key: 'child2', label: 'Child 2' },
        ],
    },
    { key: 'sibling', label: 'Sibling' },
]

describe('TrackerTabs sub-tabs', () => {
    it('defaults to the first sub-tab and renders its slot content', () => {
        const wrapper = mount(TrackerTabs, {
            props: { tabs: NESTED_TABS, modelValue: 'parent' },
            slots: { child1: '<p>Child 1 content</p>' },
        })

        const subButtons = wrapper.findAll('.tracker-tabs__bar--sub button')
        expect(subButtons.map((b) => b.text())).toEqual(['Child 1', 'Child 2'])
        expect(subButtons[0].classes()).toContain('tracker-tabs__tab--active')
        expect(wrapper.text()).toContain('Child 1 content')
    })

    it('switches sub-tab content on click without emitting update:modelValue', async () => {
        const wrapper = mount(TrackerTabs, {
            props: { tabs: NESTED_TABS, modelValue: 'parent' },
            slots: {
                child1: '<p>Child 1 content</p>',
                child2: '<p>Child 2 content</p>',
            },
        })

        await wrapper
            .findAll('.tracker-tabs__bar--sub button')[1]
            .trigger('click')

        expect(wrapper.text()).toContain('Child 2 content')
        expect(wrapper.emitted('update:modelValue')).toBeUndefined()
    })

    it('hides the sub-tab bar and resets to the first sub-tab when switching to a tab without subTabs', async () => {
        const wrapper = mount(TrackerTabs, {
            props: { tabs: NESTED_TABS, modelValue: 'parent' },
        })
        await wrapper
            .findAll('.tracker-tabs__bar--sub button')[1]
            .trigger('click')

        await wrapper.setProps({ modelValue: 'sibling' })
        expect(wrapper.find('.tracker-tabs__bar--sub').exists()).toBe(false)

        await wrapper.setProps({ modelValue: 'parent' })
        const subButtons = wrapper.findAll('.tracker-tabs__bar--sub button')
        expect(subButtons[0].classes()).toContain('tracker-tabs__tab--active')
    })
})
