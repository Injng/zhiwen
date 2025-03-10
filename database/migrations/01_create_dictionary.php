<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->string('word');
            $table->string('pinyin');
            $table->timestamps();
        });

        Schema::create('definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained()->onDelete('cascade');
            $table->string('part');
            $table->text('definition');
            $table->timestamps();
        });

        Schema::create('examples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('definition_id')->constrained()->onDelete('cascade');
            $table->text('sentence');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examples');
        Schema::dropIfExists('definitions');
        Schema::dropIfExists('dictionary');
    }
};
