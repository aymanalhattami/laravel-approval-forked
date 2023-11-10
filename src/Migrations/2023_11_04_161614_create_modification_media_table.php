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
        Schema::create('modification_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->nullable()->constrained()->noActionOnDelete();
            $table->morphs('model');
            $table->string('action')->default(\Approval\Enums\MediaActionEnum::Create->value);
            $table->json('condition_columns')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modification_media');
    }
};
