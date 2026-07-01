<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Contract;
use App\Models\CustomerSubscription;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WrkPlanSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Admin Users ─────────────────────────────────
        $superAdmin = User::firstOrCreate(['email' => 'superadmin@wrkplan.com'], [
            'name'       => 'Super Admin',
            'role'       => 'superadmin',
            'password'   => Hash::make('password'),
            'is_active'  => true,
        ]);

        $admin = User::firstOrCreate(['email' => 'admin@wrkplan.com'], [
            'name'      => 'Alex Admin',
            'role'      => 'admin',
            'password'  => Hash::make('password'),
            'is_active' => true,
        ]);

        // ─── Plans ───────────────────────────────────────
        $starter = Plan::firstOrCreate(['slug' => 'starter'], [
            'name'            => 'Starter',
            'description'     => 'Perfect for small teams getting started',
            'monthly_price'   => 49.00,
            'quarterly_price' => 135.00,
            'annual_price'    => 499.00,
            'features'        => ['Up to 5 users', '10 GB storage', 'Email support', 'Basic analytics'],
            'max_users'       => 5,
            'is_active'       => true,
            'sort_order'      => 1,
        ]);

        $professional = Plan::firstOrCreate(['slug' => 'professional'], [
            'name'            => 'Professional',
            'description'     => 'For growing businesses',
            'monthly_price'   => 149.00,
            'quarterly_price' => 399.00,
            'annual_price'    => 1499.00,
            'features'        => ['Up to 25 users', '100 GB storage', 'Priority support', 'Advanced analytics', 'API access'],
            'max_users'       => 25,
            'is_active'       => true,
            'sort_order'      => 2,
        ]);

        $enterprise = Plan::firstOrCreate(['slug' => 'enterprise'], [
            'name'            => 'Enterprise',
            'description'     => 'For large organizations',
            'monthly_price'   => 499.00,
            'quarterly_price' => 1399.00,
            'annual_price'    => 4999.00,
            'features'        => ['Unlimited users', '1 TB storage', 'Dedicated support', 'Custom integrations', 'SLA guarantee', 'On-premises option'],
            'max_users'       => 9999,
            'is_active'       => true,
            'sort_order'      => 3,
        ]);

        // ─── Demo Tenants ─────────────────────────────────
        $tenantsData = [
            [
                'company_name'  => 'Acme Corporation',
                'contact_name'  => 'John Doe',
                'contact_email' => 'john@acme.com',
                'contact_phone' => '+1-555-0100',
                'city'          => 'New York',
                'country'       => 'United States',
                'status'        => 'active',
                'plan'          => $professional,
                'cycle'         => 'monthly',
                'user_password' => 'password',
            ],
            [
                'company_name'  => 'TechStart Labs',
                'contact_name'  => 'Sarah Chen',
                'contact_email' => 'sarah@techstart.io',
                'contact_phone' => '+1-555-0200',
                'city'          => 'San Francisco',
                'country'       => 'United States',
                'status'        => 'active',
                'plan'          => $starter,
                'cycle'         => 'quarterly',
                'user_password' => 'password',
            ],
            [
                'company_name'  => 'Global Ventures Ltd',
                'contact_name'  => 'Marcus Johnson',
                'contact_email' => 'marcus@globalventures.com',
                'contact_phone' => '+44-20-1234-5678',
                'city'          => 'London',
                'country'       => 'United Kingdom',
                'status'        => 'active',
                'plan'          => $enterprise,
                'cycle'         => 'annual',
                'user_password' => 'password',
            ],
            [
                'company_name'  => 'Sunrise Media',
                'contact_name'  => 'Emma Wilson',
                'contact_email' => 'emma@sunrisemedia.com',
                'contact_phone' => '+1-555-0300',
                'city'          => 'Chicago',
                'country'       => 'United States',
                'status'        => 'trial',
                'plan'          => null,
                'cycle'         => null,
                'user_password' => 'password',
            ],
        ];

        $createdTenants = [];
        foreach ($tenantsData as $td) {
            $tenant = Tenant::firstOrCreate(['contact_email' => $td['contact_email']], [
                'company_name'  => $td['company_name'],
                'slug'          => Str::slug($td['company_name']) . '-' . Str::random(4),
                'contact_name'  => $td['contact_name'],
                'contact_phone' => $td['contact_phone'],
                'city'          => $td['city'],
                'country'       => $td['country'],
                'status'        => $td['status'],
            ]);

            // Create customer user
            $user = User::firstOrCreate(['email' => $td['contact_email']], [
                'tenant_id' => $tenant->id,
                'name'      => $td['contact_name'],
                'role'      => 'customer',
                'password'  => Hash::make($td['user_password']),
                'is_active' => true,
            ]);

            // Assign subscription
            if ($td['plan'] && $td['cycle']) {
                $startDate = Carbon::now()->subMonths(2);
                $endDate = match($td['cycle']) {
                    'monthly'   => $startDate->copy()->addMonth(),
                    'quarterly' => $startDate->copy()->addMonths(3),
                    'annual'    => $startDate->copy()->addYear(),
                };

                $sub = CustomerSubscription::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'plan_id' => $td['plan']->id],
                    [
                        'billing_cycle'     => $td['cycle'],
                        'status'            => 'active',
                        'start_date'        => $startDate,
                        'end_date'          => $endDate,
                        'next_renewal_date' => $endDate,
                        'amount'            => $td['plan']->getPriceForCycle($td['cycle']),
                        'currency'          => 'USD',
                        'created_by'        => $admin->id,
                    ]
                );

                // Create invoice
                $inv = Invoice::firstOrCreate(['invoice_number' => 'INV-2024-' . str_pad($tenant->id, 4, '0', STR_PAD_LEFT)], [
                    'tenant_id'       => $tenant->id,
                    'subscription_id' => $sub->id,
                    'status'          => 'paid',
                    'issue_date'      => $startDate,
                    'due_date'        => $startDate->copy()->addDays(30),
                    'paid_date'       => $startDate->copy()->addDays(5),
                    'subtotal'        => $sub->amount,
                    'total_amount'    => $sub->amount,
                    'currency'        => 'USD',
                    'line_items'      => [['description' => $td['plan']->name . ' Plan', 'quantity' => 1, 'unit_price' => $sub->amount, 'total' => $sub->amount]],
                    'created_by'      => $admin->id,
                ]);
            }

            // Create a demo contract
            $contract = Contract::firstOrCreate(['contract_number' => 'CTR-' . strtoupper(Str::random(8))], [
                'tenant_id'    => $tenant->id,
                'title'        => 'Service Agreement - ' . $td['company_name'],
                'type'         => 'service',
                'status'       => 'pending_signature',
                'start_date'   => Carbon::now(),
                'end_date'     => Carbon::now()->addYear(),
                'html_content' => '<h2>Service Agreement</h2><p>This agreement is between WrkPlan and ' . $td['company_name'] . '.</p><p>The platform services are provided as described in the subscription plan.</p>',
                'signer_email' => $td['contact_email'],
                'created_by'   => $admin->id,
            ]);

            // Create support ticket
            $ticket = SupportTicket::create([
                'tenant_id'     => $tenant->id,
                'created_by'    => $user->id,
                'ticket_number' => 'TKT-' . strtoupper(Str::random(8)),
                'subject'       => 'How do I access my invoices?',
                'description'   => 'I\'m trying to find where to download my invoice PDFs. Could you please guide me?',
                'priority'      => 'medium',
                'category'      => 'billing',
                'status'        => 'open',
            ]);
            SupportTicketMessage::create([
                'ticket_id'   => $ticket->id,
                'sender_id'   => $user->id,
                'sender_type' => 'customer',
                'message'     => 'I\'m trying to find where to download my invoice PDFs. Could you please guide me?',
            ]);

            $createdTenants[] = $tenant;
        }

        // ─── Announcements ───────────────────────────────
        Announcement::firstOrCreate(['title' => 'Welcome to WrkPlan!'], [
            'content'      => 'We\'re excited to have you on board. Explore your dashboard to see your subscription details, contracts, and invoices.',
            'type'         => 'success',
            'visibility'   => 'all',
            'is_published' => true,
            'published_at' => now(),
            'created_by'   => $admin->id,
        ]);

        Announcement::firstOrCreate(['title' => 'Scheduled Maintenance - Jan 15, 2025'], [
            'content'      => 'We will be performing scheduled maintenance on January 15, 2025 from 2:00 AM - 4:00 AM UTC. The platform will be temporarily unavailable.',
            'type'         => 'maintenance',
            'visibility'   => 'all',
            'is_published' => true,
            'published_at' => now()->subDays(2),
            'expires_at'   => now()->addDays(10),
            'created_by'   => $admin->id,
        ]);

        Announcement::firstOrCreate(['title' => 'New Feature: E-Sign Contracts'], [
            'content'      => 'You can now sign contracts directly in the portal! No more printing and scanning. Try it now under Contracts.',
            'type'         => 'feature',
            'visibility'   => 'all',
            'is_published' => true,
            'published_at' => now()->subDays(5),
            'created_by'   => $admin->id,
        ]);

        $this->command->info('✅ WrkPlan demo data seeded successfully!');
        $this->command->newLine();
        $this->command->line('╔══════════════════════════════════════════╗');
        $this->command->line('║         DEMO CREDENTIALS                 ║');
        $this->command->line('╠══════════════════════════════════════════╣');
        $this->command->line('║ SuperAdmin: superadmin@wrkplan.com       ║');
        $this->command->line('║ Admin:      admin@wrkplan.com            ║');
        $this->command->line('║ Customer:   john@acme.com                ║');
        $this->command->line('║ Customer:   sarah@techstart.io           ║');
        $this->command->line('║ All passwords: password                  ║');
        $this->command->line('╚══════════════════════════════════════════╝');
    }
}

