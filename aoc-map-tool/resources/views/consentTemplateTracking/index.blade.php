@extends('layouts.myaccount')
@section('title', config('consentTrackingTemplate.title'))
@section('content')
    @php
        $columns = config('consentTrackingTemplate.columns');
    @endphp

    <style>
        /* todo move it to the .css */
        #datatableFilterSearchForm .multiselect-container.dropdown-menu.show {
            height: auto  !important;
            overflow-y: auto  !important;
        }
        .consent-table-head th {
            background-color: #215eac;
            color: #fff;
        }
    </style>

    <div class="container-fluid">
        <div class="row">
            <div class="form-group col-md-6 head-title">
                <h1 class="h3 mb-2 text-gray-800"> {{ config('consentTrackingTemplate.title') }}</h1>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-body">
                @component('components.datatable-filters', [
                    'filters' => $filters,
                    'isCreateBtnEnable' => true,
                    'btnId' => 'createConsentTemplate',
                    'isShowAllEnabled' => false,
                    'showAllRouteName' => 'consent.tracking.index',
                    'label' => 'Show user consent log',
                    ])
                @endcomponent

                <div class="table-responsive">
                    <table
                        class="table table-bordered"
                        id="consentTrackingDataTable"
                        width="100%"
                        cellspacing="0"
                        data-url="{{ route('consent.tracking.data') }}"
                    >
                        <thead class="consent-table-head">
                        <tr>
                            <th></th>
                            <th>Type</th>
                            <th>Version</th>
                            <th>Status</th>
                            <th>Language</th>
                            <th>Author</th>
                            <th>Created Date</th>
                            <th>Last Updated Date</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @includeIf('consentTemplateTracking.modal')
@endsection
@push('scripts')
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <script src="{{ mix('js/components/consentTracking/consentTemplateEditor.js') }}"></script>
    <script src="{{ mix('js/components/consentTracking/consentTemplateTable.js') }}"></script>
@endpush

