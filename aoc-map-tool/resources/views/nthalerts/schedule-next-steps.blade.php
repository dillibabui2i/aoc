<div class="modal fade" id="simpleLeadsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-blue text-white">
                <h5 class="modal-title">Resolve other leads for this clinician</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">х</span>
                </button>
            </div>

            <div class="modal-body">
                {{-- Clinician Info --}}
                <div class="mb-3">
                    <p><strong>Clinician:</strong> {{ $clinicianData->fullName }} (ID: {{ $clinicianData->ClinicianID }})</p>
                    <p><strong>Current Advancing Lead:</strong> {{ $leadsInquiriesDetail->ticket_id }}
                        (Client: {{$clientDetails->Patient_FirstName ?? ''}} {{$clientDetails->Patient_LastName ?? ''}} - {{ $clientDetails->PatientAccount ?? '' }})
                    </p>
                </div>

                {{-- Table --}}
                <table id="simpleLeadsTable" class="table table-bordered table-striped w-100">
                    <thead class="bg-blue text-white">
                    <tr>
                        <th><input type="checkbox" id="selectAllLeads"></th>
                        <th>Lead ID</th>
                        <th>Client ID</th>
                        <th>Client Name</th>
                        <th>City / State</th>
                        <th>Status</th>
                        <th>Owner</th>
                    </tr>
                    </thead>
                </table>

                {{-- Notes --}}
                <div class="mt-4 small text-muted">
                    <p>• Only new or pending items are shown in the list</p>
                    <p>• When resolving items, owner is set to current recruiter</p>
                </div>
            </div>

            {{-- Footer --}}
            <div class="modal-footer d-flex flex-column align-items-start">
                <div id="resolveHelper" class="text-danger fw-bold mb-2" style="display:none;"></div>
                <div class="d-flex w-100 justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Skip</button>
                    <button type="button" id="resolveSelectedLeads" class="btn btn-success">Resolve Selected Leads</button>
                </div>
            </div>
        </div>
    </div>
    <div id="resolveHelper" class="mt-2 text-danger fw-bold" style="display:none;"></div>
</div>
