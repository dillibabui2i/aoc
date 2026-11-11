import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import EdNotesSidebar from '@/modules/vbc/components/EdVisitTracker/EdNotesSidebar.vue'

describe('EdNotesSidebar', () => {
    it('renders an empty state with no notes', () => {
        const wrapper = mount(EdNotesSidebar)
        expect(wrapper.find('.notes-sidebar__empty').exists()).toBe(true)
    })

    it('renders each note as a timestamp header and a detail line', () => {
        const wrapper = mount(EdNotesSidebar, {
            props: {
                notes: [
                    {
                        id: 1,
                        notes: 'Facility Name set to (empty) by Anna Lopatnova',
                        author_name: 'Anna Lopatnova',
                        commented_at: 'Oct 28th, 2026 at 10:15 AM',
                    },
                ],
            },
        })
        expect(wrapper.find('.notes-sidebar__item-header strong').text()).toBe(
            'Anna Lopatnova',
        )
        expect(wrapper.find('.notes-sidebar__item-header').text()).toContain(
            'commented',
        )
        expect(wrapper.find('.notes-sidebar__date').text()).toBe(
            'Oct 28th, 2026 at 10:15 AM',
        )
        expect(wrapper.find('.notes-sidebar__name').text()).toBe(
            'Anna Lopatnova',
        )
        expect(wrapper.find('.notes-sidebar__item-detail').text()).toBe(
            'Facility Name set to (empty) by Anna Lopatnova',
        )
    })

    it('keeps the order it is given (newest first from the server)', () => {
        const wrapper = mount(EdNotesSidebar, {
            props: {
                notes: [
                    {
                        id: 2,
                        notes: 'newest',
                        author_name: 'A',
                        commented_at: 'b',
                    },
                    {
                        id: 1,
                        notes: 'oldest',
                        author_name: 'A',
                        commented_at: 'a',
                    },
                ],
            },
        })
        const details = wrapper
            .findAll('.notes-sidebar__item-detail')
            .map((el) => el.text())
        expect(details).toEqual(['newest', 'oldest'])
    })

    it('preserves line breaks in a multi-line detail (Activity Log wording)', () => {
        const wrapper = mount(EdNotesSidebar, {
            props: {
                notes: [
                    {
                        id: 1,
                        notes: 'Field Name: Admission Type\nOld Value: Avoidable\nNew Value: Planned by Anna Lopatnova',
                        author_name: 'Anna Lopatnova',
                        commented_at: 'Jan 1st, 2026 at 10:00 AM',
                    },
                ],
            },
        })
        const detail = wrapper.find('.notes-sidebar__item-detail')
        expect(detail.element.textContent).toBe(
            'Field Name: Admission Type\nOld Value: Avoidable\nNew Value: Planned by Anna Lopatnova',
        )
    })
})
