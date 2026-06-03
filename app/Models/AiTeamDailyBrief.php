<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiTeamDailyBrief extends Model
{
    protected $fillable = [
        'ai_team_member_id', 'brief_date', 'brief_content',
        'knowledge_ids', 'action_items', 'metrics_snapshot', 'status',
    ];

    protected $casts = [
        'brief_date' => 'date',
        'knowledge_ids' => 'array',
        'action_items' => 'array',
        'metrics_snapshot' => 'array',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(AiTeamMember::class, 'ai_team_member_id');
    }
}
