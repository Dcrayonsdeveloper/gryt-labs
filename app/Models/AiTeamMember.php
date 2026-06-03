<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiTeamMember extends Model
{
    protected $fillable = [
        'slug', 'name', 'role', 'department', 'system_prompt', 'avatar_url',
        'expertise_tags', 'connected_to', 'kpi_definitions', 'reports_to_slug',
        'performance_score', 'total_conversations', 'total_decisions_made',
        'decisions_accepted', 'is_active', 'last_trained_at',
    ];

    protected $casts = [
        'expertise_tags' => 'array',
        'connected_to' => 'array',
        'kpi_definitions' => 'array',
        'is_active' => 'boolean',
        'last_trained_at' => 'datetime',
    ];

    public function knowledge(): HasMany
    {
        return $this->hasMany(AiTeamKnowledge::class);
    }

    public function dailyBriefs(): HasMany
    {
        return $this->hasMany(AiTeamDailyBrief::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(AiTeamConversation::class);
    }

    public function reportsTo(): ?AiTeamMember
    {
        if (!$this->reports_to_slug) return null;
        return static::where('slug', $this->reports_to_slug)->first();
    }

    public function directReports()
    {
        return static::where('reports_to_slug', $this->slug)->get();
    }

    public function buildPrompt(?string $additionalContext = null): string
    {
        $prompt = $this->system_prompt;

        $todayBrief = $this->dailyBriefs()
            ->where('brief_date', now()->toDateString())
            ->first();

        if ($todayBrief) {
            $prompt .= "\n\n## Today's Intelligence Brief (" . now()->toDateString() . ")\n\n";
            $prompt .= $todayBrief->brief_content;
        }

        $criticalKnowledge = $this->knowledge()
            ->where('priority', 'critical')
            ->where('knowledge_date', '>=', now()->subDays(7))
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('knowledge_date')
            ->limit(10)
            ->get();

        if ($criticalKnowledge->isNotEmpty()) {
            $prompt .= "\n\n## Critical Updates (Last 7 Days)\n\n";
            foreach ($criticalKnowledge as $k) {
                $prompt .= "- **{$k->title}** ({$k->knowledge_date->format('M d')}): {$k->content}\n";
            }
        }

        if ($additionalContext) {
            $prompt .= "\n\n## Additional Context\n\n" . $additionalContext;
        }

        return $prompt;
    }

    public function recordConversation(string $topic, string $question, string $response, ?int $userId = null): AiTeamConversation
    {
        $this->increment('total_conversations');

        return $this->conversations()->create([
            'user_id' => $userId,
            'topic' => $topic,
            'question' => $question,
            'response' => $response,
        ]);
    }

    public function getAcceptanceRate(): float
    {
        if ($this->total_decisions_made === 0) return 0;
        return round(($this->decisions_accepted / $this->total_decisions_made) * 100, 1);
    }

    public static function findBestFor(string $question): ?self
    {
        $keywords = array_map('trim', explode(' ', strtolower($question)));

        return static::where('is_active', true)
            ->get()
            ->sortByDesc(function ($member) use ($keywords) {
                $tags = array_map('strtolower', $member->expertise_tags ?? []);
                return count(array_intersect($keywords, $tags));
            })
            ->first();
    }
}
