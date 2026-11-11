@extends('layouts.nthLandingLayout')
@section('title', 'NTH - Error')
@section('content')
<h1>Error Occurred</h1>
@if (session('error'))
    <p>{{ session('error') }}</p>
@else
    <p>Something went wrong. Please try again later.</p>
@endif
@endsection
