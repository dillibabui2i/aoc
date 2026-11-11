<?php
use App\Helpers\H;
use App\Helpers\LD;

 ?>

                <div class="card">
                    <div class="card-header" id="dailyactivityone">
                        <h4 class="mb-0">
                            <button class="col-md-10 pull-left btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
                                data-target="#unsent_documents" aria-expanded="false" aria-controls="collapseOne">
                                Field Evaluations <span id="unsent_documents-counter"></span><i class="fa fa-chevron-right"></i>
                            </button>
                            <div class="col-md-2 text-right pull-left"><a href="{{route('fieldevaluation.list')}}" class="dashboard-showall" title="Show All Field Evaluations">Show All Field Evaluations</a></div>
                        </h4>
                    </div>
                    <div id="unsent_documents" class="collapse" aria-labelledby="dailyactivityone" data-parent="#leadership_childs">
                        <div class="card-body">
                            <?php echo H::getHelper("ClinicianCareManager")->getDataTableHtml('unsent_documents') ?>
                        </div>
                    </div>
                </div>
               
    <script>
        const clinical_case_manager = ['unsent_documents'];
 
         for(var i=0; i < clinical_case_manager.length;i++) {
             var tempObject = clinical_case_manager[i].replace(/ /g,"_");
             if($("#reload-"+tempObject).val()==1) {
                 let _token = $('meta[name="csrf-token"]').attr('content');
                 $.ajax({
                     url: "{{ route('fieldevaluation.reloadunsentdocuments') }}?type="+clinical_case_manager[i],
                     type: "POST",
                     data: {
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
                 $("#dataTable-clinician-eval-"+tempObject).DataTable().ajax.reload();
             }
         }

         function editFieldEvaluation(id) {
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{route('fieldevaluation.edit')}}",
                type: 'get',
                dateType:"html",
                data: {
                    _token: _token,
                    id: id
                },
                success: function(data) {
                    $("#tempModalCnt").remove();
                    var $div = $('<div />').appendTo('body');
                    $div.attr('id', 'tempModalCnt').html(data);
                    $('#fieldEvaluationFormModal').modal('show');
                }
            });
        }
    
     </script>