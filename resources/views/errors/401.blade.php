@extends('errors::minimal')

@section('title', __('Unauthorized'))
@section('code', '401')
@section('message', __('You are not authorized to access this page.'))

@section('content')
    <div class="container-fluid">
        <div class="main-content d-flex flex-column p-0">
            <div class="m-auto text-center">
                <img src="{{ asset('backend/admin/assets/images/error.png') }}" class="mw-430 mb-4 w-100" alt="error">
                <h3 class="fs-24 mb-3">{{ __('Unauthorized Access') }}</h3>
                <p class="mb-4">{{ __('Sorry, you do not have permission to view this page.') }}</p>
                <a href="{{ route('home') }}" class="btn btn-primary py-2 px-4 fs-16 fw-medium">
                    <span class="d-inline-block py-1">{{ __('Back To Home') }}</span>
                </a>
            </div>
        </div>
    </div>
@endsection
