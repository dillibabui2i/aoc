
<?php 
use App\Helpers\H;
?>
<x-modal>
    <x-slot name="id">coverage_delete_popup</x-slot>
    <x-slot name="title">Delete Coverage Tracker</x-slot>
    <x-slot name="size">w40</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="modal-body" id="scheduleNextSuccessDiv" style="display: none">
            <div id="successBody"></div>
            <div class="row pt-2">
                <div class="col-md-7 text-right">
                    <button type="button" onclick="closeForm()" class="btn btn-primary">Close</button>
                </div>
            </div>
        </div>

        <div class="modal-body" id="scheduleNextFormDiv">
            
            <form method="post" autocomplete="off" id="delete_coverage_form" class="scaffold-form"  enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="coverageId" id="coverageId" value="<?php echo $coverage?>"/>

                <div class="col-md-12  pt-2">
                    Are you sure you want to delete the <?php echo $coverageData->Schedule_Time?> shift on <?php echo H::getFormatedDate($coverageData->shift_date)?> for <?php echo $coverageData->Client_First_Name?> <?php echo $coverageData->Client_Last_Name?> ?
                </div>
                <div class="col-md-12  pt-2">
                    <select required="true" class="form-control" id="reason_category" name="reason_category">
                        <option value="">Select Catgegory</option>
                        <option value="Parent Cancels">Parent Cancels</option>
                        <option value="Clinician cancels">Clinician cancels</option>
                        <option value="AOC Reasons">AOC Reasons</option>
                    </select>
                </div>
                
                <div class="col-md-12  pt-2">
                    <select required="true" class="form-control" required="true" id="reason" name="reason">
                        <option value="">Select Reason</option>
                    </select>
                </div>

                <div id="candidate_save" class="row pt-2">
                    <div class="col-md-7 text-right">
                        <button type="button" id="candidateSaveBtn" onclick="deleteCoverageTrackerRecord(this)" class="btn btn-primary">Yes</button>
                        <button type="button" id="candidateSaveBtn" onclick="hideCoverageTrackerPopup(this)" class="btn btn-primary">No</button>
                    </div>
                </div>
            </form>
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>
