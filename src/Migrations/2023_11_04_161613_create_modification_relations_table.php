<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModificationRelationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('modification_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modification_id')->constrained()->noActionOnDelete();
            $table->string('model');
            $table->string('foreign_id_column');
            $table->json('condition_columns')->nullable();
            $table->json('modifications');
            $table->string('action');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('modification_relations');
    }
}
