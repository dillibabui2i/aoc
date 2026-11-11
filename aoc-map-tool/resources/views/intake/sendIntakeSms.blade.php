@php
if(isset($data['entity_id'])){
    $clientId = $data['entity_id'];
}else{
    $clientId = $clientDetails->id;
}
use App\Helpers\ConstantHelper;
$careTeamIds = config('constant.intake_care_team_dashboard');
$careTeams = ConstantHelper::getCareTeamForIntakeSms($clientId);
$combinedCareTeam = [];
foreach ($careTeamIds as $key => $designation) {
    foreach ($careTeams as $teamMember) {
        if($key == 'bd_id' && strpos(strtolower($teamMember['designation']),"business") !== false) {
            $combinedCareTeam[$key] = $teamMember['name']. ', ' . $designation;
            break;
        } elseif ($teamMember['key'] === $key) {
            $combinedCareTeam[$key] = $teamMember['name']. ', ' . $designation;
            break;
        }
    }
}
@endphp
@canPermission(['intake.sendSmsToCareTeam'])
<div class="col-md-12 p-0 pt-4  hide smscareteam">
    <div class="col-md-8 p-0">
        <input type="checkbox" id="{{ $from_page }}_sendTextMessageCheckbox" name="send_sms_care_team" class="smscareteam_checkbox" />
        <label for="{{ $from_page }}_sendTextMessageCheckbox">Send Text Message to Care Team</label>
    </div>
    <div class="col-md-8 p-0 careTeamDropdown_div" id="{{ $from_page }}_careTeamDropdown_div" style="display: none;">
        <select multiple class="form-control selectpicker careTeamDropdown" name="careTeamRole[]" id="{{ $from_page }}_careTeamDropdown">
            @foreach ($combinedCareTeam as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>
</div>
@endcanPermission
<script>
    $(document).ready(function(){
        $('#{{ $from_page }}_sendTextMessageCheckbox').change(function(){
            if($(this).is(':checked')){
                $('#{{ $from_page }}_careTeamDropdown').prop('required', true);
                $('#{{ $from_page }}_careTeamDropdown_div').show();
            } else {
                $('#{{ $from_page }}_careTeamDropdown_div').hide();
                $('#careTeamDropdown-error').hide();
                $('#{{ $from_page }}_careTeamDropdown').prop('required', false);
                $('.careTeamDropdown').multiselect('deselectAll', false);
                $('.careTeamDropdown').multiselect('refresh');
            }
        });
        $('.selectpicker').multiselect({
            includeSelectAllOption: true,
        });
    });
</script>
