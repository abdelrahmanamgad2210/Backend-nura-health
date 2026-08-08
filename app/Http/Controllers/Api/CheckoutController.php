<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CheckoutService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(private CheckoutService $checkout)
    {
    }

    public function store(Request $request)
    {
        $order = $this->checkout->checkout($request->user());

        return response()->json(['order' => $order], 201);
    }
}
