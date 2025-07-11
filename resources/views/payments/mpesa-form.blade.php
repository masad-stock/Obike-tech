@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">M-PESA Payment</div>

                <div class="card-body">
                    <h5>Rental Agreement #{{ $agreement->id }}</h5>
                    <p><strong>Customer:</strong> {{ $agreement->customer->name }}</p>
                    <p><strong>Total Amount:</strong> ${{ number_format($agreement->total_amount, 2) }}</p>
                    <p><strong>Remaining Balance:</strong> ${{ number_format($remainingBalance, 2) }}</p>

                    <form method="POST" action="{{ route('mpesa.initiate', $agreement) }}">
                        @csrf
                        
                        <div class="form-group mb-3">
                            <label for="amount">Payment Amount</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">$</span>
                                </div>
                                <input type="number" step="0.01" min="0.01" max="{{ $remainingBalance }}" 
                                    class="form-control @error('amount') is-invalid @enderror" 
                                    id="amount" name="amount" value="{{ old('amount', $remainingBalance) }}" required>
                                @error('amount')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="phone_number">M-PESA Phone Number</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">+</span>
                                </div>
                                <input type="text" 
                                    class="form-control @error('phone_number') is-invalid @enderror" 
                                    id="phone_number" name="phone_number" 
                                    placeholder="254XXXXXXXXX" 
                                    value="{{ old('phone_number') }}" required>
                                @error('phone_number')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            <small class="form-text text-muted">Enter phone number in format: 254XXXXXXXXX (e.g., 254712345678)</small>
                        </div>

                        @error('mpesa_error')
                            <div class="alert alert-danger">
                                {{ $message }}
                            </div>
                        @enderror

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Pay with M-PESA</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection