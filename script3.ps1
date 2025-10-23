# Step 3: Generate Remaining Models
Write-Host "Creating Remaining STU Key Management Models..." -ForegroundColor Green

# Create necessary directories if they don't exist
New-Item -ItemType Directory -Force -Path "./app/Models" | Out-Null
New-Item -ItemType Directory -Force -Path "./app/Traits" | Out-Null

# 9. Create KeyLog model
@'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class KeyLog extends Model
{
    protected $fillable = [
        'key_id',
        'action',
        'holder_type',
        'holder_id',
        'location_id',
        'notes',
        'performed_by'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function key(): BelongsTo
    {
        return $this->belongsTo(Key::class);
    }

    public function holder(): MorphTo
    {
        return $this->morphTo();
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
'@ | Out-File -FilePath './app/Models/KeyLog.php' -Encoding utf8NoBOM -Force

# 10. Create Notification model
@'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    protected $fillable = [
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at'
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
'@ | Out-File -FilePath './app/Models/Notification.php' -Encoding utf8NoBOM -Force

# 11. Create AnalyticsCache model
@'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsCache extends Model
{
    protected $fillable = [
        'key',
        'value',
        'expires_at'
    ];

    protected $casts = [
        'value' => 'array',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
'@ | Out-File -FilePath './app/Models/AnalyticsCache.php' -Encoding utf8NoBOM -Force

# 12. Create Setting model
@'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group'
    ];

    protected $casts = [
        'value' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
'@ | Out-File -FilePath './app/Models/Setting.php' -Encoding utf8NoBOM -Force

# 13. Create Holder Trait for polymorphic relationships
@'
<?php

namespace App\Traits;

use App\Models\KeyLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HolderTrait
{
    public function keyLogs(): MorphMany
    {
        return $this->morphMany(KeyLog::class, 'holder');
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}
'@ | Out-File -FilePath './app/Traits/HolderTrait.php' -Encoding utf8NoBOM -Force

# 14. Update HrStaff model to use HolderTrait
@'
<?php

namespace App\Models;

use App\Traits\HolderTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrStaff extends Model
{
    use HolderTrait;

    protected $fillable = [
        'staff_id',
        'name',
        'email',
        'phone',
        'department',
        'position',
        'status'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
'@ | Out-File -FilePath './app/Models/HrStaff.php' -Encoding utf8NoBOM -Force

# 15. Update PermanentStaffManual model to use HolderTrait
@'
<?php

namespace App\Models;

use App\Traits\HolderTrait;
use Illuminate\Database\Eloquent\Model;

class PermanentStaffManual extends Model
{
    use HolderTrait;

    protected $fillable = [
        'name',
        'identification_number',
        'phone',
        'email',
        'department',
        'position',
        'status'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
'@ | Out-File -FilePath './app/Models/PermanentStaffManual.php' -Encoding utf8NoBOM -Force

# 16. Update TemporaryStaff model to use HolderTrait
@'
<?php

namespace App\Models;

use App\Traits\HolderTrait;
use Illuminate\Database\Eloquent\Model;

class TemporaryStaff extends Model
{
    use HolderTrait;

    protected $fillable = [
        'name',
        'identification_number',
        'phone',
        'company',
        'purpose',
        'start_date',
        'end_date',
        'status'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
'@ | Out-File -FilePath './app/Models/TemporaryStaff.php' -Encoding utf8NoBOM -Force

Write-Host "✅ All 15 models created successfully!" -ForegroundColor Green
Write-Host "📁 Files created in app/Models/ and app/Traits/" -ForegroundColor Cyan
Write-Host "➡️ Models include: KeyLog, Notification, AnalyticsCache, Setting, and HolderTrait" -ForegroundColor Yellow
