<?php 
use App\Helpers\H;
?>

<x-modal>
    <x-slot name="id">whiteboard-popup</x-slot>
    <x-slot name="size">w100</x-slot>
    <x-slot name="title">Whiteboard</x-slot>
   
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row d-flex">
            <div class="col-md-12 popup-table-container notes-form">
                <?php if(isset($results) && is_array($results) && !empty($results)) {?>
                <table id="popup-datatable" class="table table-striped" width="100%">
                    <thead> 
                        <tr>
                            <?php foreach($results[0] as $key=> $res) { ?> 
                            <th class="tbl-heading dt-nowrap">
                                <?php if($key=='New_Current') {
                                    echo 'New or Current';
                                } else if($key=='Client_Name') {
                                    echo "Client Name";
                                } else  {
                                    echo $key;
                                } ?>
                                    
                            </th>
                            <?php  } ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $cntr=0;
                        foreach($results as $r) { 

                            $class = ($cntr%2)?"even":"odd";
                            $cntr++;
                        ?>
                    
                        <tr>
                            <?php 
                            foreach($r as $key=> $res) { 
                                    echo '<td class="tbl-body dt-nowrap">';
                                    if($key=='Scheduled Date' || $key=='Appointment Date' || $key=='CandidateStart'  ) {
                                       echo H::getFormatedDate($res);
                                    } else if($key=='Time' || $key=='CandidateStartTime'){
                                        echo H::getFormatedTime($res);
                                    } else {
                                        echo $res;
                                    }
                             
                                echo "</td>";
                              } ?>
                        </tr>
                    
                    <?php } ?>
                    </tbody>
                </table>
                <?php } ?>
             
            </div>
        </div> 
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
<script>
$(document).ready(function(){
    $('#popup-datatable').dataTable({
    "ordering": false
});
});
</script>
<style>
    .w35 {
        width:35%;
    }
    .w25 {
        width:25%;
    }
    .tbl-heading {
        background-color: #215eac;
        color: #fff;
    }
    
</style>

