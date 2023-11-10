<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('modifications', function (Blueprint $table) {
            $table->id();
            // TODO:: use morph('modifiable')
            $table->integer('modifiable_id')->nullable();
            $table->string('modifiable_type')->nullable();
            // TODO:: use morph('modifier')
            $table->integer('modifier_id')->nullable();
            $table->string('modifier_type')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('is_update')->default(true);
            $table->integer('approvers_required')->default(1);
            $table->integer('disapprovers_required')->default(1);
            $table->string('md5');
            $table->json('modifications');
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
        Schema::dropIfExists('modifications');
    }
};
