<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('championship_standings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('championship_id')->constrained('championships')->cascadeOnDelete();
            $table->foreignId('race_id')->constrained('races')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->integer('total_points');
            $table->integer('position');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('championship_standings');
    }
};
