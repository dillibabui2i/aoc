<?php
use App\Helpers\H;
use App\Renewables;
?>
<!doctype html>
<html lang="en-US">

<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <title>Automated Email Reminder</title>
    <meta name="description" content="Automated Email Reminder.">
    <style type="text/css">
        a:hover {text-decoration: underline !important;}
        p {
            color: #1e1e2d;
            font-weight: 500;
            margin: 0;
            font-size: 16px;
            font-family: 'Rubik', sans-serif;
        }
        .btn-info {
            background-color: #2f5baa;
            border: 1px solid #6cb2eb;
            border-radius: .25rem;
            color: #fff !important;
            display: inline-block;
            font-size: .9rem;
            font-weight: 400;
            line-height: 1.6;
            padding: .375rem .75rem;
            text-align: center;
            transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            vertical-align: middle;
            text-decoration: none;
        }
        .btn-info:hover {
            text-decoration: none !important;
            background-color: #4aa0e6;
            border-color: #3f9ae5;
            color: #fff !important;
            cursor: pointer;
        }
    </style>
</head>

<body marginheight="0" topmargin="0" marginwidth="0" style="margin: 0px; background-color: #f2f3f8;" leftmargin="0">
    <!--100% body table-->
    <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#f2f3f8"
        style="@import url(https://fonts.googleapis.com/css?family=Rubik:300,400,500,700|Open+Sans:300,400,600,700); font-family: 'Open Sans', sans-serif;"
        summary="Automated Email Reminder"  aria-hidden="true">
        <tr>
            <td>
                <table style="background-color: #f2f3f8; max-width:750px;  margin:0 auto;" width="100%" border="0"
                    align="center" cellpadding="0" cellspacing="0" summary="Reminder Email logo" aria-hidden="true">
                    
                    <tr>
                        <td style="text-align:center;">
                          <a href="{{ env('APP_URL') }}" title="logo" target="_blank">
                            <img width="240" src="{{ asset('images/logo.png') }}" title="{{ env('APP_NAME') }}" alt="{{ env('APP_NAME') }}">
                          </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="height:20px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td>
                            <table width="95%" border="0" align="left" cellpadding="0" cellspacing="0"
                                style="max-width:670px;background:#fff; border-radius:3px; text-align:left;-webkit-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);-moz-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);box-shadow:0 6px 18px 0 rgba(0,0,0,.06);"
                                summary="Reminder email content"  aria-hidden="true">
                                <tr>
                                    <td style="padding:0 25px;height:40px;width:170px;font-size:16px;">Dear {{$renewable->name}},</td>
                                </tr>
                                <tr>
                                    <td style="padding:0 25px;">
                                        <p>
                                            We wanted to give you a friendly reminder that your {{$renewable->renewablename}} is set to expire {{$days}} days from today, on {{ H::getFormatedDate($renewable->expiration_date) }}. Please consider making the renewal of this credential a priority to stay compliant and avoid any potential disruptions in providing outstanding services to our clients. Your proactive approach in maintaining a complete employee file is greatly appreciated. Non-compliance could result in being removed from further scheduling until your expiring credential is received.
                                        </p><br>
                                        <p>
                                            If you have any questions or need assistance with the renewal process, please don't hesitate to reach out to your local branch. They are here to assist you every step of the way. Additionally, remember to keep them updated on any progress or potential delays you might encounter. We want to make this process as smooth as possible for you because your dedication to the clients and families we serve means the world to us.
                                        </p><br>
                                        <p>
                                           Thank you for your assistance!
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="height:20px;padding:10px 25px;">
                                        <p>Note: To upload a copy of your new {{$renewable->renewablename}}, click the link below and follow the instructions listed on the page. Once uploaded, your document will be received by our office and no further action may be needed. If we have any questions, we will contact you.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="text-align:center;padding:10px 0;">
                                        <a href="{{route('renewabledocument.upload', ['id' => base64_encode($renewable->id)])}}" class="btn-info">Upload your document</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style="height:60px;">&nbsp;</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <!--/100% body table-->
</body>

</html>