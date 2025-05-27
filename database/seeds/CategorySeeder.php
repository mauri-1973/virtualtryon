<?php

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['name' => 'Mujeres', 'description' => 'Ropa para mujeres'],
            ['name' => 'Hombres', 'description' => 'Ropa para hombres'],
            ['name' => 'Accesorios', 'description' => 'Accesorios de moda'],
            ['name' => 'Zapatos', 'description' => 'Calzado para todos'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
