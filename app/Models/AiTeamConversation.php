<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiTeamConversation extends Model
{
    protected $fillable = [
        'ai_team_member_id', 'user_id', 'topic', 'question', 'response',
        'decision_outcome', 'feedback', 'rating', 'tags',
    ];

    protected $casts = [
        'tags' => 'array',
        'rating' => 'integer',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(AiTeamMember::class, 'ai_team_member_id');
    }
}
