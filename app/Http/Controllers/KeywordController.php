<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKeywordRequest;
use App\Http\Requests\UpdateKeywordRequest;
use App\Models\Keyword;
use App\Models\KeywordRanking;
use App\Models\Project;
use Illuminate\Http\Request;

class KeywordController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $search = trim($request->string('search')->toString());
        $country = trim($request->string('country')->toString());
        $device = trim($request->string('device')->toString());

        $baseQuery = $project->keywords();
        $countries = (clone $baseQuery)->select('country')->distinct()->orderBy('country')->pluck('country');
        $devices = (clone $baseQuery)->select('device')->distinct()->orderBy('device')->pluck('device');

        $keywords = $baseQuery
            ->withRankingSummary()
            ->when($search !== '', fn ($query) => $query->where('keyword', 'like', "%{$search}%"))
            ->when($country !== '', fn ($query) => $query->where('country', $country))
            ->when($device !== '', fn ($query) => $query->where('device', $device))
            ->latest()
            ->paginate(20)
            ->withQueryString();
        $keywordCount = $project->keywords()->count();
        $keywordLimit = $request->user()->planLimit('keywords_per_project');

        return view('projects.keywords.index', compact(
            'project',
            'keywords',
            'keywordCount',
            'keywordLimit',
            'search',
            'country',
            'device',
            'countries',
            'devices',
        ));
    }

    public function create(Project $project)
    {
        $this->authorize('update', $project);

        $keywordCount = $project->keywords()->count();
        $keywordLimit = auth()->user()->planLimit('keywords_per_project');

        return view('projects.keywords.create', compact('project', 'keywordCount', 'keywordLimit'));
    }

    public function store(StoreKeywordRequest $request, Project $project)
    {
        $this->authorize('update', $project);

        $project->keywords()->create($request->validated());

        return redirect()->route('keywords.index', $project)->with('status', 'Keyword added.');
    }

    public function show(Project $project, Keyword $keyword)
    {
        $this->authorizeKeyword($project, $keyword);

        $keyword->load(['recentRankings']);
        $keyword->loadMin(['rankings as best_position_value' => fn ($query) => $query
            ->where('status', KeywordRanking::STATUS_FOUND)
            ->whereNotNull('position')
        ], 'position');

        $chartRankings = $keyword->rankings()
            ->where('status', KeywordRanking::STATUS_FOUND)
            ->whereNotNull('position')
            ->orderByDesc('checked_at')
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->sortBy([['checked_at', 'asc'], ['id', 'asc']])
            ->values();

        $historyRankings = $keyword->rankings()
            ->successful()
            ->orderByDesc('checked_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('projects.keywords.show', compact('project', 'keyword', 'chartRankings', 'historyRankings'));
    }

    public function edit(Project $project, Keyword $keyword)
    {
        $this->authorizeKeyword($project, $keyword);

        $keywordCount = $project->keywords()->count();
        $keywordLimit = auth()->user()->planLimit('keywords_per_project');

        return view('projects.keywords.edit', compact('project', 'keyword', 'keywordCount', 'keywordLimit'));
    }

    public function update(UpdateKeywordRequest $request, Project $project, Keyword $keyword)
    {
        $this->authorizeKeyword($project, $keyword);

        $keyword->update($request->validated());

        return redirect()->route('keywords.index', $project)->with('status', 'Keyword updated.');
    }

    public function destroy(Project $project, Keyword $keyword)
    {
        $this->authorizeKeyword($project, $keyword);

        $keyword->delete();

        return redirect()->route('keywords.index', $project)->with('status', 'Keyword deleted.');
    }

    private function authorizeKeyword(Project $project, Keyword $keyword): void
    {
        abort_unless($keyword->project_id === $project->id, 404);
        $this->authorize('update', $project);
    }
}
