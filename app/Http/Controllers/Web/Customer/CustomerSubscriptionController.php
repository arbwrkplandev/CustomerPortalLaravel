<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Support\InternalApiGateway;

class CustomerSubscriptionController extends Controller
{
    public function __construct(protected InternalApiGateway $api) {}

    public function index()
    {
        $subscriptionResponse = $this->api->get('/customer/subscription');
        $plansResponse = $this->api->get('/customer/plans');

        $activeSubscription = $this->api->toEntities($subscriptionResponse['data'] ?? null);
        $plans = collect($this->api->toEntities($plansResponse['data'] ?? []));

        $subscriptions = collect();
        if ($activeSubscription) {
            $subscriptions->push($activeSubscription);
        }

        $daysLeft = $activeSubscription ? now()->diffInDays($activeSubscription->end_date, false) : null;

        return view('customer.subscription', compact('subscriptions', 'activeSubscription', 'plans', 'daysLeft'));
    }
}
