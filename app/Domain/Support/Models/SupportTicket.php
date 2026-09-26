<?php

namespace App\Domain\Support\Models;

use App\Domain\Support\Enums\SupportTicketPriority;
use App\Domain\Support\Enums\SupportTicketStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int|null $requester_user_id
 * @property SupportTicketStatus $status
 * @property SupportTicketPriority $priority
 * @property-read Tenant $tenant
 * @property-read User|null $requester
 */
class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'requester_user_id',
        'subject',
        'message',
        'status',
        'priority',
        'admin_notes',
        'last_replied_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SupportTicketStatus::class,
            'priority' => SupportTicketPriority::class,
            'last_replied_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }
}
