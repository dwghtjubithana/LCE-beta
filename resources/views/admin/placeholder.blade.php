@extends('admin.layout')

@section('title', $title ?? 'Admin')
@php($active = $active ?? '')

@section('content')
<div class="page-header">
    <div>
        <h2>{{ $title ?? 'Admin' }}</h2>
        <p>{{ $subtitle ?? 'This section is being configured.' }}</p>
    </div>
</div>

<div class="card">
    <div class="status">{{ $message ?? 'This page is ready for the next phase.' }}</div>
</div>
@endsection
