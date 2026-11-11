@php
    $title = 'Offer Extended';
    use App\Helpers\Pdf\OfferValueFormatter;
@endphp
@extends('pdf.therapy._layout')

@section('content')

<table>
    <tr><th>Full Name</th><td>{{ $candidate->fullName }}</td></tr>
    <tr><th>Title / Discipline</th><td>{{ $candidate->title }}</td></tr>
    <tr><th>Offer Extended Date</th><td>{{ $candidate->offerExtendedDate ? \Carbon\Carbon::parse($candidate->offerExtendedDate)->format('m/d/Y') : '—' }}</td></tr>
    <tr><th>Offer Status</th><td>{{ $offer->isSent() ? 'Sent' : 'Draft' }}</td></tr>
    <tr>
        <th style="width:35%">Field</th>
        <th style="width:32%">Clinical Manager Value</th>
        <th style="width:33%">Recruiter Final Value</th>
    </tr>
    @foreach($fields as $key => $field)
        @continue(!isset($originalValues[$key]) && !isset($finalValues[$key]))
    <tr>
        <td>{{ $field->label }}</td>
        <td>{{ isset($originalValues[$key]) ? OfferValueFormatter::format($originalValues[$key]) : '—' }}</td>
        <td>{{ isset($finalValues[$key]) ? OfferValueFormatter::format($finalValues[$key]) : '—' }}</td>
    </tr>
    @endforeach
</table>

{{-- Compensation moved out of offer_values into offer_compensation_items (dynamic list, see
     AOC-4384) — the generic field loop above no longer surfaces it, so list both versions here
     explicitly. Original/final items aren't positionally paired: a recruiter can add, remove, or
     reorder items, so each version is its own list rather than a row-by-row diff. --}}
<h3 style="margin-top:20px">Compensation Items</h3>
<table>
    <tr>
        <th style="width:50%">Clinical Manager (original)</th>
        <th style="width:50%">Recruiter (final)</th>
    </tr>
    <tr>
        <td style="vertical-align:top">
            @forelse($originalCompensationItems as $item)
                {{ $item->label() }}: ${{ number_format($item->amount, 2) }}@if($item->notes) ({{ $item->notes }})@endif<br>
            @empty
                —
            @endforelse
        </td>
        <td style="vertical-align:top">
            @forelse($finalCompensationItems as $item)
                {{ $item->label() }}: ${{ number_format($item->amount, 2) }}@if($item->notes) ({{ $item->notes }})@endif<br>
            @empty
                —
            @endforelse
        </td>
    </tr>
</table>
@endsection
