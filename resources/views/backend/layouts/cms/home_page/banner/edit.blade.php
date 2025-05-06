@extends('backend.app')
@section('title', 'Settings')

@push('styles')
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/css/dropify.css">
@endpush

@section('content')
    <div class="main-content-container overflow-hidden">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <h3 class="mb-0">Edit CMS Home Page Banner</h3>
        </div>

        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">

                <div class="mb-4">
                    <h4 class="fs-20 mb-1">Edit CMS Home Page Banner</h4>
                    <p class="fs-15">Update the details of the Home Page Banner here.</p>
                </div>

                <form action="{{ route('cms.home_page.banner.update', $data->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT') <!-- Include the method to specify this is an update request -->
                    <div class="row">
                        <!-- Title Field -->
                        <div class="col-lg-6">
                            <div class="form-group mb-4">
                                <label class="label text-secondary">Title<span class="text-danger">*</span></label>
                                <div class="form-group position-relative">
                                    <input type="text"
                                        class="form-control text-dark ps-5 h-55 @error('title') is-invalid @enderror"
                                        name="title" value="{{ old('title', $data->title) }}" required
                                        placeholder="Enter Title here">
                                </div>
                                @error('title')
                                    <div id="title-error" class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>



                        <!-- 2nd Subtitle Field -->
                        <div class="col-lg-6">
                            <div class="form-group mb-4">
                                <label class="label text-secondary">Sub Title<span class="text-danger">*</span></label>
                                <div class="form-group position-relative">
                                    <input type="text"
                                        class="form-control text-dark ps-5 h-55 @error('description') is-invalid @enderror"
                                        name="description" value="{{ old('description', $data->description) }}"
                                        placeholder="Enter Image Upper Title here">
                                </div>
                                @error('description')
                                    <div id="description-error" class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <!-- Subtitle Field -->
                        <div class="col-lg-6">
                            <div class="form-group mb-4">
                                <label class="label text-secondary">Image Upper Title<span
                                        class="text-danger">*</span></label>
                                <div class="form-group position-relative">
                                    <input type="text"
                                        class="form-control text-dark ps-5 h-55 @error('sub_title') is-invalid @enderror"
                                        name="sub_title" value="{{ old('sub_title', $data->sub_title) }}"
                                        placeholder="Enter Sub Title here">
                                </div>
                                @error('sub_title')
                                    <div id="sub_title-error" class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <!-- 3rd Subtitle Field -->
                        <div class="col-lg-6">
                            <div class="form-group mb-4">
                                <label class="label text-secondary">Image Upper Sub Title<span
                                        class="text-danger">*</span></label>
                                <div class="form-group position-relative">
                                    <input type="text"
                                        class="form-control text-dark ps-5 h-55 @error('sub_description') is-invalid @enderror"
                                        name="sub_description" value="{{ old('sub_description', $data->sub_description) }}"
                                        placeholder="Enter Image Upper Sub Title here">
                                </div>
                                @error('sub_description')
                                    <div id="sub_description-error" class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Image Field -->
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label class="label text-secondary mb-1">Image</label>
                            <input class="dropify form-control @error('image') is-invalid @enderror" type="file"
                                name="image" data-default-file="{{ $data->image ? asset($data->image) : '' }}">
                            @error('image')
                                <div id="image-error" class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="d-flex flex-wrap gap-3">
                                <button type="submit" class="btn btn-primary py-2 px-4 fw-medium fs-16">
                                    <i class="ri-check-line text-white fw-medium"></i> Update Banner
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
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
