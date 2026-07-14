<?php

namespace App\Console\Commands;

use App\Helpers\DbCompat;
use App\Models\AiTeamKnowledge;
use App\Models\AiTeamMember;
use Illuminate\Console\Command;

/**
 * Lookup command for the ai_team_knowledge table.
 *
 * Examples:
 *   php artisan ai-team:knowledge mira cohort
 *   php artisan ai-team:knowledge karan deploy
 *   php artisan ai-team:knowledge all p0
 *   php artisan ai-team:knowledge diksha signup
 *
 * First arg is a team member slug (e.g., "mira_jikra", "karan_jikra") OR the
 * literal "all" to search across every member's knowledge entries. Short slugs
 * without the "_jikra" suffix also work (e.g. "mira", "karan").
 *
 * Second arg is a free-text keyword; matches case-insensitively against both
 * title and content using SQL ILIKE (PostgreSQL).
 */
class AiTeamKnowledgeQuery extends Command
{
    protected $signature = 'ai-team:knowledge
                            {member : Team member slug (e.g. mira, karan, diksha) or "all"}
                            {keyword : Case-insensitive keyword to search topic + content}
                            {--limit=20 : Max number of entries to show}';

    protected $description = 'Search the AI team knowledge table by member and keyword (ILIKE on title + content).';

    public function handle(): int
    {
        $memberArg = (string) $this->argument('member');
        $keyword = (string) $this->argument('keyword');
        $limit = (int) $this->option('limit');

        if ($keyword === '') {
            $this->error('Keyword is required.');
            return self::INVALID;
        }

        $query = AiTeamKnowledge::query()
            ->with('member:id,slug,name,role');

        $memberLabel = 'all members';

        if (strtolower($memberArg) !== 'all') {
            $member = $this->resolveMember($memberArg);

            if (!$member) {
                $this->error("No active team member matches: {$memberArg}");
                $this->line('Try: php artisan ai-team:knowledge all ' . $keyword);
                $this->line('Or list members: php artisan tinker → App\\Models\\AiTeamMember::pluck(\'slug\', \'name\');');
                return self::FAILURE;
            }

            $query->where('ai_team_member_id', $member->id);
            $memberLabel = "{$member->name} ({$member->slug})";
        }

        $like = '%' . $keyword . '%';
        $op   = DbCompat::ilike();
        $query->where(function ($q) use ($like, $op) {
            $q->where('title', $op, $like)
              ->orWhere('content', $op, $like);
        });

        $results = $query->orderByDesc('knowledge_date')
            ->orderByDesc('relevance_score')
            ->limit($limit)
            ->get();

        if ($results->isEmpty()) {
            $this->warn("No matches for \"{$keyword}\" in {$memberLabel}.");
            $this->line('Widen your search with: php artisan ai-team:knowledge all ' . $keyword);
            return self::SUCCESS;
        }

        $this->info("Found {$results->count()} result(s) for \"{$keyword}\" in {$memberLabel}:");
        $this->newLine();

        foreach ($results as $k) {
            $owner = $k->member
                ? "{$k->member->name} ({$k->member->slug})"
                : 'unassigned / team-wide';

            $date = $k->knowledge_date
                ? $k->knowledge_date->format('Y-m-d')
                : '—';

            $preview = $this->preview($k->content, 200);
            $sourceLink = $k->source ? "file:///" . ltrim(str_replace('\\', '/', base_path($k->source)), '/') : '(no source file)';

            $this->line("<fg=cyan>■ {$k->title}</>");
            $this->line("   owner:     {$owner}");
            $this->line("   category:  {$k->category}  |  priority: {$k->priority}  |  date: {$date}");
            $this->line("   preview:   {$preview}");
            $this->line("   source:    {$sourceLink}");
            $this->newLine();
        }

        return self::SUCCESS;
    }

    /**
     * Resolve a member argument against the ai_team_members table.
     * Tries exact slug first, then "<arg>_jikra", then name ILIKE.
     */
    private function resolveMember(string $arg): ?AiTeamMember
    {
        $arg = trim($arg);

        $candidates = [
            $arg,
            strtolower($arg),
            strtolower($arg) . '_jikra',
        ];

        foreach ($candidates as $candidate) {
            $member = AiTeamMember::where('is_active', true)
                ->where('slug', $candidate)
                ->first();

            if ($member) {
                return $member;
            }
        }

        return AiTeamMember::where('is_active', true)
            ->where('name', DbCompat::ilike(), $arg)
            ->first();
    }

    /**
     * Return the first $limit characters of $content, collapsed to one line,
     * with a trailing ellipsis if the original was longer.
     */
    private function preview(?string $content, int $limit): string
    {
        if ($content === null || $content === '') {
            return '(empty)';
        }

        $flat = preg_replace('/\s+/', ' ', trim($content));
        if (mb_strlen($flat) <= $limit) {
            return $flat;
        }

        return mb_substr($flat, 0, $limit) . '…';
    }
}
