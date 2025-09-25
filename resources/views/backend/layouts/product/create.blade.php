@extends('backend.master')

@push('styles')
    <style>
        .mini-preview-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('title', isset($product) ? 'Update Product' : 'Create Product')

@section('content')
    <form action="{{ isset($product) ? route('product.update', $product->id) : route('product.store') }}"
        enctype="multipart/form-data" method="post">
        <div class="card shadow-sm mb-2">
            <div class="card-header  text-white text-center">
                <h5 class="mb-0">
                    @if (isset($product))
                        <i class="bi bi-pencil-square me-2"></i> Edit Product
                    @else
                        <i class="bi bi-plus-circle me-2"></i> Add Product
                    @endif
                </h5>
            </div>
        </div>
        @csrf
        @if (isset($product))
            @method('PUT')
        @endif

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-7">
                <div class="card mb-4">
                    <div class="card-header">Product Information</div>
                    <div class="card-body">
                        <!-- Product Title -->
                        <div class="mb-3">
                            <label class="form-label">Product Title <span class="text-danger">*</span></label>
                            <input type="text" name="product_name" class="form-control" required
                                value="{{ old('product_name', $product->product_name ?? '') }}">
                            @error('product_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>


                        <!--  Description -->
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control summernote" name="description">{{ old('description', $product->description ?? '') }}</textarea>
                        </div>


                        <!-- Chart -->
                        <div class="mb-3">
                            <label class="form-label">Chart</label>
                            <textarea class="form-control summernote" name="chart">{{ old('chart', $product->chart ?? '') }}</textarea>
                        </div>

                        <!-- Shipping -->
                        <div class="mb-3">
                            <label class="form-label">Shipping</label>
                            <textarea class="form-control summernote" name="shipping">{{ old('shipping', $product->shipping ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">Product Images</div>
                    <div class="card-body">
                        <!-- Featured Image -->
                        <div class="mb-3">
                            <label class="form-label">Featured Image</label>
                            <input type="file" name="featured_image" class="form-control" accept="image/*"
                                onchange="previewFeaturedImage(this)">
                            @error('featured_image')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="mt-2">
                                <img id="featuredImagePreview"
                                    src="{{ isset($product->featured_image) ? asset($product->featured_image) : '' }}"
                                    alt="Featured Image Preview"
                                    class="img-thumbnail {{ isset($product->featured_image) ? '' : 'd-none' }}"
                                    width="200">
                            </div>
                        </div>

                        <!-- Product Image (Separate if needed) -->
                        @isset($product->product_image)
                            <div class="mb-3">
                                <label class="form-label">Product Main Image</label>
                                <div class="mt-2">
                                    <img id="productImagePreview"
                                        src="{{ isset($product->product_image) ? asset($product->product_image) : '' }}"
                                        alt="Product Image" class="img-thumbnail" width="100">
                                </div>
                            </div>
                        @endisset

                        <!-- Gallery Upload -->
                        <div class="mb-3">
                            <label class="form-label">Gallery Images</label>
                            <input type="file" name="gallery_images[]" class="form-control" accept="image/*" multiple
                                onchange="previewGalleryImages(this)">
                            <div class="row mt-3" id="galleryPreview"></div>
                        </div>

                        <!-- Existing Gallery -->
                        <div class="mb-3">
                            @isset($product->galleries)
                                <label class="form-label">Existing Gallery Images</label>
                            @endisset

                            <div class="row">
                                @foreach (isset($product->galleries) ? $product->galleries : [] as $gallery)
                                    <div class="col-md-3 mb-2">
                                        <img src="{{ asset($gallery->image_path) }}" alt="Gallery Image"
                                            class="img-thumbnail" width="80">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <!-- Submit -->
                    <div class="col-12 d-flex justify-content-between gap-2 mx-auto p-4">
                        <a href="{{ route('product.index') }}" class="btn btn-secondary w-50">Back</a>
                        <button type="submit" class="btn btn-primary w-50">
                            {{ isset($product) ? 'Update Product' : 'Create Product' }}
                        </button>
                    </div>
                </div>

            </div>

            <!-- Right Column -->
            <div class="col-lg-5">
                <div class="card mb-4">
                    <div class="card-header">Product Meta</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Product Code</label>
                            <input type="text" name="product_code" class="form-control" required
                                value="{{ old('product_code', $product->product_code ?? '') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select " required>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ old('category_id', $product->category_id ?? '') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Max Capacity</label>
                            <input type="text" name="max_capacity" class="form-control"
                                value="{{ old('max_capacity', $product->max_capacity ?? '') }}">
                        </div>


                        <div class="mb-3">
                            <label class="form-label">EQT</label>
                            <input type="text" name="eqt" class="form-control"
                                value="{{ old('eqt', $product->eqt ?? '') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Condition</label>
                            <input type="text" name="condition" class="form-control"
                                value="{{ old('condition', $product->condition ?? '') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control"
                                value="{{ old('location', $product->location ?? '') }}">
                        </div>


                        <!-- Price & Stock -->
                        <div class="mb-3">
                            <label class="form-label">Regular Price</label>
                            <input type="text" name="regular_price" class="form-control"
                                value="{{ old('regular_price', $product->regular_price ?? '') }}">
                            @error('regular_price')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Discount Price</label>
                            <input type="text" name="discount_price" class="form-control"
                                value="{{ old('discount_price', $product->discount_price ?? '') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" class="form-control" min="0"
                                value="{{ old('quantity', $inventory->quantity ?? '') }}">
                            @error('quantity')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>



                        <!-- Status -->
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1"
                                    {{ old('status', $product->status ?? '1') == '1' ? 'selected' : '' }}>Published
                                </option>
                                <option value="0"
                                    {{ old('status', $product->status ?? '') == '0' ? 'selected' : '' }}>Unpublished
                                </option>
                                <option value="2"
                                    {{ old('status', $product->status ?? '') == '2' ? 'selected' : '' }}>Draft</option>
                            </select>
                        </div>

                    </div>
                </div>
            </div>


        </div>
    </form>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            $('.summernote').summernote({
                height: 200
            });
            $('.select2').select2({
                placeholder: "Select options",
                allowClear: true
            });
            toggleWeightFields(document.getElementById('hasWeightYes').checked);
        });

        function toggleWeightFields(show) {
            document.getElementById('weightFields').style.display = show ? 'block' : 'none';
        }

        function previewFeaturedImage(input) {
            const preview = document.getElementById('featuredImagePreview');
            const file = input.files?.[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                preview.classList.add('d-none');
            }
        }

        function previewGalleryImages(input) {
            const previewContainer = document.getElementById('galleryPreview');
            previewContainer.innerHTML = '';
            Array.from(input.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = e => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'img-thumbnail mini-preview-img';
                    const col = document.createElement('div');
                    col.className = 'col-auto mb-2';
                    col.appendChild(img);
                    previewContainer.appendChild(col);
                };
                reader.readAsDataURL(file);
            });
        }
    </script>
@endpush
