<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->withCount(['orders', 'assessments'])
            ->when($request->query('role'), fn ($query, $role) => $query->where('role', $role))
            ->latest()
            ->get();

        return response()->json(['users' => $users]);
    }
}
