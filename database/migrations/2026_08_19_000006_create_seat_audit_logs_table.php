<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seat_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seat_id')->constrained();
            $table->string('old_tier');
            $table->string('new_tier');
            $table->foreignId('admin_id')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seat_audit_logs');
    }
};
