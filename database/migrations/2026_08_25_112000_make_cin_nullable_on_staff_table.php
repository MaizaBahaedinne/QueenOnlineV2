<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE staff MODIFY cin VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE staff SET cin = CONCAT('TEMP', id) WHERE cin IS NULL");
        DB::statement('ALTER TABLE staff MODIFY cin VARCHAR(255) NOT NULL');
    }
};