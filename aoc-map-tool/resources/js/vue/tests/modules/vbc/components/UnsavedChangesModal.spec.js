import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import UnsavedChangesModal from '@/modules/vbc/components/UnsavedChangesModal.vue'

const stubs = {
    UiModal: {
        props: ['modelValue'],
        template:
            '<div v-if="modelValue" class="ui-modal-stub"><slot /><slot name="footer" /></div>',
    },
    UiButton: {
        emits: ['click'],
        template: '<button @click="$emit(\'click\')"><slot /></button>',
    },
}

describe('UnsavedChangesModal', () => {
    function mountModal(modelValue) {
        return mount(UnsavedChangesModal, {
            props: { modelValue },
            global: { stubs },
        })
    }

    it('renders nothing while closed', () => {
        expect(mountModal(false).find('.ui-modal-stub').exists()).toBe(false)
    })

    it('warns about the lost changes and emits cancel / confirm from its buttons', async () => {
        const wrapper = mountModal(true)

        expect(wrapper.text()).toContain('You have unsaved changes.')

        const [cancel, confirm] = wrapper.findAll('button')
        expect(cancel.text()).toBe('Cancel')
        expect(confirm.text()).toBe('Continue')

        await cancel.trigger('click')
        await confirm.trigger('click')

        expect(wrapper.emitted('cancel')).toHaveLength(1)
        expect(wrapper.emitted('confirm')).toHaveLength(1)
    })
})
