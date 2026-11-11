@php
    use App\Enums\Adjustments\AdjustmentEventTypeEnum;
    use App\Enums\Adjustments\AdjustmentStageEnum;
    use App\Helpers\H;
@endphp

<div class="col-md-6 pt-3 pl-3">
    <h1>Adjustment Request</h1>
</div>
<div class="col-md-3 table-wrapper" id="invoiceDetailsTable">
    <table aria-describedby="mydesc">
        <thead>
        <tr>
            <th scope="col">Level</th>
            <th scope="col">Outcome</th>
            <th scope="col">Date</th>
        </tr>
        </thead>
        <tbody>
        @php
            $levels = [
                'Approval Level 1' => AdjustmentStageEnum::LEVEL_1->value,
                'Approval Level 2' => AdjustmentStageEnum::LEVEL_2->value,
                'Approval Level 3' => AdjustmentStageEnum::LEVEL_3->value,
                'Operations'       => AdjustmentStageEnum::OPERATIONS->value,
                'AR Supervisor' => AdjustmentStageEnum::AR_SUPERVISOR->value,
            ];
        @endphp

        @foreach ($levels as $level => $stage)
            @php
                $history = $adjustmentHistoryByStage->get($stage);
            @endphp

            <tr>
                <td>
                    {{ $level }}
                </td>

                <td>
                    @if ($history)
                        @if($history->event_type === AdjustmentEventTypeEnum::COMPLETED)
                            Completed
                        @elseif ($history->event_type === AdjustmentEventTypeEnum::APPROVED)
                            Approved
                        @elseif ($history->event_type === AdjustmentEventTypeEnum::REJECTED)
                            Rejected
                        @endif
                    @endif
                </td>

                <td>
                    @if ($history)
                        {{ H::getFormatedDate($history->created_at) }}
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
