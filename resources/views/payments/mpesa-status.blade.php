@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">M-PESA Payment Status</div>

                <div class="card-body">
                    <h5>Transaction Details</h5>
                    <p><strong>Reference:</strong> {{ $transaction->reference }}</p>
                    <p><strong>Amount:</strong> ${{ number_format($transaction->amount, 2) }}</p>
                    <p><strong>Phone Number:</strong> +{{ $transaction->phone_number }}</p>
                    <p><strong>Status:</strong> 
                        @if($transaction->status == 'completed')
                            <span class="badge bg-success">Completed</span>
                        @elseif($transaction->status == 'failed')
                            <span class="badge bg-danger">Failed</span>
                        @else
                            <span class="badge bg-warning text-dark">Pending</span>
                        @endif
                    </p>
                    
                    @if($transaction->result_description)
                        <p><strong>Result:</strong> {{ $transaction->result_description }}</p>
                    @endif
                    
                    <p><strong>Date:</strong> {{ $transaction->created_at->format('M d, Y H:i:s') }}</p>

                    @if($transaction->status == 'pending')
                        <div class="alert alert-info">
                            <p>Your M-PESA payment is being processed. Please check your phone to complete the transaction.</p>
                            <p>This page will automatically refresh to update the status.</p>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="{{ route('mpesa.status', $transaction) }}" class="btn btn-primary">
                                Check Status
                            </a>
                        </div>
                    @elseif($transaction->status == 'completed')
                        <div class="alert alert-success">
                            <p>Your payment has been successfully processed. Thank you!</p>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="{{ route('rentals.agreements.show', $transaction->rental_agreement_id) }}" class="btn btn-primary">
                                View Rental Agreement
                            </a>
                        </div>
                    @else
                        <div class="alert alert-danger">
                            <p>Your payment could not be processed. Please try again or use a different payment method.</p>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="{{ route('mpesa.form', $transaction->rental_agreement_id) }}" class="btn btn-primary">
                                Try Again
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if($transaction->status == 'pending')
<script>
    // Auto-refresh the page every 10 seconds to check for updates
    setTimeout(function() {
        window.location.reload();
    }, 10000);
</script>
@endif
@endsection