@php
    use App\Tag;

    $tags = Tag::where('status', 'enabled')->pluck('name', 'id');
@endphp

<x-modal>
    <x-slot name="id">billingInvoiceTagModal</x-slot>
    <x-slot name="size">w-40</x-slot>
    <x-slot name="title">Tag Invoices</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <form id="billingInvoiceTagForm" class="form">
            @csrf
            <input type="hidden" name="selected_ids" id="billing_selected_ids" value="{{ $selectedIds ?? '' }}"/>

            <div class="form-group row d-flex">
                <label class="col-sm-2">Tags</label>
                <div class="col-sm-8">
                    <select name="tags[]" id="invoice_tags_select" class="form-control" multiple>
                        @foreach($tags as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group row d-flex">
                <div class="col-md-12 text-center mt-2">
                    <button class="btn btn-primary" onclick="saveInvoiceTags(event, this);">Save</button>
                    <button class="btn btn-secondary" data-dismiss="modal" aria-label="Close">Cancel</button>
                </div>
            </div>
        </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>

<script>
    function saveInvoiceTags(e, button) {
        e.preventDefault();
        let _token = $('meta[name="csrf-token"]').attr('content');
        let data = $('#billingInvoiceTagForm').serialize();

        button.disabled = true;
        $.post("{{ route('billing.tagInvoices') }}", data)
            .done(function () {
                $('#billingInvoiceTagModal').modal('hide');
                $('.dataTable').DataTable().ajax.reload(null, false);
            })
            .fail(function (xhr) {
                alert('Error saving tags: ' + xhr.responseText);
            })
            .always(function () {
                button.disabled = false;
            });
    }
</script>
