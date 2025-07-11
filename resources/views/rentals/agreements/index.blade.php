@extends('layouts.app')

@section('title', 'Rental Agreements')

@section('header', 'Rental Agreements')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Rental Agreements</h1>
            <p class="text-muted">Manage customer rental contracts</p>
        </div>
        <div>
            <a href="{{ route('rentals.agreements.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>New Agreement
            </a>
        </div>
    </div>

    <div id="app">
        <rental-agreement-list 
            :agreements="{{ json_encode($agreements) }}"
            :routes="{{ json_encode([
                'show' => route('rentals.agreements.show', ['agreement' => ':id']),
                'edit' => route('rentals.agreements.edit', ['agreement' => ':id']),
                'pdf' => route('rentals.agreements.pdf', ['agreement' => ':id'])
            ]) }}"
        ></rental-agreement-list>
    </div>
</div>
@endsection