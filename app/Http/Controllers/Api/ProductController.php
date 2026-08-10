<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private const AR_FIELDS = [
        'category_ar', 'name_ar', 'short_description_ar', 'long_description_ar',
        'price_note_ar', 'badge_ar', 'includes_ar', 'flow_ar',
    ];

    private function present(Product $product, string $locale): array
    {
        return collect($product->localized($locale))
            ->except(self::AR_FIELDS)
            ->all();
    }

    public function index(Request $request)
    {
        $locale = $request->query('locale', 'en');

        $products = Product::orderBy('id')->get()
            ->map(fn (Product $product) => $this->present($product, $locale));

        return response()->json(['products' => $products]);
    }

    public function show(Request $request, Product $product)
    {
        $locale = $request->query('locale', 'en');

        return response()->json(['product' => $this->present($product, $locale)]);
    }
}
