@extends('layouts.app')

@section('title', 'Application Details')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-file-alt me-2"></i> Application Details
                    </h5>
                    <span class="badge bg-light text-primary fs-6">{{ $loan->loan_track_id }}</span>
                </div>
                
                <div class="card-body p-4">
                    {{-- Basic Information --}}
                    <div class="row mb-4">
                        <div class="col-sm-6 mb-3">
                            <h6 class="text-muted text-uppercase small fw-bold">Loan Type</h6>
                            <p class="fw-bold text-dark fs-5">{{ ucfirst($loan->loan_type) }}</p>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <h6 class="text-muted text-uppercase small fw-bold">Current Status</h6>
                            <p>
                                @php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'active' => 'primary',
                                        'rejected' => 'danger'
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$loan->status] ?? 'secondary' }} fs-6">
                                    {{ ucfirst($loan->status) }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6 mb-3">
                            <h6 class="text-muted text-uppercase small fw-bold">Requested Amount</h6>
                            <p class="fw-bold h4 text-primary">TZS {{ number_format($loan->requested_amount, 2) }}</p>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <h6 class="text-muted text-uppercase small fw-bold">Submission Date</h6>
                            <p class="fw-bold text-dark">{{ $loan->created_at->format('d M Y, H:i') }}</p>
                        </div>
                    </div>

                    <hr class="my-4">

                    {{-- Documents Section --}}
                    <div class="mt-4">
                        <h6 class="text-muted text-uppercase small fw-bold mb-3">Supporting Documents</h6>
                        @if($loan->business_registration_attachment || $loan->business_proposal_document)
                            <div class="list-group">
                                @if($loan->business_registration_attachment)
                                    <a href="{{ asset('storage/'.$loan->business_registration_attachment) }}" target="_blank" class="list-group-item list-group-item-action">
                                        <i class="fas fa-file-pdf text-danger me-2"></i> Business Registration Document
                                    </a>
                                @endif
                                @if($loan->business_proposal_document)
                                    <a href="{{ asset('storage/'.$loan->business_proposal_document) }}" target="_blank" class="list-group-item list-group-item-action">
                                        <i class="fas fa-file-word text-primary me-2"></i> Business Proposal Document
                                    </a>
                                @endif
                            </div>
                        @else
                            <div class="alert alert-light border border-dashed text-center">
                                <small class="text-muted">No documents uploaded for this application.</small>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card-footer bg-light p-3 d-flex justify-content-between">
                    <a href="{{ route('loan-applications.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to List
                    </a>
                    
                    {{-- Edit/Action Placeholder --}}
                    @if($loan->status == 'pending')
                        <button class="btn btn-outline-warning">Edit Application</button>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
@endsection