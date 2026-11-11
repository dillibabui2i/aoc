<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Job Offer Letter - {{ $offer->get('employee')->value_string }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
        img {
            width: 30%;
        }
        h1 { font-size: 18px; margin-bottom: 10px; text-align: center; text-decoration: underline }
        h2 { font-size: 14px; margin-top: 20px; margin-bottom: 5px; }
        p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        td { padding: 4px 0; vertical-align: top; }
        .signature-table td { padding-top: 30px; }
        ul { margin: 5px 0 5px 20px; }
    </style>
</head>
<body>
@php
    $val = fn(string $key, string $col = 'value_int') => optional($offer->get($key))->{$col};
    $hasVal = fn(string $key, string $col = 'value_int') => ($v = optional($offer->get($key))->{$col}) !== null && $v !== 0 && $v !== '';
    $dateVal = fn(string $key) => optional($offer->get($key))->value_date?->format('m/d/Y');
@endphp
<div style="text-align: center;">
    {{-- AC15: resources/ isn't web-served, so asset()'s URL 404s inside dompdf. Read the file
         straight off disk instead — it's within dompdf's chroot (base_path()). --}}
    <img src="{{ resource_path('images/logo.png') }}">
</div>
<h1>Angels of Care Pediatric Home Health, LLC</h1>
<p>
    Dear {{ $val('employee', 'value_string') }},
</p>
<p>
    Congratulations! Angels of Care Pediatric Home Health is pleased to extend an offer of employment. Your employee agreement is as follows:
</p>
<p>
    <b>Employee:</b> {{ $val('employee', 'value_string') }}<br>
    <b>Job Title:</b> {{ $val('job_title', 'value_string') }}<br>
    <b>Direct Report:</b> {{ $val('direct_report', 'value_string') }}<br>
    <b>Effective Date:</b> {{ $dateVal('effective_date') }}<br>
    <b>Compensation:</b><br>
    @foreach($compensationItems->where('amount', '>', 0) as $item)
    <b style="margin-left: 30px">{{ $item->label() }}:</b> ${{ number_format($item->amount, 2) }}@if($item->notes) ({{ $item->notes }})@endif<br>
    @endforeach
    <b>Caseload:</b><br>
    @if($hasVal('visits_per_week'))
    <b style="margin-left: 30px">Visits per Week: Building to</b> {{ $val('visits_per_week') }} <b>visits/week</b><br>
    @endif
    @if($val('territory', 'value_string'))
    <b style="margin-left: 30px">Territory:</b> {{ $val('territory', 'value_string') }}<br>
    @endif
    <b>Benefits:</b><br>
    Full-time employees working a minimum of 80 visits per month are eligible for the following benefits. You will become eligible to enroll in the following company benefits upon achieving full-time status. Your benefit will go into effect on the first day of the month following 30 days of full-time employment.<br>
</p>
<style>
    table, tr {
        width: 100%;
    }
    td {
        width: 50%;
        text-align: center;
    }

    .sign td {
        text-align: left;
        padding-left: 30px;
    }
    .sign td:nth-child(2) {
        padding-left: 60px;
    }
</style>
<table border="0">
    <tr>
        <td>Medical</td>
        <td>Life Insurance</td>
    </tr>
    <tr>
        <td>Dental</td>
        <td>Short Term Disability</td>
    </tr>
    <tr>
        <td>Vision</td>
        <td>Supplemental Policies</td>
    </tr>
    <tr>
        <td>Paid Time Off</td>
        <td></td>
    </tr>
</table>
<p>
    Full-time employees working a minimum of 120 visits per month are eligible for the following additional benefits.<br>
    <span style="margin-left: 30px">CEU Reimbursement</span><br>
    <span style="margin-left: 30px">Professional License Reimbursement</span><br><br>
    The provisions of this offer of employment have been read by me, are understood, and the offer is herewith accepted. I understand that my employment is contingent upon completion and results of a background check and reference check. I understand that to remain employed by Angels of Care, I must maintain my board and state licenses, as applicable.  I agree that by accepting this offer letter, I must fully adhere to the policies, rules and regulations of Angels of Care Pediatric Home Health.
</p>
<br>
<table border="0" class="sign">
    <tr>
        <td>Employee Name: <span>__________________________</span></td>
        <td>Supervisor Name: <span>_______________________</span></td>
    </tr>
    <tr>
        <td>Employee Signature: <span>______________________</span></td>
        <td>Supervisor Signature: <span>___________________</span></td>
    </tr>
    <tr>
        <td>Date: <span>______________________________________</span></td>
        <td>Date: <span>___________________________________</span></td>
    </tr>
</table>
</body>
</html>
