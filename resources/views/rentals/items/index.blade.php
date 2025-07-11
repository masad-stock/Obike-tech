@extends('layouts.app')

@section('title', 'Rental Items')

@section('header', 'Rental Items')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Rental Items</h1>
            <p class="text-muted">Manage equipment available for rent</p>
        </div>
        <div>
            <a href="{{ route('rentals.items.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Add New Item
            </a>
        </div>
    </div>

    <div id="app">
        <rental-item-list 
            :items="{{ json_encode($items) }}"
            :routes="{{ json_encode([
                'show' => route('rentals.items.show', ['item' => ':id']),
                'edit' => route('rentals.items.edit', ['item' => ':id']),
                'quickRent' => route('rentals.agreements.quick-create', ['item' => ':id'])
            ]) }}"
        ></rental-item-list>
    </div>
</div>
@endsection