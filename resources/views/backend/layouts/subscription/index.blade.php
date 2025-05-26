@extends('backend.app')
@section('title', 'Dynamic page')

@push('styles')
    {{-- CKEditor CDN --}}
    <script src="https://cdn.ckeditor.com/ckeditor5/23.0.0/classic/ckeditor.js"></script>
    <style>
        .ck-editor__editable_inline {
            min-height: 300px;
        }
    </style>
@endpush
@section('content')

    <div class="main-content-container overflow-hidden">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <h3 class="mb-0">User Planning Page</h3>

            <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
                <ol class="breadcrumb align-items-center mb-0 lh-1">
                    <li class="breadcrumb-item">
                        <a href="#" class="d-flex align-items-center text-decoration-none">
                            <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                            <span class="text-secondary fw-medium hover">Dashboard</span>
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <span class="fw-medium">User Planning Page</span>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <span class="fw-medium">User Planning </span>
                    </li>
                </ol>
            </nav>
        </div>

        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">


                <div class="mb-4">
                    <h4 class="fs-20 mb-1">User Planning Page</h4>
                    <p class="fs-15">User Planning Create Or Update here.</p>
                </div>
                <form action="{{  route('user.planingStore') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('POST')
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group mb-4">
                                <label class="label text-secondary">Title</label>
                                <div class="form-group position-relative">
                                    <input type="text"
                                        class="form-control text-dark ps-5 h-55 @error('title') is-invalid @enderror"
                                        name="title" value="{{ $data->title ?? " " }}" placeholder="Enter section title here">
                                </div>
                                @error('title')
                                    <div id="title-error" class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="form-group mb-4">
                                <label class="label text-secondary">Description</label>
                                <div class="form-group position-relative">
                                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" id="page_content"
                                        placeholder="description here">{{ $data->description ?? " " }}</textarea>
                                </div>
                                @error('description')
                                    <div id="description-error" class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>


                        <div class="col-lg-12">
                            <div class="form-group mb-4">
                                <label class="label text-secondary">Price</label>
                                <div class="form-group position-relative">
                                    <input type="number"
                                        class="form-control text-dark ps-5 h-55 @error('price') is-invalid @enderror"
                                        name="price" value="{{ $data->price ?? " " }}" placeholder="Enter section price here">
                                </div>
                                @error('price')
                                    <div id="price-error" class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="form-group ">
                                <label class="label text-secondary mb-1"> Image<span class="text-danger">*</span></label>
                                <input class="dropify form-control @error('image') is-invalid @enderror" type="file"
                                    name="image" data-default-file="{{ isset($data->image) ? asset($data->image) : '' }}"
                                    accept="image/*">

                                @error('image')
                                    <div id="image-error" class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>

                    <div class="row mt-3">
                        <div class="col-lg-12">
                            <div class="d-flex flex-wrap gap-3">
                                <button type="reset" class="btn btn-danger py-2 px-4 fw-medium fs-16 text-white"
                                    onclick="window.location.href='{{ route('user.planing') }}'">Cancel</button>
                                <button type="submit" class="btn btn-primary py-2 px-4 fw-medium fs-16"> <i
                                        class="ri-check-line text-white fw-medium"></i> Submit</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        ClassicEditor
            .create(document.querySelector('#page_content'), {

            })
            .catch(error => {
                console.error(error);
            });

        $('.dropify').dropify();
    </script>
@endpush
