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
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['word', 'cloze'])->default('word');
            $table->date('due');
            $table->float('stability');
            $table->float('difficulty');
            $table->integer('elapsed_days');
            $table->integer('scheduled_days');
            $table->integer('reps');
            $table->integer('lapses');
            $table->enum('state', ['new', 'learning', 'review', 'relearning']);
            $table->date('last_review')->nullable();
        });

        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained()->onDelete('cascade');
            $table->timeTz('review_time', 3);
            $table->integer('review_rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
        Schema::dropIfExists('cards');
    }
};
