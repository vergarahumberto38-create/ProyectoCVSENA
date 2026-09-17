<?php

namespace App\Http\Controllers;

use App\Models\Profile;

class PublicCvController extends Controller
{
    public function show(string $slug)
    {
        $profile = Profile::where('public_slug', $slug)
            ->where('is_public', true)
            ->with(['educations', 'experiences', 'skills', 'languages'])
            ->firstOrFail();

        return view('cv.public', compact('profile'));
    }
}