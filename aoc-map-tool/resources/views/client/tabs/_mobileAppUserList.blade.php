<div class="col-md-12 flex-wrap alert alert-success error-msg" style="display:none" id="saveUserSuccessMsg"></div>
<div class="col-md-12 flex-wrap alert alert-danger error-msg" style="display:none" id="deleteUserError"></div>
<div class="col-md-12 p-0 mt-3">
    <div class="col-md-6 pb-1">
                <button type="button" onclick="showAddMobileUserForm();" class="btn btn-primary">Add</button>
            </div>
    <table class="table" aria-describedby="">
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Mobile</th>
            <th>Action</th>
        </tr>
        @if(!$mobileExistingUser->isEmpty())
        @foreach($mobileExistingUser as $mobileUser)
        <tr>
            <td>{{ $mobileUser->name }}</td>
            <td>{{ $mobileUser->email }}</td>
            <td>{{ $mobileUser->mobile_number }}</td>
            <td><a href="javascript:void(0);" onclick="removeMobileUser('{{ $mobileUser->aoc_patient_id }}','{{ $mobileUser->id }}')"><i class="fa fa-trash-o"></i></a></td>
        </tr>
        @endforeach
        @else
        <tr>
            <td></td>
            <td>No Record found</td>
            <td></td>
            <td></td>
        </tr>
        @endif
    </table>
</div>