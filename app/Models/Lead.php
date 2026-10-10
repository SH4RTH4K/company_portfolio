<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    use HasFactory;

    public const STATUSES = [
        'New',
        'Contacted',
        'Qualified',
        'Proposal Sent',
        'Negotiation',
        'Won',
        'Lost',
        'Spam',
    ];

    protected $fillable = [
        'full_name',
        'company_name',
        'phone',
        'email',
        'interested_service',
        'budget_range',
        'subject',
        'message',
        'language',
        'lead_source',
        'landing_page',
        'referrer',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'status',
        'reply_subject',
        'reply_message',
        'replied_by',
        'replied_at',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
    ];

    public function repliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }
}
