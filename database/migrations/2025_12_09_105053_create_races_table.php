<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('races', function (Blueprint $table) {
            $table->id();

            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->string('name');
            $table->integer('round_number');

             
            $table->dateTime('race_date');
            $table->dateTime('qualy_date');

            $table->boolean('is_result_confirmed')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('races');
    }
};
