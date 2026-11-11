<style>
    #outcomeForm {
        min-height: 1450px !important;
    }
</style>

<div class="row mt-3">
    <input type="hidden" name="isTherapy" value="{{ $isTherapy }}">
    <div class="col-md-12">
        <label for="experience">Work Experience</label>
        <textarea class="form-control required-field" rows="5" name="experience" id="experience"></textarea>
    </div>
    <div class="col-md-12 mt-3">
        <label for="licensing">Licensing</label>
        <select class="form-control required-field mb-3" id="licensing" name="licensing">
            <option value="" selected>-- Select licenses --</option>
            <option value="0">SLP</option>
            <option value="1">SLP-CF</option>
            <option value="2">SLPA</option>
            <option value="3">OT</option>
            <option value="4">COTA</option>
            <option value="5">PT</option>
            <option value="6">PTA</option>
        </select>
        <span class="mt-5">Note: Must pass the praxis, national exam for Colorado for Speech CF's</span>
    </div>

    <div class="col-md-12 mt-3 mb-0">
        <p><input type="checkbox" id="checkbox-bilingual" name="bilingual"> <laber for="checkbox-bilingual">Bilingual</laber></p>
    </div>

    <div class="col-md-12 mt-1">
        <label for="areas_wishing_to_cover">Areas wishing to cover</label>
        <textarea class="form-control required-field" rows="5" name="areas_wishing_to_cover" id="areas_wishing_to_cover"></textarea>
    </div>

    <div class="col-md-12 mt-3">
        <label for="workload">Desired workload</label>
        <select class="form-control required-field mb-3" id="workload" name="workload">
            <option value="" selected>-- Select workload --</option>
            <option value="0">0-5</option>
            <option value="1">5-10</option>
            <option value="2">10-20</option>
            <option value="3">20-30</option>
            <option value="4">30+</option>
        </select>
        <span class="mt-5">Note: Must pass the praxis, national exam for Colorado for Speech CF's</span>
    </div>

    <div class="col-md-8 mt-3">
        <label for="visits"># Visits per week</label>
        <input type="text" class="form-control required-field mb-2" id="visits" name="visits" value="" />
        <p>
            <span>
                Note: <br>
                *Part Time = 5 visits MINIMUM <br>
                *Full Time = 20 visits per week - eligible for benefits, PTO, & Vehicle Allowance <br>
                @ 30 visits per week - eligible for benefits, PTO, Vehicle Allowance, CEU, & License Reimbursement
            </span>
        </p>
    </div>
    <div class="col-md-4 mt-3">
        <input type="checkbox" id="supervise" name="supervise" style="margin-top: 40px"> <label for="supervise">Willing to supervise</label>
    </div>

    <div class="col-md-12 mt-3">
        <label for="strengths">Strengths</label>
        <textarea class="form-control required-field" rows="5" name="strengths" id="strengths"></textarea>
    </div>

    <div class="col-md-12 mt-3">
        <label for="areas_of_growth">Areas of Growth</label>
        <textarea class="form-control required-field" rows="5" name="areas_of_growth" id="areas_of_growth"></textarea>
    </div>

    <div class="col-md-12 mt-3">
        <label for="pay_expectations">Pay Expectations</label>
        <input type="text" class="form-control required-field mb-2" id="pay_expectations" name="pay_expectations" value="" />
        <p><span>Note: We pay per session - Will be broken down by rates for evals, re-evals, and threatment visits</span></p>
    </div>

    <div class="col-md-3 mt-3">
        <label for="datepicker">Start Date</label>
        <input type="text" class="form-control required-field" id="datepicker" autocomplete="off" name="start_date" value="" />
    </div>
    <div class="col-md-9 mt-3">
        <label for="qualified">Qualified / Unqualified Candidate</label>
        <select class="form-control required-field mb-3" id="qualified" name="qualified">
            <option value="" selected>-- Select option --</option>
            <option value="0">Unqualified</option>
            <option value="1">Qualified</option>
        </select>
    </div>


</div>
<script>
    $('#datepicker').datepicker({
        onSelect: function () {
            $(this).valid();
        }
    }).on('change', function () {
        $(this).valid();
    });

    $('#datepicker').on('click', function () {
        $('#ui-datepicker-div').appendTo($(this).parent());
    });

    $.validator.addClassRules('required-field', {
        required: true,
        minlength: 1
    });

    $('#jobViteOutcomeForm').validate({
        ignore: [],
        onfocusout: false,
        invalidHandler: function(form, validator) {
            if (validator.numberOfInvalids()) {
                validator.errorList[0].element.focus();
            }
        },
        errorPlacement: function(error, element) {}
    });
</script>
