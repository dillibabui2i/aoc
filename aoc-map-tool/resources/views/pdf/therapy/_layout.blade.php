<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
    h1 { font-size: 18px; margin: 0 0 4px; }
    .header { border-bottom: 2px solid #2c5282; padding-bottom: 10px; margin-bottom: 20px; }
    .header .company { font-size: 20px; font-weight: 700; color: #2c5282; }
    .header .subtitle { font-size: 13px; color: #555; margin-top: 4px; }
    .section-title { background: #2c5282; color: #fff; font-weight: 700; padding: 5px 8px; margin: 18px 0 8px; font-size: 12px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table td, table th { padding: 5px 8px; vertical-align: top; }
    table th { background: #edf2f7; font-weight: 700; width: 40%; text-align: left; }
    table td { border-bottom: 1px solid #e2e8f0; }
    .answer { white-space: pre-wrap; font-size: 11px; color: #444; }
    .footer { margin-top: 30px; border-top: 1px solid #ccc; padding-top: 8px; font-size: 10px; color: #888; }
    .two-col { display: table; width: 100%; }
    .two-col .col { display: table-cell; width: 50%; padding-right: 10px; vertical-align: top; }
</style>
</head>
<body>
<div class="header">
    <div class="company">Angels of Care</div>
    <div class="subtitle">{{ $title }} &mdash; {{ $candidate->fullName }} - {{ now()->format('m/d/Y') }}</div>
</div>

@yield('content')

<div class="footer">Generated on {{ now()->format('m/d/Y H:i') }}</div>
</body>
</html>
