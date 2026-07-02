<?php

namespace App\Services\Admin;

use App\Models\Tenant;
use App\Models\User;
use App\Models\CustomerSubscription;
use App\Models\Plan;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use DateTimeZone;

class TenantService
{
    public function list(array $filters = [], int $perPage = 15)
    {
        $query = Tenant::with(['activeSubscription.plan']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%$search%")
                  ->orWhere('contact_email', 'like', "%$search%")
                  ->orWhere('contact_name', 'like', "%$search%");
            });
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['sort'])) {
            $query->orderBy($filters['sort'], $filters['direction'] ?? 'asc');
        } else {
            $query->latest();
        }

        return $query->paginate($perPage);
    }

    public function create(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            $companyName = $data['company_name'] ?? $data['name'] ?? 'New Customer';
            $timezone = $data['timezone'] ?? $this->resolveTimezone($data['country'] ?? null);

            $tenant = Tenant::create([
                'company_name'  => $companyName,
                'corp_id'       => strtoupper((string) ($data['corp_id'] ?? Str::upper(Str::slug($companyName, '')) . '-' . Str::upper(Str::random(4)))),
                'slug'          => Str::slug($companyName) . '-' . Str::random(4),
                'contact_name'  => $data['contact_name'],
                'contact_email' => $data['contact_email'],
                'contact_phone' => $data['contact_phone'] ?? null,
                'address'       => $data['address'] ?? null,
                'city'          => $data['city'] ?? null,
                'country'       => $data['country'] ?? null,
                'timezone'      => $timezone,
                'status'        => $data['status'] ?? 'trial',
            ]);

            // Create primary customer user
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $data['contact_name'],
                'username'  => $data['username'],
                'email'     => $data['user_email'] ?? $data['contact_email'],
                'role'      => 'customer',
                'password'  => Hash::make($data['contact_password'] ?? $data['password'] ?? 'password123'),
                'is_active' => true,
            ]);

            $this->logAction('create', 'tenant', $tenant->id, null, $tenant->toArray());

            if (!empty($data['plan_id'])) {
                $this->assignSubscription($tenant, [
                    'plan_id' => $data['plan_id'],
                    'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                    'custom_rate' => $data['custom_rate'] ?? null,
                    'currency' => $data['currency'] ?? 'USD',
                    'notes' => $data['notes'] ?? 'Assigned during customer onboarding',
                ]);
            }

            return $tenant->load('users');
        });
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        $old = $tenant->toArray();
        $tenant->update(array_filter([
            'company_name'  => $data['company_name'] ?? null,
            'contact_name'  => $data['contact_name'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'address'       => $data['address'] ?? null,
            'city'          => $data['city'] ?? null,
            'country'       => $data['country'] ?? null,
            'status'        => $data['status'] ?? null,
        ], fn($v) => $v !== null));

        $this->logAction('update', 'tenant', $tenant->id, $old, $tenant->fresh()->toArray());
        return $tenant->fresh();
    }

    public function toggleStatus(Tenant $tenant): Tenant
    {
        $oldStatus = $tenant->status;
        $newStatus = $tenant->status === 'active' ? 'inactive' : 'active';
        $tenant->update(['status' => $newStatus]);
        $this->logAction('toggle_status', 'tenant', $tenant->id, ['status' => $oldStatus], ['status' => $newStatus]);
        return $tenant->fresh();
    }

    protected function resolveTimezone(?string $country): string
    {
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

        $candidate = $timezoneMap[$country] ?? 'UTC';

        return in_array($candidate, DateTimeZone::listIdentifiers(), true) ? $candidate : 'UTC';
    }

    public function assignSubscription(Tenant $tenant, array $data): CustomerSubscription
    {
        return DB::transaction(function () use ($tenant, $data) {
            $plan = Plan::findOrFail($data['plan_id']);
            $cycle = $data['billing_cycle'];
            $startDate = Carbon::parse($data['start_date'] ?? now());
            $endDate = $this->calculateEndDate($startDate, $cycle);
            $baseAmount = $plan->getPriceForCycle($cycle);
            $customRate = array_key_exists('custom_rate', $data) && $data['custom_rate'] !== null && $data['custom_rate'] !== ''
                ? (float) $data['custom_rate']
                : null;
            $finalAmount = $customRate ?? $baseAmount;
            $isOverride = $customRate !== null && abs($customRate - $baseAmount) > 0.00001;

            // Expire old active subscriptions
            CustomerSubscription::where('tenant_id', $tenant->id)
                ->where('status', 'active')
                ->update(['status' => 'cancelled']);

            $subscription = CustomerSubscription::create([
                'tenant_id'         => $tenant->id,
                'plan_id'           => $plan->id,
                'billing_cycle'     => $cycle,
                'status'            => 'active',
                'start_date'        => $startDate,
                'end_date'          => $endDate,
                'next_renewal_date' => $endDate,
                'amount'            => $finalAmount,
                'base_amount'       => $baseAmount,
                'is_custom_rate'    => $isOverride,
                'currency'          => strtoupper((string) ($data['currency'] ?? 'USD')),
                'notes'             => $this->buildSubscriptionNotes($data['notes'] ?? null, $baseAmount, $isOverride),
                'created_by'        => auth()->id(),
            ]);

            $tenant->update(['status' => 'active']);
            $this->logAction('assign_subscription', 'subscription', $subscription->id, null, $subscription->toArray());

            return $subscription->load('plan');
        });
    }

    /**
     * Update an existing active subscription in-place.
     * If plan_id changes, cancels old and creates new subscription.
     * If only custom_rate / billing_cycle changes, updates the existing row.
     */
    public function updateSubscription(Tenant $tenant, array $data): CustomerSubscription
    {
        $active = CustomerSubscription::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        $planChanged     = !empty($data['plan_id']) && ((int) $data['plan_id']) !== (int) ($active?->plan_id);
        $cycleChanged    = !empty($data['billing_cycle']) && $data['billing_cycle'] !== $active?->billing_cycle;
        $customRateValue = array_key_exists('custom_rate', $data) && $data['custom_rate'] !== null && $data['custom_rate'] !== ''
            ? (float) $data['custom_rate']
            : null;

        // If plan or cycle changed, just call assignSubscription (it handles cancelling old)
        if ($planChanged || $cycleChanged || !$active) {
            return $this->assignSubscription($tenant, $data);
        }

        // Same plan & cycle — just update the rate in-place
        $plan        = $active->plan;
        $baseAmount  = $plan->getPriceForCycle($active->billing_cycle);
        $finalAmount = $customRateValue ?? $baseAmount;
        $isOverride  = $customRateValue !== null && abs($customRateValue - $baseAmount) > 0.00001;

        $old = $active->toArray();
        $active->update([
            'amount'         => $finalAmount,
            'base_amount'    => $baseAmount,
            'is_custom_rate' => $isOverride,
            'currency'       => strtoupper((string) ($data['currency'] ?? $active->currency ?? 'USD')),
            'notes'          => $this->buildSubscriptionNotes($data['notes'] ?? null, $baseAmount, $isOverride),
        ]);

        $this->logAction('update_subscription', 'subscription', $active->id, $old, $active->fresh()->toArray());

        return $active->fresh()->load('plan');
    }

    public function resetUserPassword(Tenant $tenant, User $user, string $newPassword): User
    {
        if ((int) $user->tenant_id !== (int) $tenant->id) {
            throw new \InvalidArgumentException('User does not belong to this tenant.');
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        $this->logAction(
            'reset_password',
            'user',
            $user->id,
            null,
            ['tenant_id' => $tenant->id, 'user_id' => $user->id]
        );

        return $user->fresh();
    }

    protected function calculateEndDate(Carbon $start, string $cycle): Carbon
    {
        return match ($cycle) {
            'monthly'   => $start->copy()->addMonth(),
            'quarterly' => $start->copy()->addMonths(3),
            'annual'    => $start->copy()->addYear(),
            default     => $start->copy()->addMonth(),
        };
    }

    protected function buildSubscriptionNotes(?string $notes, float $baseAmount, bool $isOverride): ?string
    {
        $parts = [];

        if (!empty($notes)) {
            $parts[] = trim($notes);
        }

        if ($isOverride) {
            $parts[] = 'Base plan rate: ' . number_format($baseAmount, 2) . ' (custom rate override applied).';
        }

        return empty($parts) ? null : implode(' ', $parts);
    }

    protected function logAction(string $action, string $module, int $entityId, ?array $old, ?array $new): void
    {
        try {
            AuditLog::create([
                'user_id'     => auth()->id(),
                'tenant_id'   => auth()->user()?->tenant_id,
                'action'      => $action,
                'module'      => $module,
                'entity_id'   => $entityId,
                'entity_type' => $module,
                'old_values'  => $old,
                'new_values'  => $new,
                'ip_address'  => request()->ip(),
            ]);
        } catch (\Exception) {}
    }
}
