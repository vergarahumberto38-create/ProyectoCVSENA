<?php

/*
 * Agrega estas rutas dentro de tu routes/web.php,
 * dentro del grupo middleware(['auth']) que ya tengas (o crea uno nuevo).
 */

use App\Http\Controllers\CvExportController;
use App\Http\Controllers\PublicCvController;
use App\Livewire\CvWizard;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
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

// Ruta pública (SIN auth): cualquiera con el enlace puede ver el CV,
// siempre que el dueño lo haya marcado como público (is_public = true).
// IMPORTANTE: esta ruta debe ir DESPUÉS de las demás rutas de /cv/... en tu
// web.php para que no choque con /cv/crear, /cv/preview, etc.
Route::get('/cv/{slug}', [PublicCvController::class, 'show'])->name('cv.public');

// Ruta pública (sin auth) para compartir el CV por enlace - se implementa en fase posterior
// Route::get('/cv/{slug}', [PublicCvController::class, 'show'])->name('cv.public');
