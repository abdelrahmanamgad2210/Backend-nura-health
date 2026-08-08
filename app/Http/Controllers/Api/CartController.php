<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    /**
     * Products and the cart are browsable without an account. A logged-in
     * user's cart is keyed by user_id as before; a guest's cart is keyed by
     * a long-lived, httpOnly `guest_cart_token` cookie instead. Checkout
     * still requires login, so a guest cart is merged into the account at
     * that point (see AuthController::mergeGuestCart()).
     */
    private function ownerColumn(Request $request): array
    {
        if ($request->user()) {
            return ['column' => 'user_id', 'value' => $request->user()->id];
        }

        $token = $request->cookie('guest_cart_token');

        return ['column' => 'guest_token', 'value' => $token, 'isNewGuest' => ! $token];
    }

    private function attachGuestCookieIfNeeded($response, array $owner, ?string &$newToken = null)
    {
        if (($owner['isNewGuest'] ?? false) && $newToken) {
            $response->cookie('guest_cart_token', $newToken, 60 * 24 * 365, null, null, false, true, false, 'lax');
        }

        return $response;
    }

    public function index(Request $request)
    {
        $owner = $this->ownerColumn($request);

        if (! $owner['value']) {
            return response()->json(['items' => []]);
        }

        $items = CartItem::where($owner['column'], $owner['value'])->with('product')->get();

        return response()->json(['items' => $items]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
        ]);

        $owner = $this->ownerColumn($request);
        $newToken = null;

        if (! $owner['value']) {
            $newToken = (string) Str::uuid();
            $owner['value'] = $newToken;
        }

        $item = CartItem::firstOrCreate([
            $owner['column'] => $owner['value'],
            'product_id' => $data['product_id'],
        ]);

        $response = response()->json(['item' => $item->load('product')], 201);

        return $this->attachGuestCookieIfNeeded($response, $owner, $newToken);
    }

    public function destroy(Request $request, CartItem $cartItem)
    {
        $owner = $this->ownerColumn($request);

        abort_unless(
            $owner['value'] && $cartItem->{$owner['column']} === $owner['value'],
            403
        );

        $cartItem->delete();

        return response()->json(['message' => 'Removed']);
    }
}
