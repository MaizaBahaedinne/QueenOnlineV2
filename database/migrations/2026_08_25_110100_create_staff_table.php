<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('photo_path')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('cin')->nullable()->unique();
            $table->date('hire_date')->nullable();
            $table->string('position_title');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->enum('employment_type', ['permanent', 'part-time'])->default('permanent');
            $table->enum('contract_type', ['CDI', 'CDD', 'Freelance'])->default('CDI');
            $table->foreignId('manager_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['department_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
