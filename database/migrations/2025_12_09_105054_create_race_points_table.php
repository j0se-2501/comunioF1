<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('race_points', function (Blueprint $table) {
            $table->id();

            $table->foreignId('prediction_id')->constrained('predictions')->cascadeOnDelete();

            $table->foreignId('race_id')->constrained('races')->cascadeOnDelete();
            $table->foreignId('championship_id')->constrained('championships')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->integer('points');

            $table->boolean('guessed_p1')->default(false);
            $table->boolean('guessed_p2')->default(false);
            $table->boolean('guessed_p3')->default(false);
            $table->boolean('guessed_p4')->default(false);
            $table->boolean('guessed_p5')->default(false);
            $table->boolean('guessed_p6')->default(false);

            $table->boolean('guessed_pole')->default(false);
            $table->boolean('guessed_fastest_lap')->default(false);
            $table->boolean('guessed_last_place')->default(false);

            $table->timestamps();

            $table->unique(['race_id', 'championship_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('race_points');
    }
};
