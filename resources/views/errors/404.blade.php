@extends('errors::minimal')

@section('title', __('Page Not Found'))
@section('code', '404')
@section('message', __('Oops! The page you are looking for does not exist.'))

@section('content')
    <div class="container-fluid">
        <div class="main-content d-flex flex-column p-0">
            <div class="m-auto text-center">
                <img src="{{ asset('backend/admin/assets/images/error.png') }}" class="mw-430 mb-4 w-100" alt="error">
                <h3 class="fs-24 mb-3">{{ __('Oops! Page Not Found') }}</h3>
                <p class="mb-4">{{ __('The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.') }}</p>
                <a href="{{ route('home') }}" class="btn btn-primary py-2 px-4 fs-16 fw-medium">
                    <span class="d-inline-block py-1">{{ __('Back To Home') }}</span>
                </a>
            </div>
        </div>
    </div>
@endsection
