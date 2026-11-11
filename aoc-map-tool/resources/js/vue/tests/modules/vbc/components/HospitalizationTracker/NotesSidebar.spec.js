import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import NotesSidebar from '@/modules/vbc/components/HospitalizationTracker/NotesSidebar.vue'

describe('NotesSidebar', () => {
    it('renders an empty state with no notes', () => {
        const wrapper = mount(NotesSidebar)
        expect(wrapper.find('.notes-sidebar__empty').exists()).toBe(true)
    })

    it('renders each note as a "{author} commented" header and a detail line', () => {
        const wrapper = mount(NotesSidebar, {
            props: {
                notes: [
                    {
                        id: 1,
                        notes: 'Called the facility',
                        author_name: 'Anna Lopatnova',
                        commented_at: '01/01/2026 10:00 AM',
                    },
                ],
            },
        })
        expect(wrapper.find('.notes-sidebar__empty').exists()).toBe(false)
        const header = wrapper.find('.notes-sidebar__item-header')
        expect(header.find('strong').text()).toBe('Anna Lopatnova')
        expect(header.text()).toContain('Anna Lopatnova commented')
        expect(wrapper.find('.notes-sidebar__date').text()).toBe(
            '01/01/2026 10:00 AM',
        )
        expect(wrapper.find('.notes-sidebar__item-detail').text()).toBe(
            'Called the facility',
        )
    })

    it('keeps the server order (already newest-first) without re-sorting', () => {
        const wrapper = mount(NotesSidebar, {
            props: {
                notes: [
                    {
                        id: 2,
                        notes: 'newest',
                        author_name: 'A',
                        commented_at: 'c',
                    },
                    {
                        id: 3,
                        notes: 'middle',
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
        expect(details).toEqual(['newest', 'middle', 'oldest'])
    })

    it('preserves line breaks in a multi-line detail (Activity Log wording)', () => {
        const wrapper = mount(NotesSidebar, {
            props: {
                notes: [
                    {
                        id: 1,
                        notes: 'Field Name: Admission Type\nOld Value: Avoidable\nNew Value: Planned',
                        author_name: 'Anna Lopatnova',
                        commented_at: '01/01/2026 10:00 AM',
                    },
                ],
            },
        })
        const detail = wrapper.find('.notes-sidebar__item-detail')
        expect(detail.element.textContent).toBe(
            'Field Name: Admission Type\nOld Value: Avoidable\nNew Value: Planned',
        )
    })

    it('drops the status line that repeats the header and bolds the author name', () => {
        const wrapper = mount(NotesSidebar, {
            props: {
                notes: [
                    {
                        id: 1,
                        notes: "In Progress by Anna Lopatnova at 01/01/2026 10:00 AM\nStatus changed to 'In Progress' by Anna Lopatnova",
                        author_name: 'Anna Lopatnova',
                        commented_at: '01/01/2026 10:00 AM',
                    },
                ],
            },
        })
        const detail = wrapper.find('.notes-sidebar__item-detail')
        expect(detail.text()).toBe(
            "Status changed to 'In Progress' by Anna Lopatnova",
        )
        expect(detail.find('.notes-sidebar__name').text()).toBe(
            'Anna Lopatnova',
        )
    })

    it('falls back to "Someone" when the author is unknown', () => {
        const wrapper = mount(NotesSidebar, {
            props: {
                notes: [
                    {
                        id: 1,
                        notes: 'Orphan note',
                        author_name: null,
                        commented_at: 'x',
                    },
                ],
            },
        })
        expect(wrapper.find('.notes-sidebar__item-header strong').text()).toBe(
            'Someone',
        )
        expect(wrapper.find('.notes-sidebar__item-detail').text()).toBe(
            'Orphan note',
        )
    })
})
