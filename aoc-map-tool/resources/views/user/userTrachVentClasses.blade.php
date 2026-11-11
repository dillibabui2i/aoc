<p>Here is the list of classes to be reassigned:</p>
@foreach($classes as $class)
    <div>
        <a href="{{ route('managetrachventclass.edit', $class['class_id']) }}">
            {{ implode(' - ', array_filter([
                \Carbon\Carbon::parse($class['class_date'])->format('m/d/Y'),
                $class['class_time'],
                $class['class_type']])
                ) }}
        </a><br>
    </div>
@endforeach

