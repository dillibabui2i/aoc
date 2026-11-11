<?php
namespace App\Helpers;
$selectedMap = $mapInfo ?? H::getSelectedMapInfo();
$fieldsJson = json_decode($selectedMap[0]->fields_on_map);
$map_boolean_field = json_decode($selectedMap[0]->map_boolean_field,true);


?>

<div class="card border-light">
  <div class="card-header">
			@if(!empty($fieldsJson->card_heading))

				@foreach($fieldsJson->card_heading as $key => $value)
					@if($value!=null)
						{{ $mapData->$value." "}}
					@endif
				@endforeach

			@endif
	</div>
  <div class="card-body">
    <p class="card-text">
      <table>
        <tr>
          <td colspan="2" style="font-size:14px;">
			@if(!empty($fieldsJson->card_subheading))
				@foreach($fieldsJson->card_subheading as $key => $value)
					@if($value!=null)
						{{$mapData->$value." "}}
					@endif
				@endforeach

			@endif
          </td>
        </tr>

		@isset($fieldsJson->fields)
			@foreach ($fieldsJson->fields as $key => $value)
				@if (!is_null($value->name) && !empty($mapData->{$value->name}))
					<tr>
						<td>
							<strong>{{ $value->title }}:</strong>
						</td>
						<td>
							&nbsp;
							@if (is_array($map_boolean_field) && in_array($value->name, $map_boolean_field))
								@switch($mapData->{$value->name})
									@case('0')
										NO
										@break
									@case('1')
										YES
										@break
									@default
										{{ $mapData->{$value->name} }}
								@endswitch
							@else
								{{ $mapData->{$value->name} }}
							@endif
						</td>
					</tr>
				@endif
			@endforeach
		@endisset
      </table>
    </p>
  </div>
</div>