<a href="{{ url('/managejobcategory/edit', $jobCategory->id) }}" class="edit">
    <i class="fa fa-edit" title="Edit"></i>
</a>

&nbsp;|&nbsp;

<a href="javascript:void(0)" class="toggle-status"
   data-href="{{ url('/managejobcategory/toggle', $jobCategory->id) }}">
    @if($jobCategory->is_enabled)
        <i class="fa fa-toggle-on text-success" title="Disable"></i>
    @else
        <i class="fa fa-toggle-off text-muted" title="Enable"></i>
    @endif
</a>