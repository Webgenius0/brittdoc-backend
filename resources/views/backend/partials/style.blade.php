<!-- All CSS files -->
<link rel="stylesheet" href="{{ asset('backend/admin/assets/css/sidebar-menu.css') }}">
<link rel="stylesheet" href="{{ asset('backend/admin/assets/css/simplebar.css') }}">
<link rel="stylesheet" href="{{ asset('backend/admin/assets/css/apexcharts.css') }}">
<link rel="stylesheet" href="{{ asset('backend/admin/assets/css/prism.css') }}">
<link rel="stylesheet" href="{{ asset('backend/admin/assets/css/rangeslider.css') }}">
<link rel="stylesheet" href="{{ asset('backend/admin/assets/css/sweetalert.min.css') }}">
<link rel="stylesheet" href="{{ asset('backend/admin/assets/css/quill.snow.css') }}">
<link rel="stylesheet" href="{{ asset('backend/admin/assets/css/google-icon.css') }}">
<link rel="stylesheet" href="{{ asset('backend/admin/assets/css/remixicon.css') }}">
<link rel="stylesheet" href="{{ asset('backend/admin/assets/css/swiper-bundle.min.css') }}">
<link rel="stylesheet" href="{{ asset('backend/admin/assets/css/fullcalendar.main.css') }}">
<link rel="stylesheet" href="{{ asset('backend/admin/assets/css/jsvectormap.min.css') }}">
<link rel="stylesheet" href="{{ asset('backend/admin/assets/css/lightpick.css') }}">
<link rel="stylesheet" href="{{ asset('backend/admin/assets/css/style.css') }}">


<link href="{{ asset('vendor/flasher/flasher.min.css') }}" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.0/dist/sweetalert2.min.css" rel="stylesheet">
{{-- dropify --}}
<link rel="stylesheet" type="text/css" href="https://jeremyfagis.github.io/dropify/dist/css/dropify.min.css">
{{-- dropify and ck-editor start --}}
<style>
    .ck-editor__editable[role="textbox"] {
        min-height: 150px;
    }
    .dropify-wrapper .dropify-render {
        display: unset !important;
    }
</style>
{{-- dropify and ck-editor end --}}

<style>
    .fl-wrapper {
        z-index: 999999 !important;
    }
</style>

@stack('styles')
