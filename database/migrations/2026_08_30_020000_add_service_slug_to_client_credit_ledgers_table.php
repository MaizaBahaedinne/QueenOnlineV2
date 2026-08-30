<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_credit_ledgers')) {
            return;
        }

        Schema::table('client_credit_ledgers', function (Blueprint $table) {
            if (! Schema::hasColumn('client_credit_ledgers', 'service_slug')) {
                $table->string('service_slug', 50)->nullable()->after('user_id');
                $table->index(['client_id', 'service_slug'], 'client_credit_ledgers_client_service_idx');
            }
        });

        if (Schema::hasTable('reservations') && Schema::hasColumn('reservations', 'service_slug')) {
            DB::statement('UPDATE client_credit_ledgers ccl INNER JOIN reservations r ON r.id = ccl.reservation_id SET ccl.service_slug = COALESCE(NULLIF(r.service_slug, ""), "salles") WHERE ccl.service_slug IS NULL');
        } elseif (Schema::hasTable('reservations')) {
            DB::table('client_credit_ledgers')->whereNotNull('reservation_id')->whereNull('service_slug')->update(['service_slug' => 'salles']);
        }

        DB::table('client_credit_ledgers')->whereNull('service_slug')->update(['service_slug' => 'salles']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('client_credit_ledgers')) {
            return;
        }

        Schema::table('client_credit_ledgers', function (Blueprint $table) {
            if (Schema::hasColumn('client_credit_ledgers', 'service_slug')) {
                $table->dropIndex('client_credit_ledgers_client_service_idx');
                $table->dropColumn('service_slug');
            }
        });
    }
};