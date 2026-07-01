<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\JobStage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StageMediaController extends Controller
{
    public function store(Request $request, JobStage $jobStage): RedirectResponse
    {
        $this->authorize('view', $jobStage);

        $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:10'],
            'photos.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,heic', 'max:10240'],
        ]);

        foreach ($request->file('photos', []) as $file) {
            $jobStage->addMedia($file)->toMediaCollection('stage-photos');
        }

        return back()->with('success', 'Photos uploaded successfully.');
    }
}
