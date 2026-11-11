@extends('layouts.myaccount')
@section('title', 'Manage Project Tags')

@section('content')
    <style>
        .is-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220,53,69,.25);
        }
    </style>
    @php
        $filters = [
            [
                'label' => 'Status',
                'name' => 'status',
                'type' => 'select',
                'source' => [
                    ['value' => 'enabled', 'text' => 'Enabled'],
                    ['value' => 'disabled', 'text' => 'Disabled']
                ]
            ]
        ];

        $columns = [
            ['label' => 'Tag Name', 'name' => 'name'],
            ['label' => 'Description', 'name' => 'description'],
            ['label' => 'Created By', 'name' => 'created_by'],
            ['label' => 'Date Created', 'name' => 'created_at'],
            ['label' => 'Status', 'name' => 'status']
        ];
    @endphp

    <div class="container-fluid">
        <div class="row">
            <div class="form-group col-md-6 head-title">
                <h1 class="h3 mb-2 text-gray-800">Manage Project Tags</h1>
                <a href="javascript:void(0);" onclick="openTagModal()"  title="Add new tag">
                    <i class="fa fa-plus-circle"></i>
                </a>
            </div>
        </div>

        <div class="card shadow mb-4">
            @if ($message = Session::get('success'))
                <div class="alert alert-success alert-block"><strong>{{ $message }}</strong></div>
            @endif

            @component('components.datatable-filters', ['filters' => $filters]) @endcomponent

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%">
                        <thead>
                        <tr>
                            @foreach($columns as $col)
                                <th>{{ $col['label'] }}</th>
                            @endforeach
                            <th>Actions</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="tagModalContainer"></div>

    <script>
        const columnDefs = @json($columns);
        let columns = columnDefs.map(col => ({
            data: col.name,
            name: col.name
        }));

        columns[4] = {
            data: 'status',
            name: 'status',
            render: function (data, type, row) {
                const label = data === 'enabled' ? 'Enabled' : 'Disabled';
                const nextStatus = data === 'enabled' ? 'disabled' : 'enabled';
                return `
                <button class="btn btn-sm toggle-status"
                        data-id="${row.id}"
                        data-status="${nextStatus}"
                        style="background-color: #2f5baa; color: white; border-radius: 15px; padding: 4px 10px;">
                    ${label}
                </button>`;
            }
        };

        columns.push({
            data: null,
            orderable: false,
            searchable: false,
            render: function (data, type, row) {
                return `
                <div class="btn-group" role="group">
                    <a type="button" onclick="openTagModal(${row.id})">
                        <i style="color:#2f5baa" class="fa fa-edit ml-2"></i>
                    </a>
                </div>`;
            }
        });

        $(document).ready(function () {
            $('#dataTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("managetags") }}',
                    data: function (d) {
                        d.filters = $('#datatableFilterSearchForm').serializeArray();
                    }
                },
                columns: columns,
                initComplete: function () {
                    const searchInput = $('#dataTable_filter input');
                    let delay;
                    searchInput.off().on('keyup', function (e) {
                        clearTimeout(delay);
                        delay = setTimeout(() => {
                            const value = $(this).val();
                            if (value.length >= 3 || (value.length === 0 && e.keyCode === 8)) {
                                $('#dataTable').DataTable().search(value).draw();
                            }
                        }, 500);
                    });
                }
            });
        });

        function openTagModal(id = '') {
            let url = '{{ route("managetags.form") }}';
            if (id) url += '?id=' + id;

            $.get(url, function (html) {
                $('#tagModalContainer').html(html);
                $('#tagFormModal').modal('show');
            });
        }

        $(document).on('submit', '#tagForm', function (e) {
            e.preventDefault();

            $('#tagForm .form-control').removeClass('is-invalid');

            let hasError = false;

            const nameField = $('[name="name"]');
            if (nameField.val().trim() === '') {
                nameField.addClass('is-invalid');
                hasError = true;
            }

            // Validate description field
            const descriptionField = $('[name="description"]');
            if (descriptionField.val().trim() === '') {
                descriptionField.addClass('is-invalid');
                hasError = true;
            }

            // If any validation fails, stop submission
            if (hasError) {
                return;
            }

            const form = $(this);
            const method = form.find('input[name="_method"]').val() || 'POST';
            const url = method === 'POST'
                ? '{{ route("managetags.store") }}'
                : `/managetags/update/${form.find('input[name="id"]').val()}`;

            $.ajax({
                url: url,
                method: method,
                data: form.serialize(),
                success: function () {
                    $('#tagFormModal').modal('hide');
                    $('#dataTable').DataTable().ajax.reload(null, false);
                },
                error: function () {
                    alert('Error saving tag.');
                }
            });
        });

        $(document).on('click', '.toggle-status', function () {
            const id = $(this).data('id');
            const status = $(this).data('status');

            $.ajax({
                url: `/managetags/status/${id}`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    status: status
                },
                success: function () {
                    $('#dataTable').DataTable().ajax.reload(null, false);
                },
                error: function () {
                    alert('Failed to toggle status.');
                }
            });
        });
    </script>
@endsection
