<link href="{{ mix('css/bootstrap-editable.css') }}" rel="stylesheet">
<script src="{{ mix('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ mix('js/editable.js') }}"></script>
<style>
    .calldropdown.active{ display: inline-block; }
    .dropdown-menu li:hover {
        background-color: #a7aeb5;
    }
    .fa-phone {
        color: #2f5baa;
    }
</style>
<x-modal>
    <x-slot name="id">clientDetails</x-slot>
    <x-slot name="size">w50</x-slot>
    <x-slot name="title">Access denied</x-slot>
    <x-slot name="body">
        <div class="row d-flex">
            <div class="col-md-12 tracker-details client-cnt"  id="detailDivPart">
                You don't have access to that area, contact your admin!
            </div>
        </div>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>


