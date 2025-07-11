@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Process Payment</div>

                <div class="card-body">
                    <h5>Rental Agreement #{{ $agreement->id }}</h5>
                    <p><strong>Customer:</strong> {{ $agreement->customer->name }}</p>
                    <p><strong>Total Amount:</strong> ${{ number_format($agreement->total_amount, 2) }}</p>
                    <p><strong>Remaining Balance:</strong> ${{ number_format($remainingBalance, 2) }}</p>

                    <form id="payment-form" method="POST" action="{{ route('payments.process', $agreement) }}">
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
                            <label for="card-element">Credit or debit card</label>
                            <div id="card-element" class="form-control">
                                <!-- Stripe Element will be inserted here -->
                            </div>
                            <div id="card-errors" role="alert" class="text-danger mt-2"></div>
                        </div>

                        <input type="hidden" name="payment_method" id="payment-method">

                        <div class="d-grid gap-2">
                            <button id="submit-button" type="submit" class="btn btn-primary">Process Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
    const stripe = Stripe('{{ config('cashier.key') }}');
    const elements = stripe.elements();
    const cardElement = elements.create('card');
    
    cardElement.mount('#card-element');
    
    const form = document.getElementById('payment-form');
    const submitButton = document.getElementById('submit-button');
    const paymentMethodInput = document.getElementById('payment-method');
    
    cardElement.addEventListener('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });
    
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        submitButton.disabled = true;
        
        stripe.confirmCardSetup(
            '{{ $intent->client_secret }}',
            {
                payment_method: {
                    card: cardElement,
                    billing_details: {
                        name: '{{ Auth::user()->name }}',
                    }
                }
            }
        ).then(function(result) {
            if (result.error) {
                const errorElement = document.getElementById('card-errors');
                errorElement.textContent = result.error.message;
                submitButton.disabled = false;
            } else {
                paymentMethodInput.value = result.setupIntent.payment_method;
                form.submit();
            }
        });
    });
</script>
@endsection