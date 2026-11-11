<x-modal>
    <x-slot name="id">supplyRequestDelPopUp</x-slot>
    <x-slot name="size">w25</x-slot>
    <x-slot name="title">Confirmation</x-slot>
    <x-slot name="extraHeader"></x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row">
            <form class="row" action="" id="deleteSupplyRequest">
                <input type="hidden" name="supplyRequest" id="supplyRequestId" value="">
                <input type="hidden" name="supplyRequesttype" id="supplyRequesttype" value="">
            </form>
            <h5 id="confirmationDialogMessage">Do you really want to remove this Supply Request?</h5>
        </div>
    </x-slot>
    <x-slot name="footer">
        <button class="btn btn-primary" id="seeccrYesButton" onclick="deleteSupplyRequest()">Yes</button>
        <button class="btn btn-primary" id="seeccrNoButton" onclick="closesupplyRequest()">No</button>
    </x-slot>
</x-modal>
