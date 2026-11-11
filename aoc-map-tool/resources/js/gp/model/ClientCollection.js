// ClientCollection.js
import Client from './Client.js';

export default class ClientCollection {
    constructor() {
        this.clients = []; // array of Client
    }

    /**
     * Create an empty client row with a unique temporary clientKey.
     * slotIndex starts at 0; will be recalculated once patient is chosen.
     */
    createEmpty({ payers = [], branches = [], useNewWeekLogic = false } = {}) {
        const tempKey = `tmp_${Date.now()}_${this.clients.length}`;
        const slotIndex = 0;

        const client = new Client({
            clientKey: tempKey,
            slotIndex,
            data: {
                patientId: '',
                clientLabel: '',
                clientPayer: '',
                specialized: 0,
                respite: 0
            },
            payers,
            branches,
            useNewWeekLogic
        });

        this.clients.push(client);
        return client;
    }

    /**
     * Find client by its current (clientKey, slotIndex) context
     * (used in event delegation).
     */
    findByContext({ clientKey, slotIndex }) {
        return this.clients.find(
            c =>
                String(c.clientKey) === String(clientKey) &&
                Number(c.slotIndex) === Number(slotIndex)
        );
    }

    /**
     * After user selects a real client in autocomplete:
     *  - Set patientId + label on that Client
     *  - Replace clientKey with real patientId
     *  - Reindex all rows for that patient so names are field[client_id][0..N]
     */
    assignPatient(client, { patientId, label }) {
        // update model fields
        client.aocPatientId.value = patientId;
        client.clientSelection.value = label;
        client.clientKey = patientId;

        // reindex all clients with this patient
        this.reindexClient(patientId);
    }

    /**
     * Recalculate slotIndex for all rows belonging to the same patientId.
     */
    reindexClient(patientId) {
        const same = this.clients.filter(c => String(c.aocPatientId.value) === String(patientId));
        same.forEach((client, idx) => {
            client.slotIndex = idx; // 0,1,2,...
            client.setContext({
                clientKey: patientId,
                slotIndex: idx
            });
        });
    }

    /**
     * Dump everything to plain JSON for debugging or backend.
     */
    toJSON() {
        return this.clients.map(c => c.toJSON());
    }

    length () {
        return this.clients.length
    }
}
