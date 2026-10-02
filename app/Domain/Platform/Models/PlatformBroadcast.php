<?php

namespace App\Domain\Platform\Models;

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformBroadcast extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by_user_id',
        'tenant_id',
        'title_en',
        'title_ar',
        'message_en',
        'message_ar',
        'status',
        'recipients_count',
        'sent_at',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'immutable_datetime',
            'recipients_count' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class)->withTrashed();
    }
}
