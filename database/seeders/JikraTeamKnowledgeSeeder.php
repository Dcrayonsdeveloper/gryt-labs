<?php

namespace Database\Seeders;

use App\Models\AiTeamKnowledge;
use App\Models\AiTeamMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Seeds post-founding knowledge entries into ai_team_knowledge.
 *
 * Run AFTER the tenant migration (2026_04_21_000001_create_ai_team_tables.php)
 * and the JikraTeamSeeder (which creates the 17 members). Idempotent: re-runs
 * refresh content from the markdown source files without duplicating rows.
 *
 * Entries seeded:
 *   1. The 2026-04-22 P0 incident PIR  — all 17 members "know" about it
 *   2. The "no in-place prod edits" rule — Karan + Aditya
 *   3. The "no self-signup" Dcrayons scope decision — Diksha + Shivam*
 *   4. The "use specialist team members" feedback rule — Diksha
 *
 * Shivam sits in the Dcrayons-side team, not the Jikra table, so for rule #3
 * we attach the entry to Diksha and reference Shivam inside the content body.
 *
 * Schema mapping (task spec → actual columns):
 *   member_id   → ai_team_member_id
 *   topic       → title
 *   source_path → source (we prefix with the repo root so it's unambiguous)
 *   created_at  → knowledge_date + timestamps (auto)
 */
class JikraTeamKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        $basePath = base_path();
        $today = now()->toDateString();

        $entries = [
            [
                'title' => 'P0 Incident — Distorted CSS 2026-04-22 (all tenants)',
                'source_path' => '.ai-memory/project_p0_incident_2026_04_22.md',
                'category' => 'incident',
                'priority' => 'critical',
                'assign_to' => 'all',
                'fallback_summary' => 'P0 on 2026-04-22: parallel Claude session pushed direct to main, SCP upload missed 4 CSS files, manifest referenced non-existent hashes, all 4 tenants served unstyled HTML for 27 min. Fix: emergency rebuild on prod. Permanent fix: PR #9 (deploy.sh checksum verify) + CSS-link smoke test + server-side branch protection. No data lost, no orders affected.',
            ],
            [
                'title' => 'Rule — No In-Place Production File Edits',
                'source_path' => '.ai-memory/feedback_no_inplace_prod_edits.md',
                'category' => 'policy',
                'priority' => 'critical',
                'assign_to' => ['karan_jikra', 'aditya_jikra'],
                'fallback_summary' => 'Mandatory rule after 2026-04-22 P0: no file under /var/www/jikra may be edited in place. Every change goes through feature branch → PR → Diksha approval → bash scripts/deploy.sh. Allowed operationally: cache clear, fpm restart, read-only SQL, single-record DB fixes (logged). Emergency recovery edits during a live P0 allowed for Karan with a mandatory fix-forward PR within 24h and post-mortem within 48h.',
            ],
            [
                'title' => 'Dcrayons Scope — No Self-Signup',
                'source_path' => '.ai-memory/project_dcrayons_scope.md',
                'category' => 'policy',
                'priority' => 'critical',
                'assign_to' => ['diksha'], // Shivam is in Dcrayons-side team; content references him
                'fallback_summary' => 'Ratified cross-account decision (Rahul + Shivam, 2026-04-21): no public self-signup for Dcrayons tenants. Only sales-led, human-vetted onboarding. Marketing copy says "request a demo" / "book a call", never "sign up". Lead-capture form on dcrayons.app is NOT signup. Rationale: tenant quality, multi-tenant safety, brand positioning, compliance (GST/FSSAI/carrier KYC).',
            ],
            [
                'title' => 'Rule — Use the Specialist Team Members',
                'source_path' => '.ai-memory/team/README.md',
                'category' => 'policy',
                'priority' => 'high',
                'assign_to' => ['diksha'],
                'fallback_summary' => 'When Rahul or an AI session asks for work, Diksha must route it to the correct specialist rather than executing it herself or letting a generalist session handle it. Each of the 17 members has a narrow specialisation documented in .ai-memory/team/*.md. Invoking the right specialist is the whole reason the team exists — a generic answer from Diksha is a failure mode. Diksha decides, delegates, approves, and protects; she does not execute.',
            ],
        ];

        $rowsTouched = 0;

        foreach ($entries as $entry) {
            $content = $this->loadContent($basePath, $entry['source_path'], $entry['fallback_summary']);
            $memberIds = $this->resolveMembers($entry['assign_to']);

            if (empty($memberIds)) {
                $this->command?->warn("  Skipping '{$entry['title']}' — no matching team members found (has JikraTeamSeeder run?).");
                continue;
            }

            foreach ($memberIds as $memberId) {
                AiTeamKnowledge::updateOrCreate(
                    [
                        'ai_team_member_id' => $memberId,
                        'title' => $entry['title'],
                    ],
                    [
                        'category' => $entry['category'],
                        'content' => $content,
                        'source' => $entry['source_path'],
                        'source_url' => null,
                        'knowledge_date' => $today,
                        'priority' => $entry['priority'],
                        'region' => 'india',
                        'tags' => $this->buildTags($entry),
                        'is_verified' => true,
                        'relevance_score' => $this->scoreFor($entry['priority']),
                    ]
                );
                $rowsTouched++;
            }

            $count = count($memberIds);
            $this->command?->info("  Seeded '{$entry['title']}' → {$count} member(s).");
        }

        $this->command?->info("JikraTeamKnowledgeSeeder complete — {$rowsTouched} ai_team_knowledge row(s) created or refreshed.");
    }

    /**
     * Load markdown body from the source file. Falls back to the summary
     * string if the file is missing (so the seeder is resilient).
     */
    private function loadContent(string $basePath, string $relativePath, string $fallback): string
    {
        $absolute = $basePath . DIRECTORY_SEPARATOR . $relativePath;

        if (File::exists($absolute)) {
            return File::get($absolute);
        }

        return "[source file {$relativePath} not found — using seeded summary]\n\n" . $fallback;
    }

    /**
     * Expand an assignment into concrete ai_team_members.id values.
     * - 'all'                  → every active member
     * - ['slug1','slug2',...]  → the matching members (silently skips unknown slugs)
     */
    private function resolveMembers(string|array $assignTo): array
    {
        if ($assignTo === 'all') {
            return AiTeamMember::where('is_active', true)->pluck('id')->all();
        }

        return AiTeamMember::whereIn('slug', (array) $assignTo)
            ->where('is_active', true)
            ->pluck('id')
            ->all();
    }

    private function scoreFor(string $priority): int
    {
        return match ($priority) {
            'critical' => 95,
            'high' => 80,
            'medium' => 60,
            default => 50,
        };
    }

    /**
     * Build the tags array that will be JSON-encoded into the tags column.
     */
    private function buildTags(array $entry): array
    {
        $tags = [$entry['category'], $entry['priority']];

        if ($entry['assign_to'] === 'all') {
            $tags[] = 'team-wide';
        }

        return array_values(array_unique($tags));
    }
}
