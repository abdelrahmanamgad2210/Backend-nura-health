<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private const RULES = [
        'slug' => ['required', 'string', 'max:255'],
        'type' => ['required', 'in:direct,clinical,service'],
        'category' => ['required', 'string', 'max:255'],
        'category_ar' => ['nullable', 'string', 'max:255'],
        'name' => ['required', 'string', 'max:255'],
        'name_ar' => ['nullable', 'string', 'max:255'],
        'short_description' => ['required', 'string', 'max:255'],
        'short_description_ar' => ['nullable', 'string', 'max:255'],
        'long_description' => ['required', 'string'],
        'long_description_ar' => ['nullable', 'string'],
        'price' => ['nullable', 'numeric', 'min:0'],
        'due_now' => ['required', 'numeric', 'min:0'],
        'price_note' => ['nullable', 'string', 'max:255'],
        'price_note_ar' => ['nullable', 'string', 'max:255'],
        'badge' => ['nullable', 'string', 'max:255'],
        'badge_ar' => ['nullable', 'string', 'max:255'],
        'consult_only' => ['boolean'],
        'quiz_category' => ['nullable', 'string', 'max:255'],
        'includes' => ['required', 'array'],
        'includes_ar' => ['nullable', 'array'],
        'flow' => ['required', 'array'],
        'flow_ar' => ['nullable', 'array'],
    ];

    public function index()
    {
        return response()->json(['products' => Product::orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            ...self::RULES,
            'slug' => [...self::RULES['slug'], 'unique:products,slug'],
        ]);

        $product = Product::create($data);

        return response()->json(['product' => $product], 201);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            ...self::RULES,
            'slug' => [...self::RULES['slug'], 'unique:products,slug,'.$product->id],
        ]);

        $product->update($data);

        return response()->json(['product' => $product->fresh()]);
    }

    public function destroy(Product $product)
    {
        // order_items.product_id is nullOnDelete with its own snapshot columns
        // (product_name, product_slug, ...), so past orders are unaffected.
        $product->delete();

        return response()->json(status: 204);
    }
}
