<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function show($slug, Request $request)
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $query = Product::with(['images', 'category'])
            ->where('category_id', $category->id)
            ->where('status', 'active');

        // Filtrar por búsqueda
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Ordenar
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate(12);

        return view('categories.show', compact('category', 'products'));
    }
}