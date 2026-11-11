@php
    use App\Enums\TrachVentClassClinicianEmailEnum;

    $font = '"Myriad Pro","Segoe UI",Arial,Helvetica,sans-serif';
    $isCancelled = $type === TrachVentClassClinicianEmailEnum::CANCELLED;
    $isRescheduled = $type === TrachVentClassClinicianEmailEnum::RESCHEDULED;

    $banner = match ($type) {
        TrachVentClassClinicianEmailEnum::REGISTERED => ['Your registration is confirmed.', '#225daa'],
        TrachVentClassClinicianEmailEnum::CANCELLED => ['Your registration has been canceled.', '#0a2d5a'],
        TrachVentClassClinicianEmailEnum::RESCHEDULED => ['Your registration has been rescheduled.', '#0393e0'],
    };
    $boxTitle = match ($type) {
        TrachVentClassClinicianEmailEnum::REGISTERED => 'Class details',
        TrachVentClassClinicianEmailEnum::CANCELLED => 'Canceled class details',
        TrachVentClassClinicianEmailEnum::RESCHEDULED => 'New class details',
    };
    $labelStyle = "font-family:{$font};font-size:13px;color:#4a5d78;margin-top:14px;";
    $valueStyle = "font-family:{$font};font-size:15px;line-height:21px;color:#0a2d5a;";
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;padding:0;background:#f4f6f9;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;">
    <tr>
        <td align="center" style="padding:16px;">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="width:100%;max-width:640px;background:#ffffff;border:1px solid #dcdfe4;border-radius:4px;">
                <tr>
                    <td align="center" style="padding:26px 28px 18px;border-bottom:4px solid #ffc028;background:#ffffff;">
                        <img src="{{ asset('images/blue-logo.png') }}" alt="Angels of Care Pediatric Home Health" width="260" style="display:block;border:0;outline:none;text-decoration:none;max-width:100%;height:auto;">
                    </td>
                </tr>
                <tr>
                    <td style="font-family:{{ $font }};color:#0a2d5a;padding:26px 32px 30px;">
                        <div style="font-family:{{ $font }};font-size:15px;margin-bottom:6px;color:#0a2d5a;">Hi {{ $clinicianName }},</div>
                        <div style="font-family:{{ $font }};font-size:22px;font-weight:700;margin:14px 0 4px;line-height:1.25;color:{{ $banner[1] }};">{{ $banner[0] }}</div>
                        @if ($isCancelled)
                            <div style="{{ $valueStyle }}margin-top:6px;">This is to inform you that your spot in the class below has been canceled.</div>
                        @endif
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0;">
                            <tr>
                                <td style="background:#caecff;border-left:4px solid #225daa;border-radius:3px;padding:2px 18px 18px;">
                                    <h4 style="font-family:{{ $font }};font-size:15px;font-weight:700;margin:14px 0 6px;color:#0a2d5a;">{{ $boxTitle }}</h4>
                                    <div style="{{ $labelStyle }}">Class Type</div>
                                    <div style="{{ $valueStyle }}">{{ $class->classType }} Class</div>
                                    <div style="{{ $labelStyle }}">When</div>
                                    <div style="{{ $valueStyle }}">{{ $class->longDate }}<br>{{ $class->timeRange }}</div>
                                    @if ($class->location !== '')
                                        <div style="{{ $labelStyle }}">Location</div>
                                        <div style="{{ $valueStyle }}">{{ $class->location }}</div>
                                    @endif
                                    <div style="{{ $labelStyle }}">Instructor</div>
                                    <div style="{{ $valueStyle }}">{{ $class->instructor }}</div>
                                    @if ($isRescheduled && $previousClass)
                                        <div style="{{ $labelStyle }}">Previously scheduled</div>
                                        <div style="{{ $valueStyle }}color:#6b7c93;text-decoration:line-through;">{{ $previousClass->longDate }}, {{ $previousClass->timeRange }}</div>
                                    @endif
                                </td>
                            </tr>
                        </table>
                        @unless ($isCancelled)
                            <div style="border-left:4px solid #ffc028;padding:4px 0 4px 14px;margin-top:20px;font-family:{{ $font }};font-size:15px;line-height:21px;color:#0a2d5a;">
                                To cancel or reschedule your class, please call us{{ $class->branchPhone !== '' ? ' at ' . formatPhone($class->branchPhone) : '' }}.
                            </div>
                        @endunless
                    </td>
                </tr>
                <tr>
                    <td align="center" style="background:#0a2d5a;color:#ffffff;padding:18px 24px;font-family:{{ $font }};font-size:13px;line-height:1.6;">
                        <b style="font-size:14px;font-weight:700;color:#ffffff;">Angels of Care Pediatric Home Health</b>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
