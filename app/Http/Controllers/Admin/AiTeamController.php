<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiTeamConversation;
use App\Models\AiTeamDailyBrief;
use App\Models\AiTeamKnowledge;
use App\Models\AiTeamMember;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiTeamController extends Controller
{
    /**
     * Display a listing of active AI team members, grouped by department.
     */
    public function index(): View
    {
        $members = AiTeamMember::where('is_active', true)
            ->withCount('knowledge')
            ->orderBy('department')
            ->orderBy('name')
            ->get();

        $stats = [
            'members' => $members->count(),
            'knowledge' => AiTeamKnowledge::count(),
            'briefs' => AiTeamDailyBrief::count(),
            'conversations' => AiTeamConversation::count(),
        ];

        $membersByDepartment = $members->groupBy('department');

        // Presentation order for known departments (unknown ones fall to end).
        $departmentOrder = [
            'leadership' => 'Leadership',
            'operations' => 'Operations & BA',
            'technology' => 'Technology',
            'ecommerce_ops' => 'Catalog & Merchandising',
            'marketing' => 'Growth & Marketing',
            'finance' => 'Finance',
            'analytics' => 'Data & Analytics',
        ];

        return view('admin.ai-team.index', compact(
            'membersByDepartment',
            'departmentOrder',
            'stats'
        ));
    }

    /**
     * Display a specific team member with their knowledge.
     */
    public function show(string $slug): View
    {
        $member = AiTeamMember::where('slug', $slug)->firstOrFail();

        $knowledge = $member->knowledge()
            ->orderByDesc('knowledge_date')
            ->orderByDesc('priority')
            ->limit(100)
            ->get();

        $todayBrief = $member->dailyBriefs()
            ->where('brief_date', now()->toDateString())
            ->first();

        $reportsTo = $member->reportsTo();
        $directReports = $member->directReports();

        return view('admin.ai-team.show', compact(
            'member',
            'knowledge',
            'todayBrief',
            'reportsTo',
            'directReports'
        ));
    }

    /**
     * Display the searchable knowledge explorer.
     */
    public function knowledge(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $memberId = $request->input('member_id');
        $category = $request->input('category');
        $priority = $request->input('priority');

        $query = AiTeamKnowledge::with('member')
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where(function ($inner) use ($like) {
                    $inner->where('title', 'ilike', $like)
                        ->orWhere('content', 'ilike', $like);
                });
            })
            ->when($memberId, fn ($q) => $q->where('ai_team_member_id', $memberId))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($priority, fn ($q) => $q->where('priority', $priority))
            ->orderByDesc('knowledge_date')
            ->orderByDesc('id');

        $entries = $query->paginate(25)->withQueryString();

        $members = AiTeamMember::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $categories = AiTeamKnowledge::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $priorities = ['critical', 'high', 'medium', 'low'];

        return view('admin.ai-team.knowledge', compact(
            'entries',
            'members',
            'categories',
            'priorities',
            'search',
            'memberId',
            'category',
            'priority'
        ));
    }
}
