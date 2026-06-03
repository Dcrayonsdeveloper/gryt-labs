<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiTeamKnowledge extends Model
{
    protected $table = 'ai_team_knowledge';

    protected $fillable = [
        'ai_team_member_id', 'category', 'title', 'content', 'source', 'source_url',
        'knowledge_date', 'expires_at', 'priority', 'region', 'tags',
        'is_verified', 'relevance_score',
    ];

    protected $casts = [
        'tags' => 'array',
        'knowledge_date' => 'date',
        'expires_at' => 'date',
        'is_verified' => 'boolean',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(AiTeamMember::class, 'ai_team_member_id');
    }
}
