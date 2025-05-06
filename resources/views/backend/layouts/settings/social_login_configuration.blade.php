@extends('backend.layouts.settings.layout')
@section('form_title', 'Social Login Configuration')
@section('form_description', 'Social Login Configuration')
@section('form_content')
<form action="{{ route('system_settings.configuration.social') }}" method="POST">
    @csrf
    <div class="row">
        <!-- Google Client ID -->
        <div class="col-lg-12">
            <div class="form-group mb-4">
                <label class="label text-secondary">Google Client ID</label>
                <div class="form-group position-relative">
                    <input type="text" 
                           class="form-control text-dark ps-5 h-55 @error('google_client_id') is-invalid @enderror" 
                           name="google_client_id"
                           value="{{ old('google_client_id', env('GOOGLE_CLIENT_ID')) }}"
                           placeholder="Enter Google Client ID">
                </div>
                @error('google_client_id')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    
        <!-- Google Client Secret -->
        <div class="col-lg-12">
            <div class="form-group mb-4">
                <label class="label text-secondary">Google Client Secret</label>
                <div class="form-group position-relative">
                    <input type="text" 
                           class="form-control text-dark ps-5 h-55 @error('google_client_secret') is-invalid @enderror" 
                           name="google_client_secret"
                           value="{{ old('google_client_secret', env('GOOGLE_CLIENT_SECRET')) }}"
                           placeholder="Enter Google Client Secret">
                </div>
                @error('google_client_secret')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    
        <!-- Google Redirect URI -->
        <div class="col-lg-12">
            <div class="form-group mb-4">
                <label class="label text-secondary">Google Redirect URI</label>
                <div class="form-group position-relative">
                    <input type="text" 
                           class="form-control text-dark ps-5 h-55 @error('google_redirect_uri') is-invalid @enderror" 
                           name="google_redirect_uri"
                           value="{{ old('google_redirect_uri', env('GOOGLE_REDIRECT_URI')) }}"
                           placeholder="Enter Google Redirect URI">
                </div>
                @error('google_redirect_uri')
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

