<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKeywordRequest;
use App\Http\Requests\UpdateKeywordRequest;
use App\Models\Keyword;
use App\Models\Project;

class KeywordController extends Controller
{
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        $keywords = $project->keywords()->with('rankings')->latest()->paginate(20);

        return view('projects.keywords.index', compact('project', 'keywords'));
    }

    public function create(Project $project)
    {
        $this->authorize('update', $project);

        return view('projects.keywords.create', compact('project'));
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

        $keyword->load('rankings');

        return view('projects.keywords.show', compact('project', 'keyword'));
    }

    public function edit(Project $project, Keyword $keyword)
    {
        $this->authorizeKeyword($project, $keyword);

        return view('projects.keywords.edit', compact('project', 'keyword'));
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
