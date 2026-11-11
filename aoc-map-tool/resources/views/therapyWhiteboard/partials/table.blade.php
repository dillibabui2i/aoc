@php
    use App\Helpers\H;

    $hasPermission = H::canPermissions('edit-whiteboard-row');
@endphp
<div id="tab-{{ $day }}" class="whiteboard-table">
    <div class="mb-2 text-left">
        <a href="javascript:void(0)" onclick="collapseExpandAllTab(this)"><span
                class="badge badge-primary"><span>Expand</span> All State</span></a>
    </div>

    <table class="table table-bordered" style="font-size: 12px !important;">
        <thead>
        @include('therapyWhiteboard.partials.header')
        </thead>

        <tbody>
        @foreach($states as $stateCode => $state)

            {{-- STATE TOTALS --}}
            <tr class="state-row"
                data-state="{{ $stateCode }}"
                style="background:#215eac;color:#fff;font-weight:bold;cursor:pointer;">
                <td>{{ $state['stateName'] }}</td>
                <td></td>
                <td></td>
                <td>{{ $state['metrics']['candidate_weekly_start_goal'] }}</td>
                <td>{{ $state['metrics']['percent_to_goal'] }}</td>
                <td>{{ $state['metrics']['phone_screens_scheduled'] }}</td>
                <td>{{ $state['metrics']['clinical_interviews_scheduled'] }}</td>
                <td>{{ $state['metrics']['offers_extended'] }}</td>
                <td>{{ $state['metrics']['offers_accepted'] }}</td>
                <td>{{ $state['metrics']['offers_rejected'] }}</td>
                <td>{{ $state['metrics']['hires_onboardings'] }}</td>
                <td>{{ $state['metrics']['qualified_candidates'] }}</td>
                <td>{{ $state['metrics']['unqualified_candidates'] }}</td>
            </tr>

            @foreach($state['recruiters'] as $recruiterRow)
                @php
                    $recruiter = $recruiterRow['data'];
                    $metrics = $recruiterRow['metrics'];
                @endphp

                {{-- RECRUITER --}}
                <tr class="recruiter-row hidden"
                    data-parent-state="{{ $stateCode }}"
                    data-recruiter="{{ $recruiter['userId'] }}"
                    style="background:#eaf4ff; cursor:pointer;">
                    <td></td>
                    <td>{{ $recruiter['name'] }}</td>
                    <td>{{ $recruiter['region'] }}</td>
                    <td>{{ $metrics['candidate_weekly_start_goal'] }}</td>
                    <td>{{ $metrics['percent_to_goal'] }}</td>
                    <td>{{ $metrics['phone_screens_scheduled'] }}</td>
                    <td>{{ $metrics['clinical_interviews_scheduled'] }}</td>
                    <td>{{ $metrics['offers_extended'] }}</td>
                    <td>{{ $metrics['offers_accepted'] }}</td>
                    <td>{{ $metrics['offers_rejected'] }}</td>
                    <td>{{ $metrics['hires_onboardings'] }}</td>
                    <td>{{ $metrics['qualified_candidates'] }}</td>
                    <td>{{ $metrics['unqualified_candidates'] }}</td>
                </tr>

                @foreach($recruiterRow['regions'] as $region)
                    @php
                        $regionMetrics = $region['metrics'];
                    @endphp

                    {{-- REGION --}}
                    <tr class="subrow hidden"
                        data-parent-recruiter="{{ $recruiter['userId'] }}"
                        data-parent-state="{{ $stateCode }}"
                        style="background:#ffffff;">
                        <td></td>
                        <td></td>
                        <td>{{ $region['title'] }}</td>
                        <td>
                            @if($hasPermission)
                                <span
                                    class="att-value editable-text editable editable-click"
                                    data-emptytext="Click to add candidate weekly start goal"
                                    data-type="number"
                                    data-url="{{route("editable")}}"
                                    data-pk="{{ $region['rowId'] }}"
                                    data-name="candidate_weekly_start_goal"
                                    data-value="{{ $regionMetrics['candidate_weekly_start_goal'] }}"
                                    data-table="whiteboradDetails"
                                    style=""
                                >
                                {{ $regionMetrics['candidate_weekly_start_goal'] }}
                            </span>
                            @else
                                <span>{{ $regionMetrics['candidate_weekly_start_goal'] }}</span>
                            @endif
                        </td>
                        <td>{{ $regionMetrics['percent_to_goal'] }}</td>
                        <td>{{ $regionMetrics['phone_screens_scheduled'] }}</td>
                        <td>{{ $regionMetrics['clinical_interviews_scheduled'] }}</td>
                        <td>{{ $regionMetrics['offers_extended'] }}</td>
                        <td>{{ $regionMetrics['offers_accepted'] }}</td>
                        <td>{{ $regionMetrics['offers_rejected'] }}</td>
                        <td>{{ $regionMetrics['hires_onboardings'] }}</td>
                        <td>{{ $regionMetrics['qualified_candidates'] }}</td>
                        <td>{{ $regionMetrics['unqualified_candidates'] }}</td>
                    </tr>
                @endforeach
            @endforeach

        @endforeach

        {{-- NATIONAL TOTALS --}}
        <tr style="background:#d5d5d5;font-weight:600;">
            <td>National Totals</td>
            <td></td>
            <td></td>
            <td>{{ $nationalTotals['candidate_weekly_start_goal'] }}</td>
            <td>{{ $nationalTotals['percent_to_goal'] }}</td>
            <td>{{ $nationalTotals['phone_screens_scheduled'] }}</td>
            <td>{{ $nationalTotals['clinical_interviews_scheduled'] }}</td>
            <td>{{ $nationalTotals['offers_extended'] }}</td>
            <td>{{ $nationalTotals['offers_accepted'] }}</td>
            <td>{{ $nationalTotals['offers_rejected'] }}</td>
            <td>{{ $nationalTotals['hires_onboardings'] }}</td>
            <td>{{ $nationalTotals['qualified_candidates'] }}</td>
            <td>{{ $nationalTotals['unqualified_candidates'] }}</td>
        </tr>

        </tbody>
    </table>
</div>
