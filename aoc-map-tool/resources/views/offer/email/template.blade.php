<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Job Offer Letter - {{ $name }}</title>
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
<div style="text-align: center;">
    <img src="https://angelsofcareapp.com/images/logo.png">
</div>
<p>
    Hello {{ $name }},
</p>
<p>
    We are so excited you are considering employment with Angels of Care Pediatric Home Health. We would love to have you join our team of compassionate clinicians to serve children and families within our community.
</p>
<p>
    We know that making a career move is an important decision, and you need to be equipped with the right information to make the best choice for you and your family. Below are some of the many benefits that we currently offer as part of our compensation package:
</p>
<ul>
    <li>Flexible Scheduling</li>
    <li>Competitive Pay</li>
    <li>Paid Time Off (20+ visits/week)</li>
    <li>Paid Sick Time (all employees)</li>
    <li>Vehicle Allowance (20+ visits/week)</li>
    <li>Employee Referral Bonus Program</li>
    <li>Tuition discounts for you and your immediate family members through Drexel University</li>
    <li>Employee discounts to amusement parks, restaurants, movie theaters, spas, sporting events, hotels, and so much more!</li>
</ul>
<p>
    We also offer a full suite of health and wellness benefits for full-time employees (those working 20+ visits weekly) including:
</p>
<ul>
    <li>Medical (please see below for more on our ICHRA plan)</li>
    <li>Dental – 2 options</li>
    <li>Vision – 2 options</li>
    <li>Flexible Spending Account/Health Savings Account</li>
    <li>Company provided life insurance for $15,000</li>
    <li>Voluntary life insurance</li>
    <li>Voluntary spousal life insurance</li>
    <li>Voluntary child(ren) life insurance</li>
    <li>Short-Term Disability</li>
    <li>Long-Term Disability</li>
    <li>Accident/Critical Illness/Hospital Indemnity Insurances</li>
    <li>Company provided Employee Assistance Program</li>
</ul>
<p>Our benefits are effective in the first of the month following 30 days of employment.</p>
<br>
<p>
    Angels of Care offers a new and innovative way to obtain health insurance through Individual Coverage Health Reimbursement Arrangement (ICHRA pronounced ick-ruh). Angels of Care partners with Take Command Health, who is an industry leader in supporting employees and administering the ICHRA. To find out more about ICHRA please visit Take Command Health at www.takecommandhealth.com
</p>
<p>
    You can also see what your cost would be for the ICHRA plan by performing the following steps:
</p>
<ul>
    <li>
        Click on this link and search by your age and home zip code: https://app.takecommandhealth.com/health-plan-search   This will show you what plans are available to you.
        <ul>
            <li>
                Angels of Care reimburses the following amounts towards your monthly ICHRA medical premium:
                <ul>
                    <li>Ages 21-30: $205-$233</li>
                    <li>Ages 31-40: $238-$262</li>
                    <li>Ages 41-50: $267-$366</li>
                    <li>Ages 51-64+: $382-$557</li>
                    <li>Ages 61-65: $577-$615</li>
                </ul>
            </li>
        </ul>
    </li>
    <li>
        Take the total cost of the plan minus what Angels of Care would reimburse and that will be your portion of the monthly ICHRA medical premium. All field (clinical) employees are paid weekly (52 pay periods a year) and office staff are paid bi-weekly (26 pay periods a year).  As such, all deductions for your benefit elections will follow this same schedule.
    </li>
</ul>
<p>
    If you need assistance in understanding plan options available to you a member of our team will be available to assist you. At Angels of Care, we value our employees and consider them our most valuable asset. We truly hope you make the right decision and join our team of excellent medical care providers.  You will not regret it!
</p>
<br>
<p>
    Please return your signed offer letter within 3 business days.  If you have any questions in the meantime, you can reach out to me{{ $clinicalManagerName && $clinicalManagerEmail ? ", or {$clinicalManagerName} at {$clinicalManagerEmail}" : '' }}.
</p>
<hr>





</body>
