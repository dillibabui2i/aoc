// NurseCollection.js
import Nurse from './Nurse.js';

export default class NurseCollection {
    constructor() {
        this.map = new Map(); // clientId -> Nurse[]
    }

    forClient(clientId) {
        const key = String(clientId);
        if (!this.map.has(key)) this.map.set(key, []);
        return this.map.get(key);
    }

    register(nurse) {
        const list = this.forClient(nurse.clientId);
        if (!list.includes(nurse)) list.push(nurse);
    }

    findByContext({ clientId, nurseId, index }) {
        const list = this.forClient(clientId);
        return list.find(
            n =>
                String(n.nurseId) === String(nurseId) &&
                Number(n.index) === Number(index)
        );
    }

    /**
     * Create an empty nurse row for a client.
     * Used when clicking #addClinician.
     */
    createEmpty({
                    clientId,
                    selectedPayer = '',
                    isRespite = false,
                    disciplines = [],
                    useServiceLabel = false
                }) {
        const list = this.forClient(clientId);

        const tempNurseId = `tmp_${Date.now()}_${list.length + 1}`;
        const index = list.length + 1;

        const nurse = new Nurse({
            clientId,
            nurseId: tempNurseId,
            index,
            data: {
                isOldNurse: 0,
                isRespite: isRespite ? 1 : 0,
                selectedPayer,
                nurseName: '',
                hours: 0.0,
                desiredRate: 0.0,
                preHours: 0.0,
                discipline: ''
            },
            disciplines,
            useServiceLabel
        });

        list.push(nurse);
        return nurse;
    }

    /**
     * Assign real clinician to an existing nurse (after autocomplete selection).
     */
    assignClinician(nurse, clinician) {
        const clientId = nurse.clientId;
        const list = this.forClient(clientId);

        const finalNurseId = clinician.id;

        nurse.nurseName.value = clinician.name || nurse.nurseName.value;
        nurse.aocClinicianId.value = clinician.id;
        nurse.isOldNurse.value = clinician.isOldNurse ? 1 : 0;

        if (clinician.discipline) {
            nurse.gpNurseDiscipline.value = clinician.discipline;
            nurse.gpNurseDiscipline.options.forEach(o => {
                o.selected = (String(o.value) === String(clinician.discipline));
            });
        }

        // Determine new incremental index among same nurseId
        const sameNurse = list.filter(n => n !== nurse && String(n.nurseId) === String(finalNurseId));
        const newIndex = sameNurse.length + 1;

        nurse.setContext({
            clientId,
            nurseId: finalNurseId,
            index: newIndex
        });

        nurse.nurseId = finalNurseId;

        return nurse;
    }

    toJSON() {
        const out = [];
        for (const list of this.map.values()) {
            for (const n of (list || [])) {
                if (n && typeof n.toJSON === 'function') out.push(n.toJSON());
            }
        }
        return out;
    }

    length () {
        return this.map.size;
    }
}
