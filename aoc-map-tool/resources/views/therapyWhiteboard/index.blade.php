@extends('layouts.myaccount')
@section('title', 'Therapy WhiteBoard')
@section('content')
    <link href="{{ mix('css/bootstrap-editable.css') }}" rel="stylesheet">
    <link href="{{ mix('css/bootstrap-editable.css') }}" rel="stylesheet">
    <script src="{{ mix('js/bootstrap-editable.min.js') }}"></script>
    <script src="{{ mix('js/editable.js') }}"></script>
    <style>
        .hidden {
            display: none !important;
        }
        td.showhand {
            cursor: pointer;
        }
        .table-responsive{
            overflow-x: inherit;
        }
        .card-body td {
            padding: 5px 12px;!important;
            width: 10%;!important;
        }
        .sticky tbody{
            width: 100%;
            position: relative;
        }
        .sticky .table thead tr:first-child,.sticky ul.ui-tabs-nav {
            position: sticky;
            z-index: 30;
        }
        .sticky .table thead tr:first-child{
            top: 35px;
            background: #fff;
            width: 100%;
        }
        .sticky ul.ui-tabs-nav {
            top:0;
            width: 102%;
        }

        .whiteboard-header {
            text-align: left;
            margin-bottom: 15px;
        }

        .whiteboard-header h2 {
            font-weight: 600;
        }
        .disabled-item {
            pointer-events: none;
            cursor: default;
            opacity: 0.5;
            color: #888;
        }
        .ui-widget-header {
            background: #ffffff;!important;
            border: none;!important;
        }
    </style>
    <div class="container-fluid">
        <div id="tabs" class="card-body">
            <div class="whiteboard-header"><h3>Therapy Whiteboard</h3></div>

            @include('therapyWhiteboard.partials.tabs', ['days' => $days])

            @foreach($days as $dayName => $dayDate)
                @include('therapyWhiteboard.partials.table', [
                    'day' => $dayName,
                    'date' => $dayDate,
                    'states' => $data[$dayName]['states'],
                    'nationalTotals' => $data[$dayName]['nationalTotals'],
                    'disabled' => $data[$dayName]['disabled'] ?? false,
                ])
            @endforeach
        </div>
    </div>

    <script>
        function collapseExpandAllTab(element) {
            const $buttonText = $(element).find('span span');
            const $panel = $(element).closest('.whiteboard-table');

            if (!$panel.length) {
                return;
            }

            if ($buttonText.text().trim() === 'Collapse') {
                $buttonText.text('Expand');
                $panel.find('tr.recruiter-row').addClass('hidden');
                $panel.find('tr.subrow').addClass('hidden');
            } else {
                $buttonText.text('Collapse');
                $panel.find('tr.recruiter-row').removeClass('hidden');
                $panel.find('tr.subrow').removeClass('hidden');
            }
        }

        $(document).ready(function(){

            const today = "{{ \Carbon\Carbon::now()->format('l') }}";
            const dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

            function getAvailableTabIndex() {
                let todayIndex = dayOrder.indexOf(today);

                if (todayIndex === -1) {
                    return 0;
                }

                for (let i = todayIndex; i >= 0; i--) {
                    const href = '#tab-' + dayOrder[i];
                    const $tabLink = $('#tabs ul li a[href="' + href + '"]');
                    const $tabItem = $tabLink.closest('li');

                    if ($tabItem.length && !$tabItem.hasClass('ui-state-disabled')) {
                        return i;
                    }
                }

                return 0;
            }

            // INIT TABS
            $("#tabs").tabs({
                active: getAvailableTabIndex(),
                activate: function(event, ui) {
                    // hide all tab panels
                    $('.whiteboard-table').hide();

                    // show active
                    let activeId = ui.newPanel.attr('id');
                    $('#' + activeId).show();
                },
                create: function(event, ui) {
                    // hide all panels initially
                    $('.whiteboard-table').hide();

                    // show "today" (or Monday fallback)
                    const today = "{{ \Carbon\Carbon::now()->format('l') }}";
                    const tabId = "#tab-" + today;

                    if ($(tabId).length > 0) {
                        $(tabId).show();
                    } else {
                        $("#tab-Monday").show();
                    }

                    $('#tabs').show();
                }
            });

            // COLLAPSE/EXPAND LOGIC

            $(document).on('click', '.state-row', function () {
                const state = $(this).data('state');
                const recruiters = $(`tr.recruiter-row[data-parent-state="${state}"]`);
                const regions = $(`tr.subrow[data-parent-state="${state}"]`);

                const isAnyVisible = recruiters.filter(':visible').length > 0;

                if (isAnyVisible) {
                    recruiters.addClass('hidden');
                    regions.addClass('hidden');
                } else {
                    recruiters.removeClass('hidden');
                }
            });

            $(document).on('click', '.recruiter-row', function () {
                const recruiter = $(this).data('recruiter');
                const state = $(this).data('parent-state');

                $(`tr[data-parent-recruiter="${recruiter}"][data-parent-state="${state}"]`).toggleClass('hidden');
            });

        });

        $(window).scroll(function() {
            var scroll = $(window).scrollTop();
            if (scroll >= 80) {
                $("body").addClass("sticky");
            }else{
                $("body").removeClass("sticky");
            }
        });
    </script>

@endsection
