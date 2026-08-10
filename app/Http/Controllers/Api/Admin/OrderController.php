<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::query()
            ->with(['user:id,name,email', 'items'])
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->get();

        return response()->json(['orders' => $orders]);
    }

    public function show(Order $order)
    {
        $order->load('user:id,name,email', 'items.clinicalCase.decisions', 'items.prescription');

        return response()->json(['order' => $order]);
    }
}
