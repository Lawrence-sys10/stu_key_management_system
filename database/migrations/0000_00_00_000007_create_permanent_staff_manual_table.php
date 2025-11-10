<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('permanent_staff_manual', function (Blueprint $table) {
            $table->id();
            $table->string('staff_id')->nullable();
            $table->string('name');
            $table->string('phone');
            $table->string('dept')->nullable();
            $table->unsignedBigInteger('added_by');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['name', 'phone']);
            $table->index('added_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('permanent_staff_manual');
    }
};

