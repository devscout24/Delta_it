@extends('backend.master')

@section('title', 'All Product')

@section('content')
    <div>
        <!-- row -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <!-- Card Header -->
                    <div class="card-header d-md-flex border-bottom-0">
                        <div class="flex-grow-1">
                            <a href="{{ route('product.create') }}" class="btn btn-primary">+ Add Product</a>
                        </div>
                        @if (session('email_success'))
                            <div class="alert alert-info alert-dismissible fade show" role="alert" id="emailSuccessAlert">
                                {{ session('email_success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif
                        {{-- <div class="mt-3 mt-md-0">
                            <a href="#!" class="btn btn-outline-white ms-2">Import</a>
                            <a href="#!" class="btn btn-outline-white ms-2">Export</a>
                        </div> --}}
                    </div>

                    <!-- Card Body -->
                    <div class="card-body">
                        <div class="table-responsive table-card">
                            <div id="example_wrapper" class="dataTables_wrapper dt-bootstrap5 no-footer pb-3 mb-3">
                                <div class="row dt-row  ">
                                    <div class="col-sm-12">
                                        <table id="productTable"
                                            class="table text-start text-nowrap table-centered mt-0 dataTable no-footer dtr-inline"
                                            style="width: 100%;" role="grid">

                                            <thead class="table-light">
                                                <tr role="row">
                                                    <!-- Checkbox -->
                                                    <th class="pe-0 ps-2" style="width: 42px;">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="checkAll">
                                                            <label class="form-check-label" for="checkAll"></label>
                                                        </div>
                                                    </th>
                                                    <th style="width: 50px;">SL</th>

                                                    <th style="width: 100px;">Product photo</th>
                                                    <th style="width: 100px;">Product Name</th>
                                                    <th style="width: 100px; text-align: start;">Quantity</th>
                                                    <th style="width: 100px;">Category</th>
                                                    <th style="width: 100px;">Price</th>
                                                    <th class="ps-2" style="width: 100px;">Status</th>
                                                    <th class="ps-2" style="width: 150px;">Action</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <!-- Filled by DataTables AJAX -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#productTable').DataTable({
                processing: true,
                serverSide: true,
                searching: true,
                ordering: false,
                paging: true,
                lengthChange: true,
                info: true,

                language: {
                    searchPlaceholder: "Search data...",
                    search: "",
                    paginate: {
                        previous: "Previous",
                        next: "Next"
                    },
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "No records available",
                    lengthMenu: "Show _MENU_ entries per page",
                    processing: "Loading..."
                },

                ajax: {
                    url: "{{ route('product.index') }}",
                    type: "GET"
                },

                columns: [{
                        data: 'checkbox',
                        name: 'checkbox',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'product_image',
                        name: 'product_image'
                    },
                    {
                        data: 'product_name',
                        name: 'product_name'
                    },

                    {
                        data: 'quantity',
                        name: 'quantity',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'category_id',
                        name: 'category_id'
                    },
                    {
                        data: 'regular_price',
                        name: 'regular_price'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });
        });
    </script>

    {{-- flash message hide  --}}
    {{-- <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alertBox = document.getElementById('emailSuccessAlert');
            if (alertBox) {
                // Auto hide after 3 seconds (3000ms)
                setTimeout(() => {
                    // Use Bootstrap's alert close function
                    const alert = new bootstrap.Alert(alertBox);
                    alert.close();
                }, 3000);
            }
        });
    </script> --}}
@endpush
