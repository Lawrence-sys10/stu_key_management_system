<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('temporary_staff', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('id_number')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('dept')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['name', 'phone']);
            $table->index('id_number');
        });
    }

    public function down()
    {
        Schema::dropIfExists('temporary_staff');
    }
};
