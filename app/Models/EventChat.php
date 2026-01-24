<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventChat extends Model
{
    protected $table = 'events_chats';

    public const DIRECTION_IN = 'incoming';
    public const DIRECTION_OUT = 'outgoing';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_READ = 'read';

    protected $fillable = [
        'event_id',
        'provider',
        'direction',
        'author_name',
        'company_name',
        'content',
        'status',
        'external_message_id',
        'external_chat_id',
        'sync_error',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function scopeByExternalMessage($query, string $provider, string $externalMessageId)
    {
        return $query->where('provider', $provider)
            ->where('external_message_id', $externalMessageId);
    }

    public function scopeByExternalChat($query, string $provider, string $externalChatId)
    {
        return $query->where('provider', $provider)
            ->where('external_chat_id', $externalChatId);
    }
}
