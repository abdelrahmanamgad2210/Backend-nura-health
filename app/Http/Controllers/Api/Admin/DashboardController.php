<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\ClinicalCase;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return response()->json([
            'operations' => $this->operations(),
            'finance' => $this->finance(),
        ]);
    }

    private function operations(): array
    {
        return [
            'users_by_role' => User::query()->select('role', DB::raw('count(*) as count'))->groupBy('role')->pluck('count', 'role'),
            'orders_by_status' => Order::query()->select('status', DB::raw('count(*) as count'))->groupBy('status')->pluck('count', 'status'),
            'clinical_cases_by_status' => ClinicalCase::query()->select('status', DB::raw('count(*) as count'))->groupBy('status')->pluck('count', 'status'),
            'pending_clinical_reviews' => ClinicalCase::where('status', '!=', 'decided')->count(),
            'pharmacy_queue_length' => Prescription::where('status', 'ready_for_review')->count(),
            'total_assessments' => Assessment::count(),
            'urgent_assessments' => Assessment::where('urgent_flag', true)->count(),
        ];
    }

    private function finance(): array
    {
        $capturedRevenue = (float) OrderItem::whereNotNull('captured_at')->sum('due_now_amount');
        $pendingAuthorised = (float) OrderItem::whereNotNull('authorized_at')->whereNull('captured_at')->sum('due_now_amount');

        $revenueByDay = OrderItem::query()
            ->whereNotNull('captured_at')
            ->where('captured_at', '>=', now()->subDays(30))
            ->select(DB::raw("strftime('%Y-%m-%d', captured_at) as date"), DB::raw('sum(due_now_amount) as revenue'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $signupsByDay = User::query()
            ->where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw("strftime('%Y-%m-%d', created_at) as date"), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topProducts = OrderItem::query()
            ->whereNotNull('captured_at')
            ->select('product_slug', 'product_name', DB::raw('sum(due_now_amount) as revenue'), DB::raw('count(*) as orders_count'))
            ->groupBy('product_slug', 'product_name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        return [
            'captured_revenue' => $capturedRevenue,
            'pending_authorised_revenue' => $pendingAuthorised,
            'revenue_by_day' => $revenueByDay,
            'signups_by_day' => $signupsByDay,
            'top_products' => $topProducts,
            'total_orders' => Order::count(),
            'total_users' => User::count(),
        ];
    }
}
