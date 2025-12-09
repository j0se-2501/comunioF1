<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('race_id')->constrained('races')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('championship_id')->constrained('championships')->cascadeOnDelete();

            $table->foreignId('position_1')->nullable()->constrained('drivers');
            $table->foreignId('position_2')->nullable()->constrained('drivers');
            $table->foreignId('position_3')->nullable()->constrained('drivers');
            $table->foreignId('position_4')->nullable()->constrained('drivers');
            $table->foreignId('position_5')->nullable()->constrained('drivers');
            $table->foreignId('position_6')->nullable()->constrained('drivers');

            $table->foreignId('pole')->nullable()->constrained('drivers');
            $table->foreignId('fastest_lap')->nullable()->constrained('drivers');
            $table->foreignId('last_place')->nullable()->constrained('drivers');

            $table->timestamps();

            $table->unique(['race_id', 'championship_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
