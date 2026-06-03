<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_team_members', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 40)->unique();
            $table->string('name', 60);
            $table->string('role', 120);
            $table->string('department', 50);
            $table->longText('system_prompt');
            $table->string('avatar_url')->nullable();
            $table->json('expertise_tags');
            $table->json('connected_to');
            $table->json('kpi_definitions');
            $table->string('reports_to_slug', 40)->nullable();
            $table->decimal('performance_score', 3, 1)->default(0);
            $table->integer('total_conversations')->default(0);
            $table->integer('total_decisions_made')->default(0);
            $table->integer('decisions_accepted')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_trained_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_team_knowledge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_team_member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 40);
            $table->string('title', 255);
            $table->longText('content');
            $table->string('source', 255)->nullable();
            $table->string('source_url', 500)->nullable();
            $table->date('knowledge_date');
            $table->date('expires_at')->nullable();
            $table->string('priority', 20)->default('medium');
            $table->string('region', 20)->default('india');
            $table->json('tags')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->integer('relevance_score')->default(50);
            $table->timestamps();

            $table->index(['category', 'knowledge_date']);
            $table->index(['ai_team_member_id', 'category']);
            $table->index('priority');
        });

        Schema::create('ai_team_daily_briefs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_team_member_id')->constrained()->cascadeOnDelete();
            $table->date('brief_date');
            $table->longText('brief_content');
            $table->json('knowledge_ids');
            $table->json('action_items')->nullable();
            $table->json('metrics_snapshot')->nullable();
            $table->string('status', 20)->default('generated');
            $table->timestamps();

            $table->unique(['ai_team_member_id', 'brief_date']);
        });

        Schema::create('ai_team_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_team_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable();
            $table->string('topic', 255);
            $table->longText('question');
            $table->longText('response');
            $table->string('decision_outcome', 20)->default('pending');
            $table->text('feedback')->nullable();
            $table->tinyInteger('rating')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['ai_team_member_id', 'created_at']);
        });

        Schema::create('ai_team_git_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_team_member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('task_id', 50);
            $table->string('branch_name', 120);
            $table->string('action', 30);
            $table->string('pr_url', 255)->nullable();
            $table->string('commit_sha', 40)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('task_id');
            $table->index('branch_name');
        });

        Schema::create('ai_team_db_edit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approved_by_member_id')->nullable()->constrained('ai_team_members')->nullOnDelete();
            $table->foreignId('executed_by_member_id')->nullable()->constrained('ai_team_members')->nullOnDelete();
            $table->string('table_name', 80);
            $table->string('record_identifier', 120)->nullable();
            $table->text('reason');
            $table->longText('before_value')->nullable();
            $table->longText('after_value')->nullable();
            $table->timestamp('executed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_team_db_edit_log');
        Schema::dropIfExists('ai_team_git_log');
        Schema::dropIfExists('ai_team_conversations');
        Schema::dropIfExists('ai_team_daily_briefs');
        Schema::dropIfExists('ai_team_knowledge');
        Schema::dropIfExists('ai_team_members');
    }
};
