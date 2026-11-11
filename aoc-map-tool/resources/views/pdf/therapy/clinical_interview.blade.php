@php
    $title = 'Clinical Interview';
    use App\Services\Pdf\Therapy\Steps\ClinicalInterviewPdfStep;
    use App\Helpers\Pdf\OfferValueFormatter;
@endphp
@extends('pdf.therapy._layout')

@section('content')

<table>
    <tr><th>Full Name</th><td>{{ $candidate->fullName }}</td></tr>
    <tr><th>Title / Discipline</th><td>{{ $candidate->title }}</td></tr>
    <tr><th>Interview Date</th><td>{{ $candidate->clinicalInterviewDate ? \Carbon\Carbon::parse($candidate->clinicalInterviewDate)->format('m/d/Y H:i') : '—' }}</td></tr>
    @foreach(ClinicalInterviewPdfStep::INTERVIEW_LABELS as $key => $label)
    <tr>
        <th>{{ $label }}</th>
        <td class="answer">{{ $interview->interview[$key] ?? '—' }}</td>
    </tr>
    @endforeach
    @foreach($fields as $key => $field)
        @if(isset($offerValues[$key]))
        <tr>
            <th>{{ $field->label }}</th>
            <td>{{ OfferValueFormatter::format($offerValues[$key]) }}</td>
        </tr>
        @endif
    @endforeach
</table>

@endsection
