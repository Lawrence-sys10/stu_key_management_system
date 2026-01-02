<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('key_logs', function (Blueprint $table) {
            // Add new fields for tracking who returned the key
            $table->string('returned_by_type')->nullable()->after('returned_from_log_id');
            $table->unsignedBigInteger('returned_by_id')->nullable()->after('returned_by_type');
            $table->string('returned_by_name')->nullable()->after('returned_by_id');
            $table->string('returned_by_phone')->nullable()->after('returned_by_name');
            $table->timestamp('actual_return_at')->nullable()->after('returned_by_phone');
            
            // Add index for better performance
            $table->index(['returned_by_type', 'returned_by_id']);
            $table->index('actual_return_at');
        });
    }

    public function down()
    {
        Schema::table('key_logs', function (Blueprint $table) {
            $table->dropColumn([
                'returned_by_type',
                'returned_by_id',
                'returned_by_name',
                'returned_by_phone',
                'actual_return_at'
            ]);
        });
    }
};