<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'corp_id')) {
                $table->string('corp_id', 30)->nullable()->after('company_name');
                $table->unique('corp_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username', 50)->nullable()->after('name');
                $table->unique('username');
            }
        });

        DB::table('tenants')
            ->select(['id', 'company_name', 'corp_id'])
            ->orderBy('id')
            ->get()
            ->each(function ($tenant): void {
                if (!empty($tenant->corp_id)) {
                    return;
                }

                $base = strtoupper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', (string) $tenant->company_name) ?: 'TENANT', 0, 8));
                $corpId = $base . '-' . str_pad((string) $tenant->id, 4, '0', STR_PAD_LEFT);

                DB::table('tenants')->where('id', $tenant->id)->update(['corp_id' => $corpId]);
            });

        DB::table('users')
            ->select(['id', 'name', 'email', 'username'])
            ->orderBy('id')
            ->get()
            ->each(function ($user): void {
                if (!empty($user->username)) {
                    return;
                }

                $seed = strtolower((string) Str::before((string) $user->email, '@'));
                if ($seed === '' || $seed === (string) $user->email) {
                    $seed = strtolower(preg_replace('/[^A-Za-z0-9]/', '', (string) $user->name) ?: 'user');
                }
                $username = Str::limit($seed, 40, '') . '_' . $user->id;

                DB::table('users')->where('id', $user->id)->update(['username' => $username]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username')) {
                $table->dropUnique(['username']);
                $table->dropColumn('username');
            }
        });

        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'corp_id')) {
                $table->dropUnique(['corp_id']);
                $table->dropColumn('corp_id');
            }
        });
    }
};
