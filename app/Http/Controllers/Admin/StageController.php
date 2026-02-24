<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStageRequest;
use App\Http\Requests\UpdateStageRequest;
use App\Models\Stage;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/StageConfiguration', [
            'stages' => Stage::query()->orderBy('sequence')->get(),
        ]);
    }

    public function store(StoreStageRequest $request): RedirectResponse
    {
        Stage::query()->create($request->validated());

        return back()->with('success', 'Stage created successfully.');
    }

    public function update(UpdateStageRequest $request, Stage $stage): RedirectResponse
    {
        $stage->update($request->validated());

        return back()->with('success', 'Stage updated successfully.');
    }
}
