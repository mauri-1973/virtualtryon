<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{
    public function index()
    {
        $cart = Session::get('cart', []);
        $cartItems = [];
        $total = 0;

        foreach ($cart as $id => $item) {
            $product = Product::with('images')->find($id);
            if ($product) {
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'size' => $item['size'] ?? null,
                    'color' => $item['color'] ?? null,
                    'subtotal' => $product->price * $item['quantity']
                ];
                $total += $product->price * $item['quantity'];
            }
        }

        return view('cart.index', compact('cartItems', 'total'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'size' => 'nullable|string',
            'color' => 'nullable|string'
        ]);

        $product = Product::findOrFail($request->product_id);
        
        // Verificar inventario
        if ($product->inventory < $request->quantity) {
            return back()->with('error', 'No hay suficiente inventario disponible.');
        }

        $cart = Session::get('cart', []);
        $productId = $request->product_id;

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $request->quantity;
        } else {
            $cart[$productId] = [
                'quantity' => $request->quantity,
                'size' => $request->size,
                'color' => $request->color
            ];
        }

        Session::put('cart', $cart);

        return back()->with('success', 'Producto agregado al carrito.');
    }

    public function remove($id)
    {
        $cart = Session::get('cart', []);
        
        if (isset($cart[$id])) {
            unset($cart[$id]);
            Session::put('cart', $cart);
        }

        return back()->with('success', 'Producto eliminado del carrito.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $cart = Session::get('cart', []);
        
        if (isset($cart[$id])) {
            $product = Product::find($id);
            
            if ($product && $product->inventory >= $request->quantity) {
                $cart[$id]['quantity'] = $request->quantity;
                Session::put('cart', $cart);
                return back()->with('success', 'Cantidad actualizada.');
            } else {
                return back()->with('error', 'No hay suficiente inventario.');
            }
        }

        return back()->with('error', 'Producto no encontrado en el carrito.');
    }

    public function clear()
    {
        Session::forget('cart');
        return back()->with('success', 'Carrito vaciado.');
    }
}