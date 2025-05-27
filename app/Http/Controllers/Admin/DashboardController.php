<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_products' => Product::count(),
            'active_products' => Product::where('status', 'active')->count(),
            'total_categories' => Category::count(),
            'featured_products' => Product::where('is_featured', true)->count(),
            'new_products' => Product::where('is_new', true)->count(),
            'sale_products' => Product::where('is_sale', true)->count(),
        ];

        $recentProducts = Product::with(['category', 'images'])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentProducts'));
    }
}