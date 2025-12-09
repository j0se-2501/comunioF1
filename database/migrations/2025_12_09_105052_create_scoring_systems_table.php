<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scoring_systems', function (Blueprint $table) {
            $table->id();

            $table->foreignId('championship_id')
                ->constrained('championships')
                ->cascadeOnDelete();

            $table->integer('points_p1');
            $table->integer('points_p2');
            $table->integer('points_p3');
            $table->integer('points_p4');
            $table->integer('points_p5');
            $table->integer('points_p6');

            $table->integer('points_pole');
            $table->integer('points_fastest_lap');
            $table->integer('points_last_place');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scoring_systems');
    }
};
