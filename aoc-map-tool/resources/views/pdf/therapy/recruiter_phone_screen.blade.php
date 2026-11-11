@php $title = 'Recruiter Phone Screen'; @endphp
@extends('pdf.therapy._layout')

@section('content')

<table>
    <tr><th>Full Name</th><td>{{ $candidate->fullName }}</td></tr>
    <tr><th>Email</th><td>{{ $candidate->email }}</td></tr>
    <tr><th>Mobile</th><td>{{ $candidate->mobile }}</td></tr>
    <tr><th>Home Phone</th><td>{{ $candidate->homePhone }}</td></tr>
    <tr><th>Title / Discipline</th><td>{{ $candidate->title }}</td></tr>
    <tr><th>Screen Date</th><td>{{ $candidate->recruiterPhoneScreenDate ? \Carbon\Carbon::parse($candidate->recruiterPhoneScreenDate)->format('m/d/Y') : '—' }}</td></tr>
    <tr><th>Recruiter</th><td>{{ $screening->recruiter->name ?? '—' }}</td></tr>
    <tr><th>Licensed</th><td>{{ $screening->licensing ? 'Yes' : 'No' }}</td></tr>
    <tr><th>Workload (visits/wk)</th><td>{{ $workloadLabel }}</td></tr>
    <tr><th>Visits per Week</th><td>{{ $screening->visits }}</td></tr>
    <tr><th>Pay Expectations</th><td>{{ $screening->pay_expectations ? '$' . number_format($screening->pay_expectations, 2) : '—' }}</td></tr>
    <tr><th>Bilingual</th><td>{{ $screening->bilingual ? 'Yes' : 'No' }}</td></tr>
    <tr><th>Can Supervise</th><td>{{ $screening->supervise ? 'Yes' : 'No' }}</td></tr>
    <tr><th>Qualified</th><td>{{ $screening->qualified ? 'Yes' : 'No' }}</td></tr>
    <tr><th>Available Start Date</th><td>{{ $screening->start_date ? \Carbon\Carbon::parse($screening->start_date)->format('m/d/Y') : '—' }}</td></tr>
    <tr><th>Experience</th><td class="answer">{{ $screening->experience ?: '—' }}</td></tr>
    <tr><th>Areas Wishing to Cover</th><td class="answer">{{ $screening->areas_wishing_to_cover ?: '—' }}</td></tr>
    <tr><th>Strengths</th><td class="answer">{{ $screening->strengths ?: '—' }}</td></tr>
    <tr><th>Areas of Growth</th><td class="answer">{{ $screening->areas_of_growth ?: '—' }}</td></tr>
</table>

@endsection
