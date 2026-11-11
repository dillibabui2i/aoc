<?php
    use App\Helpers\H;
?>
<section class="renewables-section">
    @canPermission(['additional.permission'])
        <a href="#" id="refreshRenewableByProviderId" class="float-right" data-id="{{$clinicianDetails->ProviderID}}">Sync From KT</a>
        <i class="fa fa-info-circle float-right" title="Sync data manually from the KanTime"></i>
        <span id="refreshRenewableByProviderIdMessage" class="alert-success" style="display: none"></span>
    @endcanPermission
    <table class="table mt-2" aria-describedby="">
        <tr>
            <th>Item Name</th>
            <th>Expiration Date</th>
            <th>License Number</th>
            <th>Issued Date</th>
            <th>License State</th>
            <th class="w20">Description</th>
        </tr>
        @foreach($renewables as $item)
        <tr>
            <td>{{$item->name}}</td>
            <td>{{$item->expiration_date != '0000-00-00' && $item->expiration_date != '' ? H::getFormatedDate($item->expiration_date) : ''}}</td>
            <td>{{$item->license_no}}</td>
            <td>{{$item->issued_date != '0000-00-00' && $item->issued_date != '' ? H::getFormatedDate($item->issued_date) : ''}}</td>
            <td>{{$item->license_state}}</td>
            <td>{{$item->description}}</td>
        </tr>
        @endforeach
    </table>
</section>
