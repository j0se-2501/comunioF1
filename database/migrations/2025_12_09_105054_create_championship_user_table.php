<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('championship_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('championship_id')->constrained('championships')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->boolean('is_banned')->default(false);
            $table->integer('total_points')->default(0);
            $table->integer('position')->nullable();

            $table->timestamps();

            $table->unique(['championship_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('championship_user');
    }
};
