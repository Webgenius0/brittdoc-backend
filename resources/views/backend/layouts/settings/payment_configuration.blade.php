@extends('backend.layouts.settings.layout')
@section('form_title', 'Payment Configuration')
@section('form_description', 'Payment Configuration')

@section('form_content')
<form action="{{ route('system_settings.configuration.payment') }}" method="POST">
    @csrf
    <div class="row">
        <!-- STRIPE_PUBLIC_KEY -->
        <div class="col-lg-12">
            <div class="form-group mb-4">
                <label class="label text-secondary">STRIPE PUBLIC KEY</label>
                <div class="form-group position-relative">
                    <input type="text" 
                           class="form-control text-dark ps-5 h-55 @error('stripe_key') is-invalid @enderror" 
                           name="stripe_key"
                           value="{{ old('stripe_key', env('STRIPE_PUBLIC_KEY')) }}"
                           placeholder="Enter STRIPE PUBLIC KEY">
                </div>
                @error('stripe_key')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    
        <!-- STRIPE SECRET KEY -->
        <div class="col-lg-12">
            <div class="form-group mb-4">
                <label class="label text-secondary">STRIPE SECRET KEY</label>
                <div class="form-group position-relative">
                    <input type="text" 
                           class="form-control text-dark ps-5 h-55 @error('stripe_secret') is-invalid @enderror" 
                           name="stripe_secret"
                           value="{{ old('stripe_secret', env('STRIPE_SECRET_KEY')) }}"
                           placeholder="Enter STRIPE SECRET KEY">
                </div>
                @error('stripe_secret')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    
        <!-- STRIPE_WEBHOOK_SECRET -->
        <div class="col-lg-12">
            <div class="form-group mb-4">
                <label class="label text-secondary">STRIPE WEBHOOK SECRET</label>
                <div class="form-group position-relative">
                    <input type="text" 
                           class="form-control text-dark ps-5 h-55 @error('stripe_webhook_secret') is-invalid @enderror" 
                           name="stripe_webhook_secret"
                           value="{{ old('stripe_webhook_secret', env('STRIPE_WEBHOOK_SECRET')) }}"
                           placeholder="Enter STRIPE WEBHOOK SECRET">
                </div>
                @error('stripe_webhook_secret')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="d-flex flex-wrap gap-3">
                {{-- <button type="submit" class="btn btn-danger py-2 px-4 fw-medium fs-16 text-white">Cancel</button> --}}
                <button type="submit" class="btn btn-primary py-2 px-4 fw-medium fs-16"> <i class="ri-check-line text-white fw-medium"></i> Update</button>
            </div>
        </div>
    </div>
</form><!--end form-->

@endsection

