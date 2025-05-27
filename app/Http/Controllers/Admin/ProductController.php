<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'images'])
            ->latest()
            ->paginate(15);

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'inventory' => 'required|integer|min:0',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $product = Product::create($request->only([
            'name', 'description', 'price', 'original_price', 
            'category_id', 'inventory', 'is_featured', 'is_new', 'is_sale'
        ]));

        // Manejar imágenes
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');
                
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'alt_text' => $product->name,
                    'is_primary' => $index === 0,
                    'sort_order' => $index + 1
                ]);
            }
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Producto creado correctamente.');
    }

    public function show(Product $product)
    {
        $product->load(['category', 'images']);
        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $categories = Category::all();
        $product->load('images');
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'inventory' => 'required|integer|min:0',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $product->update($request->only([
            'name', 'description', 'price', 'original_price', 
            'category_id', 'inventory', 'is_featured', 'is_new', 'is_sale'
        ]));

        // Manejar nuevas imágenes
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');
                
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'alt_text' => $product->name,
                    'is_primary' => false,
                    'sort_order' => $product->images()->count() + $index + 1
                ]);
            }
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product)
    {
        // Eliminar imágenes del storage
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Producto eliminado correctamente.');
    }
}
