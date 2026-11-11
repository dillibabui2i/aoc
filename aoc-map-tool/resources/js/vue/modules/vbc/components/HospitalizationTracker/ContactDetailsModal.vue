<template>
    <ui-modal
        :model-value="store.contactModalOpen"
        title="Contact Details"
        css-class="vbc-modal contact-details-modal"
        @update:model-value="store.closeContactModal"
    >
        <div v-if="isReadOnly" class="contact-details__readonly">
            <div class="contact-details__row">
                <div>
                    <strong>Contact Name</strong>
                    <p>{{ store.editingContact?.contactName }}</p>
                </div>
                <div>
                    <strong>Relationship</strong>
                    <p>{{ store.editingContact?.relationship }}</p>
                </div>
            </div>
            <div class="contact-details__row">
                <div>
                    <strong>Phone Number</strong>
                    <p>{{ formatUsPhoneDisplay(store.editingContact?.phoneNumber) }}</p>
                </div>
                <div>
                    <strong>E-mail</strong>
                    <p>{{ store.editingContact?.email }}</p>
                </div>
            </div>
            <div class="contact-details__row">
                <div>
                    <strong>Contact Date</strong>
                    <p>{{ store.editingContact?.contactDate }}</p>
                </div>
                <div>
                    <strong>Contact received by</strong>
                    <p>{{ store.editingContact?.contactReceivedBy }}</p>
                </div>
            </div>
            <div>
                <strong>Contact Notes</strong>
                <p>{{ store.editingContact?.contactNotes }}</p>
            </div>
        </div>

        <div v-else class="contact-details__form">
            <div class="contact-details__row">
                <ui-input
                    v-model="form.contactName"
                    label="Contact Name"
                    :error="
                        errors.contactName
                            ? 'Contact Name is required.'
                            : null
                    "
                />
                <ui-select
                    v-model="form.relationship"
                    label="Relationship"
                    placeholder="Select..."
                    :error="
                        errors.relationship
                            ? 'Relationship is required.'
                            : null
                    "
                >
                    <option value="">Select...</option>
                    <option
                        v-for="opt in contactTypeOptions"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </ui-select>
            </div>

            <div class="contact-details__row">
                <ui-input
                    v-model="form.phoneNumber"
                    label="Phone Number"
                    type="phone"
                    :error="
                        errors.phoneOrEmail
                            ? 'Phone Number or E-mail is required.'
                            : null
                    "
                />
                <ui-input
                    v-model="form.email"
                    label="E-mail"
                    type="email"
                    :error="
                        errors.phoneOrEmail
                            ? 'Phone Number or E-mail is required.'
                            : null
                    "
                />
            </div>

            <!-- "Contact Date" and "Contact received by" aren't form fields: both are always the
                 current time/user, stamped server-side (see HospitalizationTrackerContactService::create()). -->
            <ui-textarea v-model="form.contactNotes" label="Contact Notes" />
        </div>

        <template #footer>
            <ui-button v-if="!isReadOnly" @click="submit">Submit</ui-button>
            <ui-button @click="store.closeContactModal">Close</ui-button>
        </template>
    </ui-modal>
</template>

<script setup>
// Contact creation/editing (AC5-AC11) and read-only display once the
// tracker leaves "In Progress" (AC14/AC15). Required-field validation is
// local to this component; every field uses `UiInput`/`UiSelect`/
// `UiDateTimepicker`'s own `:error` prop (red border + message), matching
// what the backend enforces — see Store/UpdateHospitalizationTrackerContactRequest.
import { reactive, computed, watch, ref } from 'vue'
import {
    UiModal,
    UiInput,
    UiSelect,
    UITextArea as UiTextarea,
    UiButton,
} from '@angels-of-care/ui'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'
import {
    HospitalizationTrackerService,
    formatUsPhoneDisplay,
} from '@/modules/vbc/services/hospitalizationTracker.service.js'

const store = useHospitalizationTracker()

const isReadOnly = computed(() => store.tracker?.status !== 'In Progress')

// Options fetched once, lazily, the first time the modal actually opens —
// mirrors CareCoordinationModal's "Coordination Type" options, since this
// component is also mounted (but not opened) just by rendering the Contacts
// tab.
const contactTypeOptions = ref([])
let optionsLoaded = false

// Neither `contactReceivedBy` nor `contactDate` are part of the form — both
// are always the current user/time, stamped server-side and immutable on
// edit (see HospitalizationTrackerContactService::create()). The read-only
// view still shows them, straight off `store.editingContact`.
function blankForm() {
    return {
        contactName: '',
        relationship: '',
        phoneNumber: '',
        email: '',
        contactNotes: '',
    }
}

const form = reactive(blankForm())

const errors = reactive({
    contactName: false,
    relationship: false,
    // A single flag drives both fields' `:error`, since the rule is "at
    // least one of the two", not "each is independently required" — mirrors
    // the backend's `required_without` pairing on phone_number/email.
    phoneOrEmail: false,
})

// Re-fills the form whenever the modal is (re)opened, for both the create
// (blank) and edit (prefilled from `editingContact`) cases.
// `editingContact.relationship` is the type's label (for the table/read-only
// view) — the dropdown's `<option>` values are ids, so `contactTypeId` (also
// on the resource) is what actually pre-selects the right one.
watch(
    () => store.contactModalOpen,
    async (open) => {
        if (!open) return
        if (!optionsLoaded) {
            optionsLoaded = true
            contactTypeOptions.value = await HospitalizationTrackerService.getContactTypes()
        }

        Object.assign(form, blankForm(), store.editingContact ?? {})
        if (store.editingContact) {
            form.relationship = store.editingContact.contactTypeId ?? ''
            // `contactNotes` comes back `null` (not `''`) for a contact saved without notes (nullable
            // column) — `UITextArea`'s `v-model` calls `.length` on it internally, which throws on
            // `null`. Same gotcha already documented in SickPlanDetailsModal.vue.
            form.contactNotes = store.editingContact.contactNotes ?? ''
            // The resource returns bare digits; the editable field needs the same masked
            // "+1 (XXX) XXX-XXXX" shape `UiInput type="phone"` already produces as you type, or
            // re-submitting without touching this field would send unformatted digits straight through
            // — harmless for validation, but a visual mismatch with the mask.
            form.phoneNumber = formatUsPhoneDisplay(store.editingContact.phoneNumber) ?? ''
        }
        Object.keys(errors).forEach((key) => (errors[key] = false))
    },
)

function validate() {
    errors.contactName = !form.contactName
    errors.relationship = !form.relationship
    errors.phoneOrEmail = !form.phoneNumber && !form.email
    return !Object.values(errors).some(Boolean)
}

function submit() {
    if (!validate()) return
    store.submitContact({ ...form })
}
</script>

<style scoped>
.contact-details__form,
.contact-details__readonly {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.contact-details__row {
    display: flex;
    gap: 12px;
}
.contact-details__row > * {
    flex: 1;
}
.contact-details__readonly p {
    margin: 2px 0 0;
}
</style>
