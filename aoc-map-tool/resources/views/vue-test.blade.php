@extends('layouts.myaccount')

@push('page-scripts')
    <script src="{{ mix('js/vue-app.js') }}"></script>
@endpush

@section('title', 'Users')

@section('content')

    <div class="container mx-auto py-10">
        <div class="v-app">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div id="vue-users"></div>
                </div>
            </div>
        </div>
    </div>

@endsection
