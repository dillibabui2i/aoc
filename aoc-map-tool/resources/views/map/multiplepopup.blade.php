<?php
namespace App\Helpers;

$selectedMap = H::getSelectedMapInfo();
$fieldsJson = json_decode($selectedMap[0]->fields_on_map);
$map_boolean_field = json_decode($selectedMap[0]->map_boolean_field,true);
?>





<div class="map-detial-heading">
	<div class="main-navigation-map col-md-6 pull-left p-0">
		<a href="#" id="prev" class="previous round"><i class="fa fa-chevron-left"></i></a>
		<a href="#" id="next" class="next round"><i class="fa fa-chevron-right"></i></a>
	</div>

	<div class="map-detail-counter col-md-6 pull-right text-right">
		<span id="map-cntr">1</span> of <?php echo count($masterMapData)?>
	</div>
</div>

<div style="clear:both">
</div>

<div class="slideshow">

<?php
$cntr = 0;
$display = "block";
$class = "";
foreach($masterMapData as $mapData) {
	if($cntr==0)  {
		$class = "active";
	} else {
		$class = "";
	}
	$cntr++;
?> 

<div class="map-detail item <?php echo $class?>"> 

	<div class="card border-light">
	<div class="card-header">
			<?php 
				if(!empty($fieldsJson->card_heading)) { 
			
					foreach($fieldsJson->card_heading as $key => $value){
						if($value!=null) {
							echo {!! $mapData->$value !!}." ";	
						}
					}
				
				}
				
			?>
		</div>
	<div class="card-body">
		<p class="card-text">
		<table>
			<tr>
			<td colspan="2" style="font-size:14px;">
				<?php 
				if(!empty($fieldsJson->card_subheading)) { 
					foreach($fieldsJson->card_subheading as $key => $value){
						if($value!=null) {
							echo {!! $mapData->$value !!}." ";	
						}
					}
				}
				?>
			</td>
			</tr>

			<?php 
			if(!empty($fieldsJson->fields)) { 
				
				foreach($fieldsJson->fields as $key => $value){ 
					
					if($value->name!=null){ 
				?>
				<?php if($mapData->{$value->name} !=''){?>
						<tr>
							<td>
								<strong>{{$value->title}} :</strong>
							</td>
							@php
								if (is_array($map_boolean_field) && in_array($value->name, $map_boolean_field)) {
									if ($mapData->{$value->name} === "0") {
										$output = "NO";
									} elseif ($mapData->{$value->name} === "1") {
										$output = "YES";
									} else {
										$output = $mapData->{$value->name};
									}
								} else {
									$output = $mapData->{$value->name};
								}
							@endphp
							<td>&nbsp; {{ $output }}</td>

						</tr>
					<?php } } ?>
					
				<?php }
			}
			?>
			
			
		</table>
		</p>
	</div>
	</div>
</div>
<?php } ?>
</div>

