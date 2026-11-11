import { describe, it, expect, vi, beforeEach } from 'vitest'
import client from '@/core/api/client.js'
import {
    HospitalizationTrackerService,
    isActiveTracker,
    isNotesEligible,
} from '@/modules/vbc/services/hospitalizationTracker.service.js'

vi.mock('@/core/api/client.js', () => ({
    default: {
        get: vi.fn(),
        post: vi.fn(),
        patch: vi.fn(),
        put: vi.fn(),
    },
}))

// Route name plus its params as a query string, so each call pins both the
// named route and the exact route params the service passes to Ziggy.
vi.mock('ziggy-js', () => ({
    route: (name, params) => {
        const query = Object.entries(params ?? {})
            .map(([key, value]) => `${key}=${value}`)
            .join('&')
        return query ? `/${name}?${query}` : `/${name}`
    },
}))

describe('isActiveTracker', () => {
    it('treats Completed and Closed as not active', () => {
        expect(isActiveTracker('Completed')).toBe(false)
        expect(isActiveTracker('Closed')).toBe(false)
    })

    it('treats any other status as active', () => {
        expect(isActiveTracker('Open')).toBe(true)
        expect(isActiveTracker('In Progress')).toBe(true)
    })
})

describe('isNotesEligible', () => {
    it('treats Closed and Completed as ineligible for notes', () => {
        expect(isNotesEligible('Closed')).toBe(false)
        expect(isNotesEligible('Completed')).toBe(false)
    })

    it('treats any other status as eligible for notes', () => {
        expect(isNotesEligible('Open')).toBe(true)
        expect(isNotesEligible('In Progress')).toBe(true)
        expect(isNotesEligible('Discharged')).toBe(true)
        expect(isNotesEligible('Ready for Review')).toBe(true)
        expect(isNotesEligible('Ready for Next Steps')).toBe(true)
    })
})

describe('HospitalizationTrackerService', () => {
    beforeEach(() => {
        vi.clearAllMocks()
    })

    it('searchClients sends the trimmed query and unwraps data.data', async () => {
        const clients = [{ patient_account: '12345-PDN' }]
        client.get.mockResolvedValue({ data: { data: clients } })

        const result =
            await HospitalizationTrackerService.searchClients(' john ')

        expect(client.get).toHaveBeenCalledWith(
            '/hospitalization.clients.index',
            {
                params: { search: 'john' },
            },
        )
        expect(result).toEqual(clients)
    })

    it('searchClients returns nothing for an empty query without a request', async () => {
        const result = await HospitalizationTrackerService.searchClients('  ')

        expect(result).toEqual([])
        expect(client.get).not.toHaveBeenCalled()
    })

    it('getClient merges the client data with the active-tracker meta', async () => {
        client.get.mockResolvedValue({
            data: {
                data: {
                    patient_account: '99999-PDN',
                    patient_name: 'John Doe',
                },
                meta: { active_tracker_id: 10087 },
            },
        })

        const result =
            await HospitalizationTrackerService.getClient('99999-PDN')

        expect(client.get).toHaveBeenCalledWith(
            '/hospitalization.clients.show?patientId=99999-PDN',
        )
        expect(result).toEqual({
            patient_account: '99999-PDN',
            patient_name: 'John Doe',
            active_tracker_id: 10087,
        })
    })

    it.each([
        ['getAssignees', '/hospitalization.assignees.index'],
        ['getContactTypes', '/hospitalization.contact-types.index'],
        ['getTrainingTypes', '/hospitalization.training-types.index'],
        ['getTrainingSources', '/hospitalization.training-sources.index'],
    ])('%s requests %s and unwraps data', async (method, url) => {
        const options = [{ value: 1, text: 'Option' }]
        client.get.mockResolvedValue({ data: options })

        const result = await HospitalizationTrackerService[method]()

        expect(client.get).toHaveBeenCalledWith(url)
        expect(result).toEqual(options)
    })

    it('createTracker posts the payload and returns the created tracker', async () => {
        const tracker = {
            tracker_id: 501,
            status: 'Open',
            patient_name: 'John Doe',
        }
        client.post.mockResolvedValue({ data: { data: tracker } })

        const payload = { patient_account: '12345-PDN', assigned_to_id: 7 }
        const result =
            await HospitalizationTrackerService.createTracker(payload)

        expect(client.post).toHaveBeenCalledWith(
            '/hospitalization.trackers.store',
            payload,
        )
        expect(result).toMatchObject(tracker)
    })

    it('getTrackerById requests the IP tracker and returns it', async () => {
        const tracker = { tracker_id: 502, status: 'In Progress' }
        client.get.mockResolvedValue({ data: { data: tracker } })

        const result = await HospitalizationTrackerService.getTrackerById(502)

        expect(client.get).toHaveBeenCalledWith(
            '/hospitalization.trackers.show?trackerId=502&encounterType=IP',
        )
        expect(result).toMatchObject(tracker)
    })

    it('getTrackerById returns null on a 404', async () => {
        client.get.mockRejectedValue({ response: { status: 404 } })

        const result = await HospitalizationTrackerService.getTrackerById(999)

        expect(result).toBeNull()
    })

    it('getTrackerById rethrows non-404 errors', async () => {
        const error = { response: { status: 500 } }
        client.get.mockRejectedValue(error)

        await expect(
            HospitalizationTrackerService.getTrackerById(999),
        ).rejects.toBe(error)
    })

    it('saveField patches one field by key and returns the updated tracker', async () => {
        const tracker = { tracker_id: 503, level: 'Level II' }
        client.patch.mockResolvedValue({ data: { data: tracker } })

        const result = await HospitalizationTrackerService.saveField(
            503,
            'level',
            'Level II',
        )

        expect(client.patch).toHaveBeenCalledWith(
            '/hospitalization.trackers.update?trackerId=503',
            {
                field: 'level',
                value: 'Level II',
            },
        )
        expect(result).toMatchObject(tracker)
    })

    it.each([
        ['Discharge', 'DISCHARGED'],
        ['Ready for Review', 'READY_FOR_REVIEW'],
        ['Ready for Next Steps', 'READY_FOR_NEXT_STEPS'],
        ['Completed', 'COMPLETED'],
    ])('updateOutcome maps %s to %s on the wire', async (outcome, wire) => {
        client.patch.mockResolvedValue({ data: { data: { tracker_id: 504 } } })

        await HospitalizationTrackerService.updateOutcome(504, { outcome })

        expect(client.patch).toHaveBeenCalledWith(
            '/hospitalization.trackers.outcome.update?trackerId=504',
            { outcome: wire },
        )
    })

    it('updateOutcome sends discharge fields, assignee and notes when given', async () => {
        const tracker = { tracker_id: 505, status: 'Ready for Review' }
        client.patch.mockResolvedValue({ data: { data: tracker } })

        const fields = { discharge_date: '03/08/2026' }
        const result = await HospitalizationTrackerService.updateOutcome(505, {
            outcome: 'Ready for Review',
            fields,
            assigneeId: 11,
            notes: 'Please review',
        })

        expect(client.patch).toHaveBeenCalledWith(
            '/hospitalization.trackers.outcome.update?trackerId=505',
            {
                outcome: 'READY_FOR_REVIEW',
                fields,
                assignee_id: 11,
                notes: 'Please review',
            },
        )
        expect(result).toMatchObject(tracker)
    })

    it('updateOutcomeStatus sends In Progress', async () => {
        client.patch.mockResolvedValue({ data: { data: { tracker_id: 506 } } })

        await HospitalizationTrackerService.updateOutcomeStatus(506, {
            outcome: 'In Progress',
        })

        expect(client.patch).toHaveBeenCalledWith(
            '/hospitalization.trackers.outcome.update?trackerId=506',
            { outcome: 'IN_PROGRESS', reason: undefined },
        )
    })

    it('updateOutcomeStatus sends Closed with the mapped reason, fields and notes', async () => {
        const newTracker = { tracker_id: 600, status: 'Open' }
        client.patch.mockResolvedValue({ data: { data: newTracker } })

        const fields = { facility_id: 3 }
        const result = await HospitalizationTrackerService.updateOutcomeStatus(
            507,
            {
                outcome: 'Closed',
                reason: 'Transfer',
                fields,
                notes: 'Moved to another hospital',
            },
        )

        expect(client.patch).toHaveBeenCalledWith(
            '/hospitalization.trackers.outcome.update?trackerId=507',
            {
                outcome: 'CLOSED',
                reason: 'TRANSFERRED',
                fields,
                notes: 'Moved to another hospital',
            },
        )
        expect(result).toMatchObject(newTracker)
    })

    it('createContact maps the form to the wire contract and appends the contact', async () => {
        const contact = { id: 31, contactName: 'Mary Smith' }
        client.post.mockResolvedValue({ data: { data: contact } })

        const result = await HospitalizationTrackerService.createContact(510, {
            relationship: 2,
            contactName: 'Mary Smith',
            phoneNumber: '5551234567',
            email: 'mary@example.com',
            contactNotes: '',
            contactReceivedBy: 'ignored',
        })

        expect(client.post).toHaveBeenCalledWith(
            '/hospitalization.trackers.contacts.store?trackerId=510',
            {
                contact_type_id: 2,
                contact_name: 'Mary Smith',
                phone_number: '5551234567',
                email: 'mary@example.com',
                contact_notes: null,
            },
        )
        expect(result.contacts).toEqual([contact])
    })

    it('updateContact replaces only the matching contact', async () => {
        client.post.mockResolvedValueOnce({
            data: { data: { id: 41, contactName: 'A' } },
        })
        client.post.mockResolvedValueOnce({
            data: { data: { id: 42, contactName: 'B' } },
        })
        await HospitalizationTrackerService.createContact(511, {
            contactName: 'A',
        })
        await HospitalizationTrackerService.createContact(511, {
            contactName: 'B',
        })

        client.patch.mockResolvedValue({
            data: { data: { id: 41, contactName: 'A2' } },
        })
        const result = await HospitalizationTrackerService.updateContact(
            511,
            41,
            {
                contactName: 'A2',
            },
        )

        expect(client.patch).toHaveBeenCalledWith(
            '/hospitalization.trackers.contacts.update?trackerId=511&contactId=41',
            expect.objectContaining({ contact_name: 'A2' }),
        )
        expect(result.contacts).toEqual([
            { id: 41, contactName: 'A2' },
            { id: 42, contactName: 'B' },
        ])
    })

    it('createDiagnosis posts the payload as-is and appends the diagnosis', async () => {
        const diagnosis = { id: 51, icd_10_code: 'R94.5' }
        client.post.mockResolvedValue({ data: { data: diagnosis } })

        const payload = { icd_10_code: 'R94.5', rank: 1 }
        const result = await HospitalizationTrackerService.createDiagnosis(
            520,
            payload,
        )

        expect(client.post).toHaveBeenCalledWith(
            '/hospitalization.trackers.diagnoses.store?trackerId=520',
            payload,
        )
        expect(result.diagnoses).toEqual([diagnosis])
    })

    it('updateDiagnosis replaces only the matching diagnosis', async () => {
        client.post.mockResolvedValueOnce({
            data: { data: { id: 61, rank: 1 } },
        })
        client.post.mockResolvedValueOnce({
            data: { data: { id: 62, rank: 2 } },
        })
        await HospitalizationTrackerService.createDiagnosis(521, {})
        await HospitalizationTrackerService.createDiagnosis(521, {})

        client.patch.mockResolvedValue({ data: { data: { id: 62, rank: 3 } } })
        const result = await HospitalizationTrackerService.updateDiagnosis(
            521,
            62,
            { rank: 3 },
        )

        expect(client.patch).toHaveBeenCalledWith(
            '/hospitalization.trackers.diagnoses.update?trackerId=521&diagnosisId=62',
            { rank: 3 },
        )
        expect(result.diagnoses).toEqual([
            { id: 61, rank: 1 },
            { id: 62, rank: 3 },
        ])
    })

    it('updateWasDiagnosisMade patches the dedicated toggle endpoint', async () => {
        client.patch.mockResolvedValue({ data: { data: { tracker_id: 522 } } })

        await HospitalizationTrackerService.updateWasDiagnosisMade(522, true)

        expect(client.patch).toHaveBeenCalledWith(
            '/hospitalization.trackers.diagnosis-toggle.update?trackerId=522',
            { was_diagnosis_made: true },
        )
    })

    it('createEducationRecord / updateEducationRecord keep education_records in sync', async () => {
        client.post.mockResolvedValue({
            data: { data: { id: 71, training_type_id: 1 } },
        })
        await HospitalizationTrackerService.createEducationRecord(530, {
            training_type_id: 1,
        })

        client.patch.mockResolvedValue({
            data: { data: { id: 71, training_type_id: 2 } },
        })
        const result =
            await HospitalizationTrackerService.updateEducationRecord(530, 71, {
                training_type_id: 2,
            })

        expect(client.post).toHaveBeenCalledWith(
            '/hospitalization.trackers.educations.store?trackerId=530',
            { training_type_id: 1 },
        )
        expect(client.patch).toHaveBeenCalledWith(
            '/hospitalization.trackers.educations.update?trackerId=530&educationId=71',
            { training_type_id: 2 },
        )
        expect(result.education_records).toEqual([
            { id: 71, training_type_id: 2 },
        ])
    })

    it('searchHospitalizedClientsForFollowUp maps suggestions to the camelCase shape', async () => {
        client.get.mockResolvedValue({
            data: {
                data: [
                    {
                        patient_account: '12345-PDN',
                        patient_name: 'John Taylor',
                        hospitalization_tracker_id: 10085,
                        facility_name: 'General Hospital',
                        admission_date_time: '03/08/2026 10:00',
                        level_label: 'Level I',
                    },
                ],
            },
        })

        const result =
            await HospitalizationTrackerService.searchHospitalizedClientsForFollowUp(
                ' taylor ',
            )

        expect(client.get).toHaveBeenCalledWith('/follow-up.clients.index', {
            params: { search: 'taylor' },
        })
        expect(result).toEqual([
            {
                clientId: '12345-PDN',
                fullName: 'John Taylor',
                trackerId: 10085,
                facilityName: 'General Hospital',
                admissionDateTime: '03/08/2026 10:00',
                level: 'Level I',
            },
        ])
    })

    it('searchHospitalizedClientsForFollowUp returns nothing for an empty query', async () => {
        const result =
            await HospitalizationTrackerService.searchHospitalizedClientsForFollowUp(
                '',
            )

        expect(result).toEqual([])
        expect(client.get).not.toHaveBeenCalled()
    })

    it('createFollowUp posts the tracker id and maps the follow-up', async () => {
        client.post.mockResolvedValue({
            data: {
                data: {
                    id: 81,
                    status_label: 'Open',
                    hospitalization_tracker_id: 540,
                    created_at: '03/08/2026',
                },
            },
        })

        const result = await HospitalizationTrackerService.createFollowUp(540)

        expect(client.post).toHaveBeenCalledWith('/follow-up.store', {
            hospitalization_tracker_id: 540,
        })
        expect(result).toMatchObject({
            id: 81,
            status: 'Open',
            trackerId: 540,
            createdAt: '03/08/2026',
        })
    })

    it('updateFollowUpOutcome patches the outcome and maps the follow-up', async () => {
        client.patch.mockResolvedValue({
            data: {
                data: { id: 82, status_label: 'Completed', notes: 'Done' },
            },
        })

        const payload = { status: 'COMPLETED', notes: 'Done' }
        const result =
            await HospitalizationTrackerService.updateFollowUpOutcome(
                82,
                payload,
            )

        expect(client.patch).toHaveBeenCalledWith(
            '/follow-up.outcome.update?followupId=82',
            payload,
        )
        expect(result).toMatchObject({
            id: 82,
            status: 'Completed',
            additionalNotes: 'Done',
            attemptNotes: 'Done',
        })
    })

    it('getSickPlan returns the plan, or null on a 404', async () => {
        const plan = { id: 91, items: [] }
        client.get.mockResolvedValueOnce({ data: { data: plan } })
        client.get.mockRejectedValueOnce({ response: { status: 404 } })

        expect(await HospitalizationTrackerService.getSickPlan(550)).toEqual(
            plan,
        )
        expect(await HospitalizationTrackerService.getSickPlan(551)).toBeNull()
        expect(client.get).toHaveBeenCalledWith(
            '/hospitalization.trackers.sick-plan.show?trackerId=550',
        )
    })

    it('createNote posts the text and returns the Activity Log', async () => {
        const log = [{ id: 1, notes: 'Called the family' }]
        client.post.mockResolvedValue({ data: { data: log } })

        const result = await HospitalizationTrackerService.createNote(
            560,
            'Called the family',
        )

        expect(client.post).toHaveBeenCalledWith(
            '/hospitalization.trackers.notes.store?trackerId=560',
            { notes: 'Called the family' },
        )
        expect(result).toEqual(log)
    })

    it('getTrackerNotes returns the Activity Log', async () => {
        const log = [{ id: 2, notes: 'x' }]
        client.get.mockResolvedValue({ data: { data: log } })

        const result = await HospitalizationTrackerService.getTrackerNotes(561)

        expect(client.get).toHaveBeenCalledWith(
            '/hospitalization.trackers.notes.index?trackerId=561',
        )
        expect(result).toEqual(log)
    })

    it('saveCurrentInfo puts the whole section', async () => {
        client.put.mockResolvedValue({ data: { data: { tracker_id: 562 } } })

        const values = { admission_date_time: '03/08/2026 10:00' }
        await HospitalizationTrackerService.saveCurrentInfo(562, values)

        expect(client.put).toHaveBeenCalledWith(
            '/hospitalization.trackers.current-info.update?trackerId=562',
            values,
        )
    })

    it('searchActiveHospitalizedClients only returns clients with an In Progress tracker', async () => {
        const results =
            await HospitalizationTrackerService.searchActiveHospitalizedClients(
                'John',
            )

        const clientIds = results.map((c) => c.clientId)
        expect(clientIds).toEqual(
            expect.arrayContaining(['12345-PDN', '91299-PDN', '8979-PDN']),
        )
        // 99999-PDN's #10087 fixture defaults to 'Open', not 'In Progress'.
        expect(clientIds).not.toContain('99999-PDN')
        results.forEach((c) => {
            expect(c.trackerId).toBeTruthy()
            expect(c.fullName).toBeTruthy()
        })
    })

    it('searchActiveHospitalizedClients matches by name substring, case-insensitive', async () => {
        const results =
            await HospitalizationTrackerService.searchActiveHospitalizedClients(
                'taylor',
            )

        expect(results.length).toBeGreaterThan(0)
        results.forEach((c) => {
            expect(c.fullName.toLowerCase()).toContain('taylor')
        })
        expect(results.some((c) => c.clientId === '12345-PDN')).toBe(true)
    })

    it('searchActiveHospitalizedClients returns nothing for an empty query', async () => {
        const results =
            await HospitalizationTrackerService.searchActiveHospitalizedClients(
                '',
            )
        expect(results).toEqual([])
    })
})
