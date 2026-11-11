<?php
use App\Helpers\H;
use App\Helpers\LD;
$ahJournalNotesCategorys = DB::table('journal_notes')
    ->distinct()
    ->select('category')
    ->where('category', 'LIKE', 'After%')
	->orderBy('category','asc')
    ->get();
$ahJournalNotesCategory = [];
foreach($ahJournalNotesCategorys as $key => $value) {
    $ahJournalNotesCategory[] = ["value" => $value->category, "text" => $value->category];
}
$ahJournalNotesFilter = [
    [
        'label'=>'Me Mode',
        'name'=>'ah_journal_notes_memode',
        'type'=>'checkbox',
        'value'=>true
    ],
    [
        'label'=>'State',
        'name'=>'ah_journal_notes_state',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],[
        'label'=>'Region',
        'name'=>'ah_journal_notes_region',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],[
        'label'=>'Branch',
        'name'=>'ah_journal_notes_branch',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],[
        'label'=>'Client Care Coordinator',
        'name'=>'ah_journal_notes_clientcare',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getClientCareCoordinator()
    ],
];
if(LD::checkFeatureFlag('add-category-field-and-filter-in-ahJournalNotes-bucket')){
    $ahJournalNotesFilter[] = [
            'label'=>'Category',
            'name'=>'category',
            'type'=>'multiselect',
            'source'=> $ahJournalNotesCategory
        ];
}
$ahJournalNotesFilter[] = [
        'label'=>'Payer',
        'name'=>'ah_journal_notes_payer',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getPayers()
    ];
$ahJournalNotesFilter[] = [
        'label' => 'Special Payer Group',
        'name' => 'ah_journal_notes_payer_group',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getPayerGroup()
    ];
?>
<div class="card-body">
    <div class="card">
        <div class="card-header" id="dailyactivityone">
            <h4 class="mb-0">
                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
                    data-target="#ah_journal_notes" aria-expanded="false" aria-controls="collapseOne">
                    After Hours Journal Notes <span id="ah_journal_notes-counter"></span><i class="fa fa-chevron-right"></i>
                </button>
            </h4>
        </div>
    
        <div
            id="ah_journal_notes"
            class="collapse"
            aria-labelledby="dailyactivityone"
            data-parent="#client_care_childs"
        >
            <p style="text-align: left;">
                <u>
                    <b>Responsible Party:</b> Client Care Coordinator
                </u><br>
                <b>Description:</b> Review each after hours journal note,
                complete any task associated with the note, then clear the note.<br>
                <b>Timeline:</b>
            </p>
            <div class="filters-common">
                @component('components.datatable-filters',
                [
                    'filters' => $ahJournalNotesFilter,
                    'formname' => 'ahJournalNotesForm'
                ])
                @endcomponent
            </div>
            <div class="card-body">
                <?php echo H::getHelper("AfterHours")->getAhJournalNotesDataTableHtml('ah_journal_notes')?>
            </div>
        </div>
    </div>
</div>
<script>
    const ahJournalNotes = ['ah_journal_notes'];

$(document).on('submit', '#ahJournalNotesForm', function(e) {
    e.preventDefault();
    var serializeData = $("#ahJournalNotesForm").serializeArray();
    for(var i=0; i < ahJournalNotes.length;i++) {
        var tempObject = ahJournalNotes[i].replace(/ /g,"_");
        if($("#reload-"+tempObject).val()==1) {
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{ route('afterhours.reloadAhJournalData') }}?type=" + ahJournalNotes[i],
                type: "POST",
                data: {
                    filters: serializeData,
                    _token: _token
                },
                success: function(response) {
                    var jsonObject = JSON.parse(response);
                    var type = jsonObject.type;
                    var idObject = type.replace(/ /g,"_");
                    $("#"+idObject+'-counter').html(jsonObject.counter);
                },
            });
        } else {
            $("#dataTable-"+tempObject).DataTable().ajax.reload();
        }
    }
});
$("#ahJournalNotesForm").trigger("submit");

$(document).on("change",'#ah_journal_notes_state',function(){
    var state = $(this).val().join(",");
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{ route('getstatebranch') }}",
        type: "GET",
        data: {
            state: state,
            _token: _token
        },
        success: function(response) {
            $('#ah_journal_notes_branch').empty();
            $.each(response, function( index, value ) {
                $("#ah_journal_notes_branch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
            });
            $("#ah_journal_notes_branch").multiselect("rebuild");
        },
    });
});

$(document).on("click",'#ah_journal_notes_memode',function() {
    $("#ah_journal_notes_clientcare").multiselect("clearSelection");
});
$(document).on("change",'#ah_journal_notes_clientcare',function() {
    $('#ah_journal_notes_memode').prop('checked', false);
});
$('#ah_journal_notes_state').change(function(){
    if ($(this).val().indexOf('TX') !== -1) {
        $('#ahJournalNotesForm select[name="ah_journal_notes_region"]').multiselect('enable');
    } else {
        $('#ahJournalNotesForm select[name="ah_journal_notes_region"]').multiselect('disable');
    }
})
$('#ah_journal_notes_state .clearall').click(function() {
    $('#ah_journal_notes_state sleect[name="ah_journal_notes_region"]').multiselect('disable');
});
</script>
