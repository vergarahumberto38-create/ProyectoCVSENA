<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CvExportController;
use App\Http\Controllers\PublicCvController;
use App\Livewire\CvWizard;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/cv/crear', CvWizard::class)->name('cv.wizard');

    // Previsualización del CV con la plantilla ATS-friendly
    Route::get('/cv/preview', function () {
        $profile = auth()->user()->profile()
            ->with(['educations', 'experiences', 'skills', 'languages'])
            ->firstOrFail();

        return view('cv.preview', compact('profile'));
    })->name('cv.preview');

    // Exportación a PDF
    Route::get('/cv/exportar/pdf', [CvExportController::class, 'pdf'])->name('cv.export.pdf');

    // Exportación a DOCX
    Route::get('/cv/exportar/docx', [CvExportController::class, 'docx'])->name('cv.export.docx');
});

Route::get('/cv/{slug}', [PublicCvController::class, 'show'])->name('cv.public');

require __DIR__.'/auth.php';
