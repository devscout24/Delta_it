@extends('backend.master')
<style>
    .card-custom {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 8px rgb(0 0 0 / 0.05);
        transition: box-shadow 0.3s ease;
    }

    .card-custom:hover {
        box-shadow: 0 8px 20px rgb(0 0 0 / 0.12);
    }

    #digitalClock {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-weight: 700;
        font-size: 1.5rem;
        color: #2563eb;
        /* blue-600 */
        user-select: none;
        min-width: 100px;
        text-align: center;
    }

    .welcome-text {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-weight: 600;
        color: #475569;
        /* slate-600 */
        margin-bottom: 0;
    }

    .icon-circle {
        width: 56px;
        height: 56px;
        background: #e0e7ff;
        /* light indigo */
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: inset 0 0 6px #c7d2fe;
    }

    .username {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-weight: 600;
        font-size: 1.1rem;
        color: #1e293b;
        /* slate-900 */
        margin-left: 16px;
    }
</style>

@section('content')
    <div class="container-fluid">

        <div class="row  ">

            <div class="col-xl-4 col-lg-4 mb-5">
                <div class="card h-100 rounded-3 shadow-sm card-custom">
                    <div class="card-body p-4 d-flex flex-column align-items-center">

                        <!-- Top row: Clock and Welcome message -->
                        <div class="d-flex justify-content-center align-items-center gap-3 mb-4 w-100">
                            <!-- Digital Clock -->
                            <div id="digitalClock">--:--:--</div>

                            <!-- Welcome Text -->
                            <h5 class="welcome-text">Welcome To Dashboard</h5>
                        </div>

                        <!-- Bottom row: Icon and Username -->
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-circle">
                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none"
                                    stroke="#3b82f6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                                    class="feather feather-smile">
                                    <circle cx="14" cy="14" r="13"></circle>
                                    <path d="M9 20s3.5 3.5 10.5 0"></path>
                                    <line x1="10" y1="13" x2="10.01" y2="13"></line>
                                    <line x1="18" y1="13" x2="18.01" y2="13"></line>
                                </svg>
                            </div>

                            <span class="username">{{ Auth::user()->name }}</span>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-4 mb-5">
                <div class="card h-100 card-lift">
                    <div class="card-body">
                        <!-- Title and Icon -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted fw-semi-bold">Orders Summary</span>
                            <span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="feather feather-shopping-bag text-primary">
                                    <path d="M6 2l1.5 4h9L18 2"></path>
                                    <path d="M3 6h18v14H3z"></path>
                                </svg>
                            </span>
                        </div>

                        <!-- Summary Grid -->
                        <div class="row g-3">
                            <!-- Today Orders -->
                            <div class="col-6">
                                <div
                                    class="border rounded p-2 h-100 d-flex flex-column justify-content-between text-center">
                                    {{-- <div class="fw-bold fs-4 text-info">{{ $todayOrdersCount }}</div> --}}
                                    <div class="d-flex align-items-center justify-content-center mt-2">
                                        <svg class="text-info me-1" xmlns="http://www.w3.org/2000/svg" width="16"
                                            height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-calendar">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2">
                                            </rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                        </svg>
                                        <span class="fw-semibold text-info small">Today Orders</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Previous Orders -->
                            <div class="col-6">
                                <div
                                    class="border rounded p-2 h-100 d-flex flex-column justify-content-between text-center">
                                    {{-- <div class="fw-bold fs-4 text-secondary">{{ $previousOrdersCount }}</div> --}}
                                    <div class="d-flex align-items-center justify-content-center mt-2">
                                        <svg class="text-secondary me-1" xmlns="http://www.w3.org/2000/svg" width="16"
                                            height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-clock">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        <span class="fw-semibold text-secondary small">Previous Orders</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-4 mb-5">
                <div class="card h-100 card-lift">
                    <div class="card-body">
                        <!-- Title and Card Icon -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted fw-semi-bold">Monthly Summary</span>
                            <span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="feather feather-bar-chart-2 text-primary">
                                    <line x1="18" y1="20" x2="18" y2="10"></line>
                                    <line x1="12" y1="20" x2="12" y2="4"></line>
                                    <line x1="6" y1="20" x2="6" y2="14"></line>
                                </svg>
                            </span>
                        </div>

                        <!-- 3 Horizontal Cards -->
                        <div class="row g-3">
                            <!-- Completed -->
                            <div class="col-4">
                                <div
                                    class="border rounded p-2 h-100 d-flex flex-column justify-content-between text-center">
                                    {{-- <div class="fw-bold fs-4 text-success">{{ $completed }}</div> --}}
                                    <div class="d-flex align-items-center justify-content-center mt-2">
                                        <svg class="text-success me-1" xmlns="http://www.w3.org/2000/svg" width="16"
                                            height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-check-circle">
                                            <path d="M9 12l2 2l4 -4"></path>
                                            <circle cx="12" cy="12" r="10"></circle>
                                        </svg>
                                        <span class="fw-semibold text-success small">Completed</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Canceled -->
                            <div class="col-4">
                                <div
                                    class="border rounded p-2 h-100 d-flex flex-column justify-content-between text-center">
                                    {{-- <div class="fw-bold fs-4 text-danger">{{ $canceled }}</div> --}}
                                    <div class="d-flex align-items-center justify-content-center mt-2">
                                        <svg class="text-danger me-1" xmlns="http://www.w3.org/2000/svg" width="16"
                                            height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-x-circle">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="15" y1="9" x2="9" y2="15"></line>
                                            <line x1="9" y1="9" x2="15" y2="15"></line>
                                        </svg>
                                        <span class="fw-semibold text-danger small">Canceled</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Pending -->
                            <div class="col-4">
                                <div
                                    class="border rounded p-2 h-100 d-flex flex-column justify-content-between text-center">
                                    {{-- <div class="fw-bold fs-4 text-warning">{{ $pending }}</div> --}}
                                    <div class="d-flex align-items-center justify-content-center mt-2">
                                        <svg class="text-warning me-1" xmlns="http://www.w3.org/2000/svg" width="16"
                                            height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-clock">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        <span class="fw-semibold text-warning small">Pending</span>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
            </div>

        </div>


        <div class="row ">
            <div class="col-xl-6 col-12 mb-5">
                <div class="card ">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Monthly Comparison</h4>
                        <i class="bi bi-bar-chart-fill me-2"></i>
                    </div>
                    <div class="card-body" style="height: 300px; margin-bottom:100px">
                        <div>
                            <canvas id="monthlyComparisonCanvas" style="min-height: 365px;">
                                Your browser does not support the canvas element.
                            </canvas>
                        </div>
                    </div>
                </div>
            </div>
            {{-- {{ $topSales->category }} --}}
            <div class="col-xl-6 col-12 mb-5">
                <div class="card ">
                    <div class="card-header  d-flex justify-content-between align-items-center ">
                        <h4 class="mb-0 text-muted">Top Sales</h4>
                        <i class="bi bi-currency-dollar display-7 text-success"></i>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm small">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Sold (Qty)</th>
                                    <th>Last Sold</th>

                                </tr>
                            </thead>
                            {{-- <tbody>
                                @foreach ($topSales as $sales)
                                    <tr>
                                        <td>{{ $sales->product->product_name }}</td>
                                        <td>{{ $sales->product->category->name }}</td>
                                        <td>{{ $sales->total_sold }}</td>
                                        <td>{{ \Carbon\Carbon::parse($sales->last_sold_date)->format('d M Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody> --}}
                        </table>


                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Pending Orders --}}
            <div class="col-xl-6 col-lg-6 mb-5">
                <div class="card ">
                    <div class="card-header">
                        <h4><i class="bi bi-hourglass-split"></i> Pending Orders</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm small">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Billing Info</th>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            {{-- <tbody>
                                @forelse($pendingOrders as $pending)
                                    <tr>
                                        <td><a href="#!">{{ $pending->order_number }}</a></td>
                                        <td>
                                            <span class="fw-bold">{{ $pending->user->name }}</span><br>
                                            <span class="text-muted">{{ $pending->billing->name }}</span>
                                        </td>
                                        <td>
                                            @foreach ($pending->items as $item)
                                                <span class="text-muted">{{ $item->product->product_name }}</span><br>
                                            @endforeach
                                        </td>
                                        <td>
                                            @foreach ($pending->items as $item)
                                                <span class="fw-bold">{{ $item->inventory->quantity }}</span><br>
                                            @endforeach
                                        </td>
                                        <td>
                                            <span class="fw-bold">{{ $pending->total_amount }}</span>
                                        </td>
                                        @if ($pending->status == 'pending')
                                            <td>
                                                <a href="" class="btn btn-sm btn-outline-primary">Accept</a>
                                            </td>
                                        @endif

                                        @if ($pending->status == 'completed')
                                            <td>
                                                <a href="" class="btn btn-sm btn-outline-danger">Reject</a>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No pending orders found.</td>
                                    </tr>
                                @endforelse
                            </tbody> --}}
                        </table>

                        {{-- See More Button --}}
                        <div class="text-end">
                            <a href="" class="btn btn-sm btn-link">See More</a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent Registered Customers --}}
            <div class="col-xl-3 col-lg-6 mb-5">
                <div class="card ">
                    <div class="card-header">
                        <h4><i class="bi bi-person-plus me-2"></i> Recent Registered Customers</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm small">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            {{-- <tbody>
                                @foreach ($recentCustomers as $customer)
                                    <tr>
                                        <td><span class="fw-bold">{{ $customer->name }}</span></td>
                                        <td><span class="fw-bold">{{ $customer->email }}</span></td>
                                        <td><span class="fw-bold">{{ $customer->created_at->format('F d, Y') }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody> --}}
                        </table>

                        {{-- See More Button --}}
                        {{-- <div class="text-end">
                            <a href="{{ route('customer.index') }}" class="btn btn-sm btn-link">See More</a>
                        </div> --}}
                    </div>
                </div>
            </div>

            {{-- Recent Products --}}
            <div class="col-xl-3 col-lg-6 mb-5">
                <div class="card ">
                    <div class="card-header">
                        <h4><i class="bi bi-box-seam me-2"></i> Recent Products Added</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm small">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            {{-- <tbody>
                                @foreach ($recentProducts as $product)
                                    <tr>
                                        <td><span class="fw-bold">{{ $product->product_name }}</span></td>
                                        <td><span class="fw-bold">{{ $product->category->name }}</span></td>
                                        <td><span class="fw-bold">{{ $product->created_at->format('F d, Y') }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody> --}}
                        </table>

                        {{-- See More Button --}}
                        <div class="text-end">
                            <a href="" class="btn btn-sm btn-link">See More</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- <div class="col-lg-6 mb-5">
                <div class="card h-100">
                    <div class="card-header">
                        <h4 class="mb-0">Recent Orders</h4>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive table-card">
                            <table class="table text-nowrap mb-0 table-centered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Product</th>
                                        <th>Amount</th>
                                        <th>Vendor</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><a href="#!">#DU0007</a></td>
                                        <td><a href="#!" class="text-inherit">
                                                <img src="../assets/images/avatar/avatar-8.jpg" alt="Image"
                                                    class="avatar-xs avatar rounded-circle me-1">
                                                <span>Michell Forge</span>
                                            </a></td>
                                        <td>Headphone</td>
                                        <td class="text-success ">$99.00</td>
                                        <td>DollarSmart</td>
                                        <td><span class="badge badge-success-soft text-success">Paid</span></td>
                                    </tr>
                                    <tr>
                                        <td><a href="#!">#DU0006</a></td>
                                        <td><a href="#!" class="text-inherit">
                                                <img src="../assets/images/avatar/avatar-11.jpg" alt="Image"
                                                    class="avatar-xs avatar rounded-circle me-1">
                                                <span>Judy Huston</span>
                                            </a></td>
                                        <td>Beauty Lips</td>
                                        <td class="text-success ">$399.00</td>
                                        <td>Snail Bouque</td>
                                        <td><span class="badge badge-success-soft text-success">Paid</span></td>
                                    </tr>
                                    <tr>
                                        <td><a href="#!">#DU0005</a></td>
                                        <td><a href="#!" class="text-inherit">
                                                <span class="avatar avatar-xs me-1">
                                                    <span
                                                        class="avatar-initials rounded-circle fs-6 bg-primary-soft">OT</span>
                                                </span>
                                                <span>Olivier Tassi</span>
                                            </a></td>
                                        <td>Lady Bag</td>
                                        <td class="text-success ">$729.00</td>
                                        <td>Cartmax</td>
                                        <td><span class="badge badge-danger-soft text-danger">Cancel</span></td>
                                    </tr>
                                    <tr>
                                        <td><a href="#!">#DU0004</a></td>
                                        <td><a href="#!" class="text-inherit">
                                                <img src="../assets/images/avatar/avatar-2.jpg" alt="Image"
                                                    class="avatar-xs avatar rounded-circle me-1">
                                                <span>Cynth Spur</span>
                                            </a></td>
                                        <td>Headphone</td>
                                        <td class="text-success ">$225.00</td>
                                        <td>DollarSmart</td>
                                        <td><span class="badge badge-warning-soft text-warning">Pending</span></td>
                                    </tr>
                                    <tr>
                                        <td><a href="#!">#DU0003</a></td>
                                        <td><a href="#!" class="text-inherit">
                                                <span class="avatar avatar-xs me-1">
                                                    <span
                                                        class="avatar-initials rounded-circle fs-6 bg-danger-soft text-danger">RJ</span>
                                                </span>
                                                <span>Ruby Jackson</span>

                                            </a></td>
                                        <td>Furniture</td>
                                        <td class="text-success ">$639.00</td>
                                        <td>Megaplex</td>
                                        <td><span class="badge badge-success-soft text-success">Paid</span></td>
                                    </tr>
                                    <tr>
                                        <td><a href="#!">#DU0002</a></td>
                                        <td><a href="#!" class="text-inherit">
                                                <img src="../assets/images/avatar/avatar-3.jpg" alt="Image"
                                                    class="avatar-xs avatar rounded-circle me-1">
                                                <span>Joshua Galher</span>

                                            </a></td>
                                        <td>Accessories</td>
                                        <td class="text-success ">$89.00</td>
                                        <td>Shopperia</td>
                                        <td><span class="badge badge-success-soft text-success">Paid</span></td>
                                    </tr>
                                    <tr>
                                        <td><a href="#!">#DU0001</a></td>
                                        <td><a href="#!" class="text-inherit">
                                                <img src="../assets/images/avatar/avatar-4.jpg" alt="Image"
                                                    class="avatar-xs avatar rounded-circle me-1">
                                                <span>Michael Bro</span>

                                            </a></td>
                                        <td>Kitchen</td>
                                        <td class="text-success ">$29.00</td>
                                        <td>Frcommerce</td>
                                        <td><span class="badge badge-success-soft text-success">Paid</span></td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>
                    </div>




                </div>
            </div>
            <div class="col-lg-6 mb-5">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">

                        <h4 class="mb-0">Top Seller</h4>
                        <div>

                            <select class="form-select form-select-sm">
                                <option selected="">Report</option>
                                <option value="1">Report Download</option>
                                <option value="2">Export</option>
                                <option value="3">Import</option>
                            </select>

                        </div>

                    </div>
                    <div class="card-body">
                        <div class="table-responsive table-card">
                            <table class="table text-nowrap mb-0 table-centered">

                                <tbody>
                                    <tr>

                                        <td>
                                            <div class=" d-flex">
                                                <a href="#!"> <img src="../assets/images/svg/brand-logo-1.svg"
                                                        alt="Image"></a>
                                                <div class="ms-3 lh-1">
                                                    <h5 class="mb-0"><a href="#!" class="text-inherit">Brilliant
                                                            Boutique</a></h5>
                                                    <small>Bryan M. Flores</small>

                                                </div>
                                            </div>
                                        </td>
                                        <td>Beauty Lips</td>
                                        <td>
                                            <div class="lh-1">
                                                <h5 class="mb-0">3214

                                                </h5>
                                                <small>Stock</small>
                                            </div>
                                        </td>
                                        <td class="  text-dark">$529511</td>
                                        <td>42% <span class="ms-2"><svg xmlns="http://www.w3.org/2000/svg"
                                                    width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    class="feather feather-trending-up text-success icon-xs">
                                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                                    <polyline points="17 6 23 6 23 12"></polyline>
                                                </svg></span></td>
                                    </tr>
                                    <tr>


                                        <td>
                                            <div class=" d-flex">
                                                <a href="#!"> <img src="../assets/images/svg/brand-logo-2.svg"
                                                        alt="Image"></a>
                                                <div class="ms-3 lh-1">
                                                    <h5 class="mb-0"><a href="#!" class="text-inherit">Cartmax</a>
                                                    </h5>
                                                    <small>Mamie Lacasse</small>

                                                </div>
                                            </div>
                                        </td>

                                        <td>Lady Bag</td>
                                        <td>
                                            <div class="lh-1">
                                                <h5 class="mb-0">4213

                                                </h5>
                                                <small>Stock</small>
                                            </div>
                                        </td>
                                        <td class="  text-dark">$308719</td>
                                        <td>89% <span class="ms-2"><svg xmlns="http://www.w3.org/2000/svg"
                                                    width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    class="feather feather-trending-up text-success icon-xs">
                                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                                    <polyline points="17 6 23 6 23 12"></polyline>
                                                </svg></span></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class=" d-flex">
                                                <a href="#!"> <img src="../assets/images/svg/brand-logo-3.svg"
                                                        alt="Image"></a>
                                                <div class="ms-3 lh-1">
                                                    <h5 class="mb-0"><a href="#!"
                                                            class="text-inherit">DollarSmart</a></h5>
                                                    <small>Diane Hilbert</small>

                                                </div>
                                            </div>
                                        </td>


                                        <td>Headphone</td>
                                        <td>
                                            <div class="lh-1">
                                                <h5 class="mb-0">756

                                                </h5>
                                                <small>Stock</small>
                                            </div>
                                        </td>
                                        <td class="  text-dark">$24859</td>
                                        <td>89% <span class="ms-2"><svg xmlns="http://www.w3.org/2000/svg"
                                                    width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    class="feather feather-trending-up text-success icon-xs">
                                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                                    <polyline points="17 6 23 6 23 12"></polyline>
                                                </svg></span></td>
                                    </tr>
                                    <tr>

                                        <td>
                                            <div class=" d-flex">
                                                <a href="#!"> <img src="../assets/images/svg/brand-logo-4.svg"
                                                        alt="Image"></a>
                                                <div class="ms-3 lh-1">
                                                    <h5 class="mb-0"><a href="#!"
                                                            class="text-inherit">Megaplex</a></h5>
                                                    <small>Charity Parris</small>

                                                </div>
                                            </div>
                                        </td>


                                        <td>Furniture</td>
                                        <td>
                                            <div class="lh-1">
                                                <h5 class="mb-0">252

                                                </h5>
                                                <small>Stock</small>
                                            </div>
                                        </td>
                                        <td class="  text-dark">$3204</td>
                                        <td>63% <span class="ms-2"><svg xmlns="http://www.w3.org/2000/svg"
                                                    width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    class="feather feather-trending-up text-success icon-xs">
                                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                                    <polyline points="17 6 23 6 23 12"></polyline>
                                                </svg></span></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class=" d-flex">
                                                <a href="#!"> <img src="../assets/images/svg/brand-logo-5.svg"
                                                        alt="Image"></a>
                                                <div class="ms-3 lh-1">
                                                    <h5 class="mb-0"><a href="#!"
                                                            class="text-inherit">Shopperia</a></h5>
                                                    <small>Maurice Phillips</small>

                                                </div>
                                            </div>
                                        </td>


                                        <td>Accessories</td>
                                        <td>
                                            <div class="lh-1">
                                                <h5 class="mb-0">636

                                                </h5>
                                                <small>Stock</small>
                                            </div>
                                        </td>
                                        <td class="  text-dark">$92079</td>
                                        <td>55% <span class="ms-2"><svg xmlns="http://www.w3.org/2000/svg"
                                                    width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    class="feather feather-trending-up text-success icon-xs">
                                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                                    <polyline points="17 6 23 6 23 12"></polyline>
                                                </svg></span></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class=" d-flex">
                                                <a href="#!"> <img src="../assets/images/svg/brand-logo-6.svg"
                                                        alt="Image"></a>
                                                <div class="ms-3 lh-1">
                                                    <h5 class="mb-0"><a href="#!"
                                                            class="text-inherit">Freshcommerce</a></h5>
                                                    <small>Timothy Doss</small>

                                                </div>
                                            </div>
                                        </td>

                                        <td>Kitchen</td>
                                        <td>
                                            <div class="lh-1">
                                                <h5 class="mb-0">522

                                                </h5>
                                                <small>Stock</small>
                                            </div>
                                        </td>
                                        <td class="  text-dark">$3204</td>
                                        <td>67% <span class="ms-2"><svg xmlns="http://www.w3.org/2000/svg"
                                                    width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    class="feather feather-trending-up text-success icon-xs">
                                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                                    <polyline points="17 6 23 6 23 12"></polyline>
                                                </svg></span></td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between align-items-center  ">
                        <span>Showing <span class="text-dark">5</span> of <span class="text-dark">25</span>
                            Results</span>
                        <nav>
                            <ul class="pagination  mb-0 pagination-sm">
                                <li class="page-item disabled">
                                    <a class="page-link " href="#!"><i class="mdi mdi-chevron-left"></i></a>
                                </li>
                                <li class="page-item active">
                                    <a class="page-link " href="#!">1</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link " href="#!">2</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link " href="#!">3</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link " href="#!"><i class="mdi mdi-chevron-right"></i></a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div> --}}
        </div>




    </div>
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            $.ajax({
                url: '/dashboard-data',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    const monthlyData = data.monthlyComparisons;
                    if (!monthlyData) {
                        console.error('monthlyComparisons is missing from the response');
                        return;
                    }
                    // Extract data
                    const labels = monthlyData.map(item => item.month);
                    const completed = monthlyData.map(item => item.completed);
                    const cancelled = monthlyData.map(item => item.cancelled);
                    const pending = monthlyData.map(item => item.pending);

                    // Chart.js setup
                    const ctx = document.getElementById('monthlyComparisonCanvas').getContext('2d');
                    const monthlyChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                    label: 'Completed',
                                    data: completed,
                                    backgroundColor: 'rgba(54, 162, 235, 0.7)' // Blue
                                },
                                {
                                    label: 'Cancelled',
                                    data: cancelled,
                                    backgroundColor: 'rgba(255, 99, 132, 0.7)' // Red
                                },
                                {
                                    label: 'Pending',
                                    data: pending,
                                    backgroundColor: 'rgba(255, 206, 86, 0.7)' // Yellow
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                }
            });
        });
    </script>

    </script>
    <script>
        function updateClock() {
            const clock = document.getElementById('digitalClock');
            if (!clock) return;
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const mins = String(now.getMinutes()).padStart(2, '0');
            const secs = String(now.getSeconds()).padStart(2, '0');
            clock.textContent = `${hours}:${mins}:${secs}`;
        }
        setInterval(updateClock, 1000);
        updateClock(); // initial call
    </script>
@endpush
