
    <div class="col-md-12">
        <div class="form-group row">
            <label  class="w-100" for="result">Result:</label>
            <select class="form-control  result" name="result" id="result" multiple data-placeholder="Select Result" data-live-search="true">
                <option value="Left Voicemail">Left Voicemail</option>
                <option value="Sent Text">Sent Text</option>
                <option value="Sent Email">Sent Email</option>
            </select>
        </div>
    
        <div class="col-md-12 p-0 nonadmitfield ">
            <div class="form-group row">
                    <label for="activecoverage_comment">Comments</label>
                    <textarea
                        name="activecoverage_comment"
                        id="activecoverage_comment"
                        class="form-control"></textarea>
            </div>
        </div>
    </div>

<script>
     $('#activeCoverageOutcomeForm #result').multiselect({
        includeSelectAllOption: true,
        maxHeight: 135
    });
</script>