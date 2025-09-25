<?php

namespace App\Http\Controllers\Backend;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItemDetail;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class BackendController extends Controller
{
    // dashboard show
    public function index()
    {

        // $todayOrdersCount = Order::whereDate('created_at', today())->count();
        // $previousOrdersCount = Order::whereDate('created_at', today()->subDay())->count();
        // $completed = Order::whereMonth('created_at', now()->month)->where('status', 'completed')->count();
        // $canceled = Order::whereMonth('created_at', now()->month)->where('status', 'cancelled')->count();
        // $pending = Order::whereMonth('created_at', now()->month)->where('status', 'pending')->count();

        // Fetching pending orders and recent data

        // $pendingOrders = Order::with('user', 'billing', 'items.inventory')->where('status', 'pending')->limit(3)->get();
        // $recentCustomers = User::orderBy('created_at', 'desc')->take(3)->get();
        // $recentProducts = Product::with('category')->orderBy('created_at', 'desc')->take(3)->get();


        // $topSales = OrderItemDetail::with(['product.category',])
        //     ->select(
        //         'product_id',
        //         DB::raw('SUM(quantity) as total_sold'),
        //         DB::raw('MAX(created_at) as last_sold_date')
        //     )
        //     ->groupBy('product_id')
        //     ->orderByDesc('total_sold')
        //     ->limit(10)
        //     ->get();


        return view('backend.layouts.dashboard');
    }


    public function monthlyData()
    {
        $monthlyComparisons = [];

        for ($i = 0; $i < 6; $i++) {
            $monthDate = Carbon::now()->subMonths($i);
            $monthName = $monthDate->format('F'); // e.g., August

            $startOfMonth = $monthDate->copy()->startOfMonth();
            $endOfMonth = $monthDate->copy()->endOfMonth();

            $completed = Order::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->where('status', 'completed')
                ->count();

            $cancelled = Order::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->where('status', 'cancelled')
                ->count();

            $pending = Order::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->where('status', 'pending')
                ->count();

            $monthlyComparisons[] = [
                'month' => $monthName,
                'completed' => $completed,
                'cancelled' => $cancelled,
                'pending' => $pending,
            ];
        }
        return response()->json([
            'monthlyComparisons' => array_reverse($monthlyComparisons)
        ]);
    }

    // public function Page404(Request $request)
    // {
    //     return view('backend.layouts.dashboard');
    // }
}
