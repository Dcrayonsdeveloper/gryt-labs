<?php

namespace Database\Seeders;

use App\Models\AiTeamMember;
use App\Models\AiTeamKnowledge;
use Illuminate\Database\Seeder;

class JikraTeamSeeder extends Seeder
{
    /**
     * Seeds the dedicated 17-member Jikra AI team.
     * System prompts are loaded from .ai-memory/team/*.md at seed time
     * (single source of truth — edit the .md files, re-seed to refresh prompts).
     */
    public function run(): void
    {
        $this->seedTeamMembers();
        $this->seedInitialKnowledge();
    }

    private function seedTeamMembers(): void
    {
        $teamMemoryPath = base_path('.ai-memory/team');

        $members = [
            [
                'slug' => 'diksha',
                'name' => 'Diksha',
                'role' => 'Jikra Account Head / Brand Manager',
                'department' => 'leadership',
                'reports_to_slug' => null,
                'expertise_tags' => ['leadership', 'd2c', 'brand', 'pnl', 'okrs', 'decisions', 'approvals'],
                'connected_to' => ['aarav_ba_jikra', 'aditya_jikra', 'karan_jikra', 'rohit_jikra', 'mira_jikra'],
                'kpi_definitions' => [
                    ['metric' => 'monthly_gmv', 'target' => 'set_quarterly_with_rahul', 'unit' => 'INR'],
                    ['metric' => 'overall_conversion_rate', 'target' => 2.5, 'unit' => '%'],
                    ['metric' => 'blended_roas', 'target' => 3.0, 'unit' => 'x'],
                    ['metric' => 'repeat_purchase_rate_90d', 'target' => 25, 'unit' => '%'],
                    ['metric' => 'pr_turnaround_hours', 'target' => 24, 'unit' => 'hours'],
                ],
                'file' => '00_diksha_account_head.md',
            ],
            [
                'slug' => 'aarav_ba_jikra',
                'name' => 'Aarav',
                'role' => 'Business Analyst (Jikra)',
                'department' => 'operations',
                'reports_to_slug' => 'diksha',
                'expertise_tags' => ['business_analysis', 'task_scoping', 'requirements', 'acceptance_criteria', 'glossary'],
                'connected_to' => ['diksha', 'aditya_jikra', 'karan_jikra', 'mira_jikra'],
                'kpi_definitions' => [
                    ['metric' => 'spec_rejection_rate', 'target' => 10, 'unit' => '%_max'],
                    ['metric' => 'input_to_spec_hours', 'target' => 4, 'unit' => 'hours'],
                    ['metric' => 'tasks_closed_no_rework', 'target' => 80, 'unit' => '%'],
                ],
                'file' => '01_aarav_business_analyst.md',
            ],
            [
                'slug' => 'prerna_jikra',
                'name' => 'Prerna',
                'role' => 'Catalog & Merchandising Specialist',
                'department' => 'ecommerce_ops',
                'reports_to_slug' => 'diksha',
                'expertise_tags' => ['catalog', 'products', 'categories', 'attributes', 'inventory', 'pricing', 'merchandising', 'flash_sale'],
                'connected_to' => ['vihaan_jikra', 'anaya_jikra', 'tanvi_jikra', 'rohit_jikra', 'aditya_jikra'],
                'kpi_definitions' => [
                    ['metric' => 'catalog_completeness', 'target' => 95, 'unit' => '%'],
                    ['metric' => 'avg_images_per_product', 'target' => 4, 'unit' => 'count'],
                    ['metric' => 'pricing_anomaly_detection_hours', 'target' => 24, 'unit' => 'hours'],
                ],
                'file' => '02_prerna_catalog.md',
            ],
            [
                'slug' => 'vihaan_jikra',
                'name' => 'Vihaan',
                'role' => 'Marketplace & Feed Manager',
                'department' => 'ecommerce_ops',
                'reports_to_slug' => 'diksha',
                'expertise_tags' => ['google_merchant', 'meta_catalog', 'feeds', 'marketplace', 'shopping_ads', 'gtin'],
                'connected_to' => ['prerna_jikra', 'rohit_jikra', 'mira_jikra', 'aditya_jikra'],
                'kpi_definitions' => [
                    ['metric' => 'merchant_active_products', 'target' => 700, 'unit' => 'count'],
                    ['metric' => 'disapproval_rate', 'target' => 3, 'unit' => '%_max'],
                    ['metric' => 'gtin_coverage', 'target' => 85, 'unit' => '%'],
                ],
                'file' => '03_vihaan_marketplace.md',
            ],
            [
                'slug' => 'anaya_jikra',
                'name' => 'Anaya',
                'role' => 'Conversion Rate Optimisation Specialist',
                'department' => 'ecommerce_ops',
                'reports_to_slug' => 'diksha',
                'expertise_tags' => ['cro', 'ab_testing', 'funnel', 'checkout', 'cart', 'abandonment', 'pdp', 'experiments'],
                'connected_to' => ['riya_jikra', 'rohit_jikra', 'naina_jikra', 'mira_jikra', 'aryan_jikra'],
                'kpi_definitions' => [
                    ['metric' => 'overall_conversion_rate', 'target' => 2.5, 'unit' => '%'],
                    ['metric' => 'cart_to_checkout_rate', 'target' => 65, 'unit' => '%'],
                    ['metric' => 'cart_recovery_rate', 'target' => 8, 'unit' => '%'],
                ],
                'file' => '04_anaya_cro.md',
            ],
            [
                'slug' => 'rohit_jikra',
                'name' => 'Rohit',
                'role' => 'Performance Marketing Lead',
                'department' => 'marketing',
                'reports_to_slug' => 'diksha',
                'expertise_tags' => ['google_ads', 'meta_ads', 'shopping', 'pmax', 'roas', 'cac', 'paid_media', 'targeting'],
                'connected_to' => ['vihaan_jikra', 'anaya_jikra', 'tanvi_jikra', 'naina_jikra', 'mira_jikra'],
                'kpi_definitions' => [
                    ['metric' => 'blended_roas', 'target' => 3.0, 'unit' => 'x'],
                    ['metric' => 'google_cac_pct_of_aov', 'target' => 30, 'unit' => '%_max'],
                    ['metric' => 'wasted_spend_pct', 'target' => 10, 'unit' => '%_max'],
                ],
                'file' => '05_rohit_performance_marketing.md',
            ],
            [
                'slug' => 'tanvi_jikra',
                'name' => 'Tanvi',
                'role' => 'SEO & Content Lead',
                'department' => 'marketing',
                'reports_to_slug' => 'diksha',
                'expertise_tags' => ['seo', 'content', 'keywords', 'schema', 'gsc', 'blog', 'technical_seo', 'on_page'],
                'connected_to' => ['prerna_jikra', 'aditya_jikra', 'ishaan_jikra', 'mira_jikra'],
                'kpi_definitions' => [
                    ['metric' => 'organic_sessions_mom_growth', 'target' => 10, 'unit' => '%'],
                    ['metric' => 'keywords_top_10_growth', 'target' => 5, 'unit' => 'per_month'],
                    ['metric' => 'gsc_indexed_pct', 'target' => 95, 'unit' => '%'],
                ],
                'file' => '06_tanvi_seo_content.md',
            ],
            [
                'slug' => 'ishaan_jikra',
                'name' => 'Ishaan',
                'role' => 'Social & Influencer Lead',
                'department' => 'marketing',
                'reports_to_slug' => 'diksha',
                'expertise_tags' => ['social_media', 'instagram', 'reels', 'facebook', 'whatsapp', 'influencer', 'ugc', 'community'],
                'connected_to' => ['rohit_jikra', 'naina_jikra', 'tanvi_jikra', 'mira_jikra', 'yash_jikra'],
                'kpi_definitions' => [
                    ['metric' => 'ig_follower_growth_mom', 'target' => 5, 'unit' => '%'],
                    ['metric' => 'reels_per_month', 'target' => 14, 'unit' => 'count'],
                    ['metric' => 'engagement_rate', 'target' => 3, 'unit' => '%'],
                ],
                'file' => '07_ishaan_social_influencer.md',
            ],
            [
                'slug' => 'naina_jikra',
                'name' => 'Naina',
                'role' => 'Email & Retention Lead',
                'department' => 'marketing',
                'reports_to_slug' => 'diksha',
                'expertise_tags' => ['email', 'newsletter', 'whatsapp_marketing', 'loyalty', 'retention', 'win_back', 'cohort', 'segmentation'],
                'connected_to' => ['anaya_jikra', 'ishaan_jikra', 'yash_jikra', 'mira_jikra', 'aryan_jikra'],
                'kpi_definitions' => [
                    ['metric' => 'email_open_rate', 'target' => 28, 'unit' => '%'],
                    ['metric' => 'repeat_purchase_rate_90d', 'target' => 25, 'unit' => '%'],
                    ['metric' => 'unsubscribe_rate', 'target' => 0.3, 'unit' => '%_max'],
                ],
                'file' => '08_naina_email_retention.md',
            ],
            [
                'slug' => 'aditya_jikra',
                'name' => 'Aditya',
                'role' => 'Tech Lead (Laravel 12 + Multi-Tenancy)',
                'department' => 'technology',
                'reports_to_slug' => 'diksha',
                'expertise_tags' => ['laravel', 'php', 'postgresql', 'multi_tenancy', 'architecture', 'security', 'performance', 'eloquent'],
                'connected_to' => ['karan_jikra', 'riya_jikra', 'diya_jikra', 'tanvi_jikra', 'aarav_ba_jikra'],
                'kpi_definitions' => [
                    ['metric' => 'pr_review_turnaround_hours', 'target' => 12, 'unit' => 'hours'],
                    ['metric' => 'p95_response_ms', 'target' => 500, 'unit' => 'ms'],
                    ['metric' => 'tenant_isolation_incidents', 'target' => 0, 'unit' => 'count'],
                ],
                'file' => '09_aditya_tech_lead.md',
            ],
            [
                'slug' => 'riya_jikra',
                'name' => 'Riya',
                'role' => 'Frontend & UX Engineer',
                'department' => 'technology',
                'reports_to_slug' => 'diksha',
                'expertise_tags' => ['frontend', 'tailwind', 'alpine', 'blade', 'mobile', 'accessibility', 'theme_system', 'wcag'],
                'connected_to' => ['anaya_jikra', 'aditya_jikra', 'tanvi_jikra', 'karan_jikra'],
                'kpi_definitions' => [
                    ['metric' => 'lcp_mobile_seconds', 'target' => 2.5, 'unit' => 'seconds_max'],
                    ['metric' => 'cls_mobile', 'target' => 0.1, 'unit' => 'max'],
                    ['metric' => 'mobile_usability_errors', 'target' => 0, 'unit' => 'count'],
                ],
                'file' => '10_riya_frontend.md',
            ],
            [
                'slug' => 'karan_jikra',
                'name' => 'Karan',
                'role' => 'DevOps & Deploy Pipeline Owner',
                'department' => 'technology',
                'reports_to_slug' => 'diksha',
                'expertise_tags' => ['devops', 'aws', 'lightsail', 'postgresql', 'redis', 'meilisearch', 'deploy', 'monitoring', 'backups', 'ci_cd'],
                'connected_to' => ['aditya_jikra', 'diya_jikra', 'mira_jikra', 'aryan_jikra'],
                'kpi_definitions' => [
                    ['metric' => 'production_uptime', 'target' => 99.9, 'unit' => '%'],
                    ['metric' => 'mttr_minutes', 'target' => 60, 'unit' => 'min_max'],
                    ['metric' => 'failed_deploys_per_month', 'target' => 1, 'unit' => 'count_max'],
                ],
                'file' => '11_karan_devops.md',
            ],
            [
                'slug' => 'diya_jikra',
                'name' => 'Diya',
                'role' => 'AI/ML & Recommendations Engineer',
                'department' => 'technology',
                'reports_to_slug' => 'diksha',
                'expertise_tags' => ['ml', 'recommendations', 'meilisearch', 'personalisation', 'fraud_detection', 'llm', 'ranking', 'embeddings'],
                'connected_to' => ['aditya_jikra', 'karan_jikra', 'anaya_jikra', 'mira_jikra'],
                'kpi_definitions' => [
                    ['metric' => 'recommendation_ctr', 'target' => 5, 'unit' => '%'],
                    ['metric' => 'fraud_true_positive_rate', 'target' => 85, 'unit' => '%'],
                    ['metric' => 'llm_cost_per_session_inr', 'target' => 0.5, 'unit' => 'INR_max'],
                ],
                'file' => '12_diya_ml_recommendations.md',
            ],
            [
                'slug' => 'yash_jikra',
                'name' => 'Yash',
                'role' => 'Customer Support & Reviews Manager',
                'department' => 'operations',
                'reports_to_slug' => 'diksha',
                'expertise_tags' => ['support', 'tickets', 'reviews', 'csat', 'returns', 'qa', 'community'],
                'connected_to' => ['sneha_jikra', 'naina_jikra', 'anaya_jikra', 'aryan_jikra', 'ishaan_jikra'],
                'kpi_definitions' => [
                    ['metric' => 'first_response_hours', 'target' => 2, 'unit' => 'hours_max'],
                    ['metric' => 'csat', 'target' => 4.5, 'unit' => 'of_5'],
                    ['metric' => 'negative_review_response_rate', 'target' => 100, 'unit' => '%'],
                ],
                'file' => '13_yash_support_reviews.md',
            ],
            [
                'slug' => 'sneha_jikra',
                'name' => 'Sneha',
                'role' => 'Fulfillment & Logistics Lead',
                'department' => 'operations',
                'reports_to_slug' => 'diksha',
                'expertise_tags' => ['logistics', 'shipping', 'delhivery', 'rto', 'returns', 'fraud_signals', 'cod_reconciliation', 'warehouse'],
                'connected_to' => ['yash_jikra', 'aryan_jikra', 'diya_jikra', 'prerna_jikra'],
                'kpi_definitions' => [
                    ['metric' => 'on_time_delivery_rate', 'target' => 90, 'unit' => '%'],
                    ['metric' => 'rto_rate', 'target' => 12, 'unit' => '%_max'],
                    ['metric' => 'avg_delivery_days', 'target' => 4, 'unit' => 'days_max'],
                ],
                'file' => '14_sneha_fulfillment.md',
            ],
            [
                'slug' => 'aryan_jikra',
                'name' => 'Aryan',
                'role' => 'Finance & Payments Lead',
                'department' => 'finance',
                'reports_to_slug' => 'diksha',
                'expertise_tags' => ['finance', 'razorpay', 'payments', 'refunds', 'gst', 'reconciliation', 'margins', 'payouts'],
                'connected_to' => ['mira_jikra', 'naina_jikra', 'sneha_jikra', 'aditya_jikra'],
                'kpi_definitions' => [
                    ['metric' => 'revenue_close_time', 'target' => '11_AM_next_day', 'unit' => 'time'],
                    ['metric' => 'refund_processing_hours', 'target' => 48, 'unit' => 'hours_max'],
                    ['metric' => 'reconciliation_discrepancy_pct', 'target' => 0.1, 'unit' => '%_max'],
                ],
                'file' => '15_aryan_finance.md',
            ],
            [
                'slug' => 'mira_jikra',
                'name' => 'Mira',
                'role' => 'Jikra Data Analyst',
                'department' => 'analytics',
                'reports_to_slug' => 'diksha',
                'expertise_tags' => ['analytics', 'ga4', 'gsc', 'attribution', 'cohorts', 'dashboards', 'kpis', 'reporting'],
                'connected_to' => ['rohit_jikra', 'anaya_jikra', 'naina_jikra', 'tanvi_jikra', 'aryan_jikra', 'diya_jikra'],
                'kpi_definitions' => [
                    ['metric' => 'daily_kpi_snapshot_time', 'target' => '9_30_AM', 'unit' => 'time'],
                    ['metric' => 'anomaly_detection_hours', 'target' => 1, 'unit' => 'hours_max'],
                    ['metric' => 'metric_definition_disputes', 'target' => 0, 'unit' => 'count'],
                ],
                'file' => '16_mira_analytics.md',
            ],
        ];

        foreach ($members as $m) {
            $promptFile = $teamMemoryPath . DIRECTORY_SEPARATOR . $m['file'];
            $systemPrompt = file_exists($promptFile)
                ? file_get_contents($promptFile)
                : "Member file missing: {$m['file']}. Slug: {$m['slug']}.";

            AiTeamMember::updateOrCreate(
                ['slug' => $m['slug']],
                [
                    'name' => $m['name'],
                    'role' => $m['role'],
                    'department' => $m['department'],
                    'reports_to_slug' => $m['reports_to_slug'],
                    'system_prompt' => $systemPrompt,
                    'expertise_tags' => $m['expertise_tags'],
                    'connected_to' => $m['connected_to'],
                    'kpi_definitions' => $m['kpi_definitions'],
                    'is_active' => true,
                    'last_trained_at' => now(),
                ]
            );

            $this->command?->info("Seeded {$m['slug']}: {$m['name']} — {$m['role']}");
        }

        $this->command?->info('Total members seeded: ' . count($members));
    }

    private function seedInitialKnowledge(): void
    {
        $diksha = AiTeamMember::where('slug', 'diksha')->first();
        $karan = AiTeamMember::where('slug', 'karan_jikra')->first();
        $aditya = AiTeamMember::where('slug', 'aditya_jikra')->first();
        $aarav = AiTeamMember::where('slug', 'aarav_ba_jikra')->first();

        $entries = [
            [
                'member_id' => null,
                'category' => 'technology',
                'title' => 'Jikra Stack Snapshot (2026-04-21)',
                'content' => 'Laravel 12, PHP 8.3, PostgreSQL, Redis, Meilisearch, Tailwind, Alpine. Multi-tenant via stancl/tenancy v3.10. Central DB jikra_central; tenant DB jikra (tenant #1). Production: AWS Lightsail 13.205.162.30 at /var/www/jikra. Razorpay payments. Meta Graph API v21.0.',
                'priority' => 'critical',
            ],
            [
                'member_id' => $karan?->id,
                'category' => 'technology',
                'title' => 'Deploy Pipeline (Karan-only)',
                'content' => 'Production deploys ONLY by Karan. Path: development → main (CI/CD) → AWS Lightsail. Requires Diksha merge approval + smoke test + rollback plan. After PHP deploys: restart php8.3-fpm. After view-only: php artisan view:clear. Direct push to AWS forbidden for everyone except Karan.',
                'priority' => 'critical',
            ],
            [
                'member_id' => null,
                'category' => 'technology',
                'title' => 'Git Workflow (mandatory)',
                'content' => 'Every code change: feature branch off development → pull → code → test → tag/content/QA validation → PR to development → Diksha approves → Karan merges and deploys. Branch naming: feature|fix|hotfix|content/jikra-<short>-<YYYY-MM-DD>. NO direct push to AWS, ever.',
                'priority' => 'critical',
            ],
            [
                'member_id' => null,
                'category' => 'technology',
                'title' => 'Direct DB-edit Channel',
                'content' => 'Direct Postgres data fixes ALLOWED only by Karan (or Aditya with Diksha approval) for non-schema, single-record-class fixes. Every edit logged in ai_team_db_edit_log table with reason, before/after. Schema changes always go via Git migration.',
                'priority' => 'critical',
            ],
            [
                'member_id' => null,
                'category' => 'platform_update',
                'title' => 'Account Scope: Jikra D2C',
                'content' => 'Brand: Jikra (jikra.in). 743+ products across 50+ categories. Channels: Google Merchant ID 5752082852, GA4 528759246, Search Console (jikra.in), GBP, Instagram (jikraofficial), Facebook, WhatsApp Phone Number ID 979502275251082. Payments: Razorpay.',
                'priority' => 'critical',
            ],
            [
                'member_id' => $diksha?->id,
                'category' => 'dcrayons_metric',
                'title' => 'Account-Level KPIs (Diksha primary)',
                'content' => 'GMV (set quarterly), conversion rate >=2.5%, blended ROAS >=3.0x, repeat purchase 90d >=25%, defect rate <2%, mobile LCP <2.5s, PR turnaround <24h, MTTR <60min.',
                'priority' => 'high',
            ],
            [
                'member_id' => $aarav?->id,
                'category' => 'industry_trend',
                'title' => 'Task Spec Template (Aarav)',
                'content' => 'Every task: ID, Title, Goal, Why, Acceptance Criteria (3+ testable), Constraints (tech/time/budget), Suggested Owner (slug), Estimated Effort (S/M/L), Dependencies. Reject vague inputs; ask 1-3 clarifying questions instead.',
                'priority' => 'high',
            ],
            [
                'member_id' => $aditya?->id,
                'category' => 'technology',
                'title' => 'Tenant Safety (Aditya enforced in PR review)',
                'content' => 'Every PR review checks: tenant scope intact, no central-DB writes from tenant context, no N+1 queries, security (mass assignment / XSS / SQL injection), no hardcoded content (use Setting::get / __ / config). New migrations always go in database/migrations/tenant/.',
                'priority' => 'critical',
            ],
            [
                'member_id' => null,
                'category' => 'technology',
                'title' => 'Model Selection Guideline',
                'content' => 'Claude Haiku 4.5: classification, summarisation, fast cheap tasks. Claude Sonnet 4.6: code, content writing, complex reasoning. Claude Opus 4.7 (1M context): high-stakes architecture, deep analysis, large context. Don\'t default to Opus — wasteful. Don\'t default to Haiku — shallow.',
                'priority' => 'high',
            ],
            [
                'member_id' => null,
                'category' => 'industry_trend',
                'title' => 'Prompt Engineering Baseline (every member)',
                'content' => 'Structure: Role + Context + Task + Format + Constraints. Use few-shot examples when format matters. System prompt for stable identity; user prompt for the variable task. If output is wrong, fix the prompt — not the model. Iterate, don\'t escalate.',
                'priority' => 'high',
            ],
        ];

        foreach ($entries as $e) {
            AiTeamKnowledge::create([
                'ai_team_member_id' => $e['member_id'],
                'category' => $e['category'],
                'title' => $e['title'],
                'content' => $e['content'],
                'knowledge_date' => now()->toDateString(),
                'priority' => $e['priority'],
                'region' => 'india',
                'is_verified' => true,
                'relevance_score' => match($e['priority']) {
                    'critical' => 95,
                    'high' => 80,
                    default => 60,
                },
                'source' => 'JikraTeamSeeder (founding 2026-04-21)',
            ]);
        }

        $this->command?->info('Knowledge entries seeded: ' . count($entries));
    }
}
