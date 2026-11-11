<?php
use Illuminate\Support\Facades\Storage;
use App\Helpers\H;
use Illuminate\Support\Carbon;
?>
<style>
    .halos-section .table {
        border-collapse: collapse;
        border: 2px solid #343a40;
    }

    .halos-section th,
    .halos-section td {
        border: 1px solid #343a40 !important;
        vertical-align: middle;
    }

    .halos-section thead th {
        background-color: #f8f9fa;
        font-weight: 600;
        border-bottom: 2px solid #343a40 !important;
    }

    .halos-section tbody tr {
        border-bottom: 1px solid #343a40;
    }

    .halos-section img {
        max-height: 160px;
        max-width: 100%;
        object-fit: contain;
        border: 1px solid #dee2e6;
    }
</style>

<section class="halos-section">
    <div class="col-md-12">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                <tr>
                    <th style="width: 20%;">Date and Author</th>
                    <th style="width: 30%; text-align: center;">HALOS Moment Photo</th>
                    <th style="width: 50%;">HALOS Moment Description</th>
                </tr>
                </thead>
                <tbody>
                @foreach($halos as $h)
                    <tr>
                        <td>
                            <strong>{{ $h->created_at ? Carbon::parse($h->created_at)->format('M jS Y') : '—' }}</strong><br>
                            {{ $h->created_by_name ?? '—' }}
                        </td>

                        <td class="text-center">
                            <img
                                    src="{{ $h->path }}"
                                    alt="Halos Moment"
                                    class="img-fluid"
                            >
                        </td>

                        <td>
                            {{ $h->notes }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
