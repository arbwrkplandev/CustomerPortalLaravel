<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_subscriptions', 'base_amount')) {
                $table->decimal('base_amount', 10, 2)->nullable()->after('amount');
            }

            if (!Schema::hasColumn('customer_subscriptions', 'is_custom_rate')) {
                $table->boolean('is_custom_rate')->default(false)->after('base_amount');
            }
        });

        DB::table('customer_subscriptions')
            ->whereNull('base_amount')
            ->update([
                'base_amount' => DB::raw('amount'),
                'is_custom_rate' => false,
            ]);
    }

    public function down(): void
    {
        Schema::table('customer_subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('customer_subscriptions', 'is_custom_rate')) {
                $table->dropColumn('is_custom_rate');
            }

            if (Schema::hasColumn('customer_subscriptions', 'base_amount')) {
                $table->dropColumn('base_amount');
            }
        });
    }
};
