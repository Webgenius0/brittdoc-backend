@extends('backend.app')
@section('title', 'Banner')

@push('styles')
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/css/dropify.css">
@endpush

@section('content')
    <div class="main-content-container overflow-hidden">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <h3 class="mb-0">CMS Home Page Banner</h3>
        </div>

        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">

                <div class="mb-4">
                    <h4 class="fs-20 mb-1">CMS Home Page Banner</h4>
                    <p class="fs-15">Update Home Page Banner and site details here.</p>
                </div>

                <form action="{{ route('cms.home_page.banner.update_banner') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <!-- Title Field -->
                        <div class="col-lg-6">
                            <div class="form-group mb-4">
                                <label class="label text-secondary">Title<span class="text-danger">*</span></label>
                                <div class="form-group position-relative">
                                    <input type="text"
                                        class="form-control text-dark ps-5 h-55 @error('title') is-invalid @enderror"
                                        name="title" value="{{ old('title', $data->title ?? '') }}" required
                                        placeholder="Enter Title here">
                                </div>
                                @error('title')
                                    <div id="title-error" class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>


                        <!-- Description Field -->
                        <div class="col-lg-6">
                            <div class="form-group mb-4">
                                <label class="label text-secondary">Description<span class="text-danger">*</span></label>
                                <div class="form-group position-relative">
                                    <textarea class="form-control text-dark ps-5 h-55 @error('description') is-invalid @enderror" name="description"
                                        placeholder="Enter Description here">{{ old('description', $data->description ?? '') }}</textarea>
                                </div>
                                @error('description')
                                    <div id="description-error" class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <div class="form-group ">
                            <label class="label text-secondary mb-1">Background Image<span
                                    class="text-danger">*</span></label>
                            <input class="dropify form-control @error('background_image') is-invalid @enderror"
                                type="file" name="background_image" accept="image/*"
                                data-default-file="{{ isset($data) && $data->background_image ? asset($data->background_image) : '' }}">
                            @error('background_image')
                                <div id="background_image-error" class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex flex-wrap gap-3">
                        {{-- <button type="submit" class="btn btn-danger py-2 px-4 fw-medium fs-16 text-white">Cancel</button> --}}
                        <button type="submit" class="btn btn-primary py-2 px-4 fw-medium fs-16"> <i
                                class="ri-check-line text-white fw-medium"></i> Submit Banner</button>
                    </div>
                </div>
            </div>
            </form>
        </div>
    </div>



@endsection

@push('scripts')
    <script src="{{ asset('frontend/assets/js/plugins/jquery-3.7.1.min.js') }}"></script>
    <script type="text/javascript" src="https://jeremyfagis.github.io/dropify/dist/js/dropify.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.dropify').dropify();
        })
    </script>
@endpush
