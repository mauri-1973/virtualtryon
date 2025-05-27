<?php

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $womenCategory = Category::where('name', 'Mujeres')->first();
        $menCategory = Category::where('name', 'Hombres')->first();

        $products = [
            [
                'name' => 'Camiseta de Algodón',
                'description' => 'Camiseta cómoda de algodón orgánico perfecta para el uso diario.',
                'price' => 29.99,
                'category_id' => $womenCategory->id,
                'inventory' => 45,
                'is_featured' => true,
                'is_new' => true,
            ],
            [
                'name' => 'Jeans Ajustados',
                'description' => 'Jeans de corte ajustado con diseño moderno.',
                'price' => 59.99,
                'original_price' => 79.99,
                'category_id' => $menCategory->id,
                'inventory' => 32,
                'is_featured' => true,
                'is_sale' => true,
            ],
            [
                'name' => 'Vestido de Verano',
                'description' => 'Vestido ligero perfecto para los días de verano.',
                'price' => 49.99,
                'category_id' => $womenCategory->id,
                'inventory' => 18,
                'is_featured' => true,
            ],
            [
                'name' => 'Chaqueta de Cuero',
                'description' => 'Chaqueta de cuero genuino con estilo clásico.',
                'price' => 199.99,
                'category_id' => $menCategory->id,
                'inventory' => 12,
                'is_featured' => true,
            ],
        ];

        foreach ($products as $productData) {
            $product = Product::create($productData);
            
            // Crear imagen de ejemplo para cada producto
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => 'products/sample-' . $product->id . '.jpg',
                'alt_text' => $product->name,
                'is_primary' => true,
                'sort_order' => 1,
            ]);
        }
    }
}
