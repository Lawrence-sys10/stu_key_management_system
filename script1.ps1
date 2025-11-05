# Step 1: Generate All Migrations
Write-Host "Creating STU Key Management Database Migrations..." -ForegroundColor Green

# 1. Create users table migration (extended for Spatie roles)
@'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['email', 'deleted_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
'@ | Out-File -FilePath .\database\migrations\0000_00_00_000000_create_users_table.php -Encoding UTF8

# 2. Create permissions tables (Spatie)
@'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        }

        Schema::create($tableNames['permissions'], function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            
            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['roles'], function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            
            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames) {
            $table->unsignedBigInteger('permission_id');

            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign('permission_id')
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->primary(['permission_id', $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
        });

        Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $columnNames) {
            $table->unsignedBigInteger('role_id');

            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign('role_id')
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            $table->primary(['role_id', $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
        });

        Schema::create($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');

            $table->foreign('permission_id')
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            $table->primary(['permission_id', 'role_id'], 'role_has_permissions_permission_id_role_id_primary');
        });

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down()
    {
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not found and could not be dropped.');
        }

        Schema::drop($tableNames['role_has_permissions']);
        Schema::drop($tableNames['model_has_roles']);
        Schema::drop($tableNames['model_has_permissions']);
        Schema::drop($tableNames['roles']);
        Schema::drop($tableNames['permissions']);
    }
};
'@ | Out-File -FilePath .\database\migrations\0000_00_00_000001_create_permission_tables.php -Encoding UTF8

# 3. Create security_shifts table
@'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('security_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('start_at');
            $table->timestamp('end_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['start_at', 'end_at']);
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('security_shifts');
    }
};
'@ | Out-File -FilePath .\database\migrations\0000_00_00_000002_create_security_shifts_table.php -Encoding UTF8

# 4. Create locations table
@'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('campus');
            $table->string('building');
            $table->string('room')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['campus', 'building']);
            $table->index('is_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('locations');
    }
};
'@ | Out-File -FilePath .\database\migrations\0000_00_00_000003_create_locations_table.php -Encoding UTF8

# 5. Create keys table
@'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('keys', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->enum('key_type', ['physical', 'electronic', 'master', 'duplicate'])->default('physical');
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['available', 'checked_out', 'lost', 'maintenance'])->default('available');
            $table->foreignId('last_log_id')->nullable()->constrained('key_logs')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['code', 'status']);
            $table->index('location_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('keys');
    }
};
'@ | Out-File -FilePath .\database\migrations\0000_00_00_000004_create_keys_table.php -Encoding UTF8

# 6. Create key_tags table
@'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('key_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('key_id')->constrained()->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->timestamp('printed_at')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['uuid', 'is_active']);
            $table->index('key_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('key_tags');
    }
};
'@ | Out-File -FilePath .\database\migrations\0000_00_00_000005_create_key_tags_table.php -Encoding UTF8

# 7. Create hr_staff table
@'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('hr_staff', function (Blueprint $table) {
            $table->id();
            $table->string('staff_id')->unique();
            $table->string('name');
            $table->string('phone');
            $table->string('dept')->nullable();
            $table->string('email')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('synced_at')->nullable();
            $table->enum('source', ['csv', 'api'])->default('csv');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['staff_id', 'status']);
            $table->index('phone');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('hr_staff');
    }
};
'@ | Out-File -FilePath .\database\migrations\0000_00_00_000006_create_hr_staff_table.php -Encoding UTF8

# 8. Create permanent_staff_manual table
@'
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
            $table->foreignId('added_by')->constrained('users')->onDelete('cascade');
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
'@ | Out-File -FilePath .\database\migrations\0000_00_00_000007_create_permanent_staff_manual_table.php -Encoding UTF8

# 9. Create temporary_staff table
@'
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
'@ | Out-File -FilePath .\database\migrations\0000_00_00_000008_create_temporary_staff_table.php -Encoding UTF8

# 10. Create key_logs table
@'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('key_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('key_id')->constrained()->onDelete('cascade');
            $table->enum('action', ['checkout', 'checkin']);
            
            // Polymorphic holder relationship
            $table->string('holder_type'); // hr, perm_manual, temp
            $table->unsignedBigInteger('holder_id');
            $table->string('holder_name');
            $table->string('holder_phone');
            
            // Security officer who processed
            $table->foreignId('receiver_user_id')->constrained('users')->onDelete('cascade');
            $table->string('receiver_name'); // Snapshot
            
            $table->timestamp('expected_return_at')->nullable();
            $table->foreignId('returned_from_log_id')->nullable()->constrained('key_logs')->onDelete('set null');
            
            // Evidence
            $table->string('signature_path')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            
            // Verification flags
            $table->boolean('verified')->default(false);
            $table->boolean('discrepancy')->default(false);
            $table->text('discrepancy_reason')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['key_id', 'created_at']);
            $table->index(['holder_type', 'holder_id']);
            $table->index(['action', 'created_at']);
            $table->index('receiver_user_id');
            $table->index('verified');
            $table->index('discrepancy');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('key_logs');
    }
};
'@ | Out-File -FilePath .\database\migrations\0000_00_00_000009_create_key_logs_table.php -Encoding UTF8

# 11. Create notifications table
@'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('key_log_id')->constrained()->onDelete('cascade');
            $table->enum('channel', ['sms', 'whatsapp', 'email']);
            $table->string('to');
            $table->string('template_key');
            $table->json('payload_json');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            
            $table->index(['channel', 'status']);
            $table->index('key_log_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};
'@ | Out-File -FilePath .\database\migrations\0000_00_00_000010_create_notifications_table.php -Encoding UTF8

# 12. Create analytics_cache table
@'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('analytics_cache', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('hour_bucket'); // 0-23
            $table->integer('total_checkouts')->default(0);
            $table->integer('avg_duration_minutes')->default(0);
            $table->boolean('busiest_flag')->default(false);
            $table->timestamps();
            
            $table->unique(['date', 'hour_bucket']);
            $table->index(['date', 'busiest_flag']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('analytics_cache');
    }
};
'@ | Out-File -FilePath .\database\migrations\0000_00_00_000011_create_analytics_cache_table.php -Encoding UTF8

# 13. Create settings table
@'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->string('group')->default('general');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['group', 'key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('settings');
    }
};
'@ | Out-File -FilePath .\database\migrations\0000_00_00_000012_create_settings_table.php -Encoding UTF8

# 14. Create failed_jobs table
@'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('failed_jobs');
    }
};
'@ | Out-File -FilePath .\database\migrations\0000_00_00_000013_create_failed_jobs_table.php -Encoding UTF8

# 15. Create jobs table
@'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('jobs');
    }
};
'@ | Out-File -FilePath .\database\migrations\0000_00_00_000014_create_jobs_table.php -Encoding UTF8

Write-Host "✅ All 15 migrations created successfully!" -ForegroundColor Green
Write-Host "📁 Files created in database/migrations/" -ForegroundColor Cyan
Write-Host "➡️ Next: Run 'php artisan migrate' to create tables" -ForegroundColor Yellow