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

class TenantService
{
    public function list(array $filters = [], int $perPage = 15)
    {
        $query = Tenant::with(['subscriptions' => fn($q) => $q->where('status', 'active')->with('plan')]);

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
            $tenant = Tenant::create([
                'company_name'  => $companyName,
                'slug'          => Str::slug($companyName) . '-' . Str::random(4),
                'contact_name'  => $data['contact_name'],
                'contact_email' => $data['contact_email'],
                'contact_phone' => $data['contact_phone'] ?? null,
                'address'       => $data['address'] ?? null,
                'city'          => $data['city'] ?? null,
                'country'       => $data['country'] ?? null,
                'status'        => $data['status'] ?? 'trial',
            ]);

            // Create primary customer user
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $data['contact_name'],
                'email'     => $data['contact_email'],
                'role'      => 'customer',
                'password'  => Hash::make($data['contact_password'] ?? $data['password'] ?? 'password123'),
                'is_active' => true,
            ]);

            $this->logAction('create', 'tenant', $tenant->id, null, $tenant->toArray());
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
        $newStatus = $tenant->status === 'active' ? 'inactive' : 'active';
        $tenant->update(['status' => $newStatus]);
        $this->logAction('toggle_status', 'tenant', $tenant->id, ['status' => !$newStatus], ['status' => $newStatus]);
        return $tenant->fresh();
    }

    public function assignSubscription(Tenant $tenant, array $data): CustomerSubscription
    {
        return DB::transaction(function () use ($tenant, $data) {
            $plan = Plan::findOrFail($data['plan_id']);
            $cycle = $data['billing_cycle'];
            $startDate = Carbon::parse($data['start_date'] ?? now());
            $endDate = $this->calculateEndDate($startDate, $cycle);

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
                'amount'            => $plan->getPriceForCycle($cycle),
                'currency'          => 'USD',
                'notes'             => $data['notes'] ?? null,
                'created_by'        => auth()->id(),
            ]);

            $tenant->update(['status' => 'active']);
            $this->logAction('assign_subscription', 'subscription', $subscription->id, null, $subscription->toArray());

            return $subscription->load('plan');
        });
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
