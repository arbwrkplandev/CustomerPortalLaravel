<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiWiredPortalSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_portal_pages_render_with_api_bridge(): void
    {
        $admin = User::factory()->create([
            'tenant_id' => null,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $routes = [
            '/admin/dashboard',
            '/admin/customers',
            '/admin/plans',
            '/admin/contracts',
            '/admin/invoices',
            '/admin/tickets',
            '/admin/announcements',
            '/admin/audit',
        ];

        foreach ($routes as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_customer_portal_pages_render_with_api_bridge(): void
    {
        $tenant = Tenant::create([
            'company_name' => 'Acme India Pvt Ltd',
            'corp_id' => 'ACME-IND',
            'slug' => 'acme-india',
            'contact_name' => 'Acme Admin',
            'contact_email' => 'contact+' . Str::random(5) . '@acme.test',
            'status' => 'active',
            'timezone' => 'Asia/Kolkata',
            'country' => 'India',
        ]);

        $customer = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'customer',
            'is_active' => true,
            'username' => 'customer_' . Str::lower(Str::random(6)),
        ]);

        $this->actingAs($customer);

        $routes = [
            '/portal/dashboard',
            '/portal/subscription',
            '/portal/invoices',
            '/portal/contracts',
            '/portal/support',
            '/portal/support/create',
        ];

        foreach ($routes as $uri) {
            $this->get($uri)->assertOk();
        }
    }
}
