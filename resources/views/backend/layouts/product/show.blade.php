@extends('backend.master')
<style>
    .gallery-thumb:hover {
        transform: scale(1.05);
        transition: all 0.3s ease-in-out;
        cursor: pointer;
    }

    .gallery-image-container:hover .delete-form {
        display: block !important;
    }
</style>
@section('title', $product->product_name)

@section('content')
    <div class="row">
        <div class="col-lg-10  rounded-4">
            <div class="card border-0 shadow rounded-4 overflow-hidden">
                <!-- Header inside card body -->
                <div class="card-body pb-0">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="mb-0">{{ $product->product_name }}</h3>
                        <a href="{{ route('product.index') }}" class="btn btn-sm btn-outline-dark">
                            ← Back to Products
                        </a>
                    </div>
                </div>

                <!-- Image + Details -->
                <div class="row g-0 px-4 pb-4">
                    <!-- Image Section -->
                    <div class="col-md-5">
                        <div class="pe-md-3 pb-3 pb-md-0">
                            <img src="{{ asset($product->product_image) }}" alt="{{ $product->product_name }}"
                                class="img-fluid w-100 rounded-3"
                                style="object-fit: cover; height: 350px; cursor: pointer;">
                        </div>
                    </div>



                    <!-- Details Section -->
                    <div class="col-md-7">
                        <div class="ps-md-3 pt-3 pt-md-0">
                            <div class="row mb-2">
                                <div class="col-6"><strong>Brand:</strong> {{ $product->brand }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-6"><strong>Product Code:</strong> {{ $product->product_code }}</div>
                                <div class="col-6"><strong>Type:</strong> {{ $product->product_type }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-6"><strong>Version:</strong> {{ $product->product_version_type }}</div>
                                <div class="col-6">
                                    <strong>Weight:</strong> {{ $product->weight }}
                                    {{ $product->weight ? $product->weight_unit : 'N/A' }}
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="col-6"><strong>Quantity:</strong>
                                    {{ $product->inventory->quantity }}</div>
                                <div class="col-6">
                                    <strong>Category:</strong> {{ $product->category->name ?? 'N/A' }}
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-6">
                                    <strong>Regular Price:</strong> KWD-{{ number_format($product->regular_price, 2) }}
                                </div>
                                <div class="col-6">
                                    <strong>Discount Price:</strong>
                                    @if ($product->discount_price)
                                        KWD-{{ number_format($product->discount_price, 2) }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </div>
                            </div>

                            <div class="mb-2">
                                <strong>Status:</strong>
                                @if ($product->inventory->stock_status == '1')
                                    <span class="badge bg-success">In stock</span>
                                @else
                                    <span class="badge bg-danger">Out of stock</span>
                                @endif
                            </div>
                        </div>

                        <!-- Gallery Section -->
                        @if ($product->galleries->count())
                            <div class="p-4 border-top">
                                <h6 class="fw-bold mb-3">Product Gallery</h6>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($product->galleries as $image)
                                        <div class="position-relative gallery-image-container border rounded shadow-sm"
                                            style="width: 120px; height: 120px; overflow: hidden;">

                                            <!-- Image -->
                                            <img src="{{ asset($image->image_path) }}" alt="Product Image"
                                                class="img-fluid h-100 w-100 gallery-thumb" style="object-fit: cover;">

                                            <!-- Delete Button (appears on hover) -->
                                            <form action="{{ route('product.image.destroy', $image->id) }}" method="POST"
                                                class="position-absolute top-0 end-0 m-1 d-none delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger p-1" title="Delete">
                                                    <i class="bi bi-x-lg"></i> {{-- You can also use feather icons --}}
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Description Section -->
                <div class="p-4 border-top">
                    <div class="mb-3">
                        <h6 class="fw-bold">Short Description</h6>
                        <p class="text-muted">{{ $product->short_description }}</p>
                    </div>

                    <div class="mb-3">
                        <h6 class="fw-bold">Description</h6>
                        <div class="border rounded p-3 bg-light">
                            {!! $product->description !!}
                        </div>
                    </div>

                    <div>
                        <h6 class="fw-bold">Additional Description</h6>
                        <div class="border rounded p-3 bg-light">
                            {!! $product->additional_description !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Optional scripts --}}
@endpush
