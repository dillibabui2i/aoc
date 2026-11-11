<x-modal>
    <x-slot name="id">tagFormModal</x-slot>
    <x-slot name="size">w-50</x-slot>
    <x-slot name="title">{{ isset($tag) ? 'Edit Tag' : 'Create Tag' }}</x-slot>
    <x-slot name="body">
        <form id="tagForm">
            @csrf
            @if(isset($tag))
                <input type="hidden" name="id" value="{{ $tag->id }}">
                <input type="hidden" name="_method" value="PUT">
            @endif

            <div class="form-group">
                <label>Tag Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $tag->name ?? '') }}">
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control">{{ old('description', $tag->description ?? '') }}</textarea>
            </div>

            @if(isset($tag))
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="enabled" {{ $tag->status === 'enabled' ? 'selected' : '' }}>Enabled</option>
                        <option value="disabled" {{ $tag->status === 'disabled' ? 'selected' : '' }}>Disabled</option>
                    </select>
                </div>
            @endif

            @if(!empty($activityLogs) && count($activityLogs) > 0)
                <div class="form-group">
                    <label>Activity Log</label>
                    <div style="width: 100%; overflow-y: scroll; height: 225px;">
                            <table style="width: 100%;border: 1px solid #dee2e6;" class="table table-striped" summary="User Form" aria-hidden="true">
                                @foreach ($activityLogs as $log)
                                    <tr>
                                        <td class="text-left">{!! nl2br(e($log['log_info'])) !!}</td>
                                        <td class="text-left">{{date("M d Y", strtotime($log['created_at'])) . ' at ' . date("g:i A", strtotime($log['created_at']))}}</td>
                                    </tr>
                                @endforeach
                            </table>
                    </div>
{{--                    <div class="border p-2" style="max-height: 150px; overflow-y: auto; font-size: 14px;">--}}
{{--                        <ul class="list-unstyled mb-0">--}}
{{--                            @foreach($activityLogs as $log)--}}
{{--                                <li class="mb-1">--}}
{{--                                    <div>{!! nl2br(e($log['log_info'])) !!}</div>--}}
{{--                                    <small class="text-muted">{{ \Carbon\Carbon::parse($log['created_at'])->format('M d, Y h:i A') }}</small>--}}
{{--                                </li>--}}
{{--                            @endforeach--}}
{{--                        </ul>--}}
{{--                    </div>--}}
                </div>
            @endif

            <div class="form-group text-center">
                <button type="submit" class="btn btn-primary">{{ isset($tag) ? 'Update' : 'Save' }}</button>
            </div>
        </form>
    </x-slot>
</x-modal>