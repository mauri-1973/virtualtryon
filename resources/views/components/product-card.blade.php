<div class="group relative">
    <div class="aspect-square overflow-hidden rounded-lg bg-gray-100 group-hover:opacity-90 transition-opacity">
        @if($product->main_image)
            <img src="{{ $product->main_image->image_url }}" 
                 alt="{{ $product->name }}" 
                 class="h-full w-full object-cover object-center">
        @else
            <div class="h-full w-full bg-gray-200 flex items-center justify-center">
                <i data-lucide="image" class="h-12 w-12 text-gray-400"></i>
            </div>
        @endif
        
        <div class="absolute top-2 right-2">
            <button class="flex h-8 w-8 items-center justify-center rounded-full bg-white shadow-sm transition-colors hover:bg-gray-100">
                <i data-lucide="heart" class="h-4 w-4"></i>
            </button>
        </div>
        
        @if($product->is_new)
            <span class="absolute top-2 left-2 bg-black text-white px-2.5 py-0.5 text-xs font-semibold rounded-full">Nuevo</span>
        @endif
        
        @if($product->is_sale)
            <span class="absolute top-2 left-2 bg-red-500 text-white px-2.5 py-0.5 text-xs font-semibold rounded-full">Oferta</span>
        @endif
    </div>
    
    <div class="mt-3 flex justify-between">
        <div>
            <h3 class="text-sm font-medium text-gray-900">
                <a href="{{ route('products.show', $product->id) }}">
                    <span aria-hidden="true" class="absolute inset-0"></span>
                    {{ $product->name }}
                </a>
            </h3>
            <p class="mt-1 text-sm text-gray-500">{{ $product->category->name }}</p>
        </div>
        <div class="text-sm font-medium">
            @if($product->is_sale && $product->original_price)
                <div class="flex items-center gap-1.5">
                    <span class="text-red-500">{{ $product->formatted_price }}</span>
                    <span class="text-gray-500 line-through">{{ $product->formatted_original_price }}</span>
                </div>
            @else
                <span class="text-gray-900">{{ $product->formatted_price }}</span>
            @endif
        </div>
    </div>
    
    <div class="mt-3 opacity-0 group-hover:opacity-100 transition-opacity">
        <button class="w-full btn-primary text-sm py-1">
            Agregar al Carrito
        </button>
    </div>
</div>
