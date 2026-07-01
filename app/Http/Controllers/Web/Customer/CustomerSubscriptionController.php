<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerSubscription;
use App\Models\Plan;
use Illuminate\Support\Facades\Auth;

class CustomerSubscriptionController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;
        $subscriptions = CustomerSubscription::where('tenant_id', $tenantId)->with('plan')->latest()->get();
        $activeSubscription = $subscriptions->firstWhere('status', 'active');
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        $daysLeft = $activeSubscription ? now()->diffInDays($activeSubscription->end_date, false) : null;
        return view('customer.subscription', compact('subscriptions', 'activeSubscription', 'plans', 'daysLeft'));
    }
}
