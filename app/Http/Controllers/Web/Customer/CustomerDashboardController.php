<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Support\InternalApiGateway;
use Illuminate\Support\Facades\Auth;
use DateTimeZone;

class CustomerDashboardController extends Controller
{
    public function __construct(protected InternalApiGateway $api) {}

    public function index()
    {
        $user = Auth::user();

        $response = $this->api->get('/customer/dashboard');
        $payload = $this->api->toEntities($response['data'] ?? []);

        $tenant = $payload?->tenant ?? $user->tenant;
        $activeSubscription = $payload?->active_subscription;
        $announcements = collect($payload?->announcements ?? []);
        $pendingContracts = collect($payload?->pending_contract_list ?? []);
        $stats = array_merge([
            'open_tickets' => 0,
            'pending_contracts' => 0,
            'unpaid_invoices' => 0,
            'signed_contracts' => 0,
            'subscription_days_left' => null,
        ], $payload?->stats instanceof \App\Support\ApiEntity ? $payload->stats->toArray() : (array) ($payload?->stats ?? []));

        $daysLeft = $stats['subscription_days_left'] ?? null;

        $timezone = $this->resolveTimezone($tenant?->timezone, $tenant?->country);
        $localNow = now()->setTimezone($timezone);
        $greeting = $this->resolveGreeting($localNow->hour);
        $motivation = $this->dailyMotivation();

        return view('customer.dashboard', compact(
            'activeSubscription', 'daysLeft', 'announcements',
            'pendingContracts', 'stats', 'tenant', 'localNow', 'timezone', 'greeting', 'motivation'
        ));
    }

    protected function resolveTimezone(?string $timezone, ?string $country): string
    {
        if (!empty($timezone) && in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            return $timezone;
        }

        $country = strtolower(trim((string) $country));
        $timezoneMap = [
            'india' => 'Asia/Kolkata',
            'usa' => 'America/New_York',
            'united states' => 'America/New_York',
            'us' => 'America/New_York',
            'uk' => 'Europe/London',
            'united kingdom' => 'Europe/London',
            'canada' => 'America/Toronto',
            'australia' => 'Australia/Sydney',
            'singapore' => 'Asia/Singapore',
        ];

        return $timezoneMap[$country] ?? 'UTC';
    }

    protected function resolveGreeting(int $hour): string
    {
        return match (true) {
            $hour < 12 => 'Good morning',
            $hour < 17 => 'Good afternoon',
            $hour < 21 => 'Good evening',
            default => 'Good night',
        };
    }

    protected function dailyMotivation(): string
    {
        $messages = [
            'Consistency beats intensity. Small progress today builds big outcomes tomorrow.',
            'Focus on what you can control, then execute with clarity.',
            'Every challenge you solve today becomes confidence for tomorrow.',
            'Progress compounds. One disciplined day at a time.',
            'Your future results are hidden in your daily habits. Keep going.',
            'Do the next right thing, then repeat.',
            'Momentum is built by starting before you feel ready.',
        ];

        return $messages[(int) now()->dayOfYear % count($messages)];
    }
}
