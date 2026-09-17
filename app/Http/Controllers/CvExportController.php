<?php

namespace App\Http\Controllers;

use App\Models\CvExport;
use App\Models\Profile;
use App\Services\CvTextCorrector;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Format;
use Intervention\Image\Laravel\Facades\Image;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CvExportController extends Controller
{
    public function pdf()
    {
        $profile = auth()->user()->profile()
            ->with(['educations', 'experiences', 'skills', 'languages'])
            ->firstOrFail();
        $exportProfile = app(CvTextCorrector::class)->prepare($profile);

        $photoSrc = $this->photoAsBase64($exportProfile);

        $pdf = Pdf::loadView('cv.pdf', [
            'profile' => $exportProfile,
            'photoSrc' => $photoSrc,
        ])->setPaper('letter');

        $filename = Str::slug($exportProfile->full_name ?: 'cv').'.pdf';

        CvExport::create([
            'profile_id' => $profile->id,
            'type' => 'pdf',
            'file_path' => null, // se genera al vuelo, no se guarda en disco
        ]);

        return $pdf->download($filename);
    }

    /**
     * Exportación a DOCX usando PhpWord.
     *
     * IMPORTANTE: no reutilizamos la plantilla Blade aquí (HTML y DOCX son
     * formatos muy distintos); en su lugar construimos el documento
     * programáticamente, pero replicando la MISMA estructura ATS-friendly:
     * una columna, sin tablas para el contenido principal, texto plano,
     * fuente estándar y datos de contacto siempre como texto.
     */
    public function docx(): StreamedResponse
    {
        $profile = auth()->user()->profile()
            ->with(['educations', 'experiences', 'skills', 'languages'])
            ->firstOrFail();
        $exportProfile = app(CvTextCorrector::class)->prepare($profile);

        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginTop' => 900,
            'marginBottom' => 900,
            'marginLeft' => 900,
            'marginRight' => 900,
        ]);

        // Estilos reutilizables
        $nameStyle = ['bold' => true, 'size' => 20];
        $headlineStyle = ['italic' => true, 'size' => 12, 'color' => '444444'];
        $contactStyle = ['size' => 10, 'color' => '333333'];
        $sectionTitleStyle = ['bold' => true, 'size' => 12, 'color' => '222222'];
        $sectionTitleParagraph = ['spaceBefore' => 240, 'spaceAfter' => 100, 'borderBottomSize' => 6, 'borderBottomColor' => '999999'];
        $itemTitleStyle = ['bold' => true, 'size' => 11];
        $itemSubtitleStyle = ['size' => 10, 'color' => '444444'];
        $itemDatesStyle = ['italic' => true, 'size' => 9, 'color' => '666666'];
        $bodyStyle = ['size' => 10.5];

        // --- ENCABEZADO ---
        $section->addText($exportProfile->full_name ?: '', $nameStyle);

        if ($exportProfile->headline) {
            $section->addText($exportProfile->headline, $headlineStyle);
        }

        $contactParts = array_filter([
            $exportProfile->phone,
            auth()->user()->email ?? null,
            trim(($exportProfile->city ?? '').(($exportProfile->city && $exportProfile->country) ? ', ' : '').($exportProfile->country ?? '')),
            $exportProfile->linkedin_url,
            $exportProfile->portfolio_url,
        ]);

        if (! empty($contactParts)) {
            $section->addText(implode('  |  ', $contactParts), $contactStyle, ['spaceAfter' => 200]);
        }

        // --- PERFIL PROFESIONAL ---
        if ($exportProfile->summary) {
            $section->addText('PERFIL PROFESIONAL', $sectionTitleStyle, $sectionTitleParagraph);
            $section->addText($exportProfile->summary, $bodyStyle);
        }

        // --- EXPERIENCIA ---
        if ($exportProfile->experiences->isNotEmpty()) {
            $section->addText('EXPERIENCIA LABORAL', $sectionTitleStyle, $sectionTitleParagraph);

            foreach ($exportProfile->experiences as $exp) {
                $section->addText($exp->position.' — '.$exp->company, $itemTitleStyle, ['spaceBefore' => 120]);

                if ($exp->location) {
                    $section->addText($exp->location, $itemSubtitleStyle);
                }

                $dates = optional($exp->start_date)->format('M Y').' — '.
                    ($exp->is_current ? 'Actualidad' : optional($exp->end_date)->format('M Y'));
                $section->addText($dates, $itemDatesStyle);

                if ($exp->description) {
                    $section->addText($exp->description, $bodyStyle);
                }

                if ($exp->achievements) {
                    $section->addText($exp->achievements, $bodyStyle);
                }
            }
        }

        // --- EDUCACIÓN ---
        if ($exportProfile->educations->isNotEmpty()) {
            $section->addText('EDUCACIÓN', $sectionTitleStyle, $sectionTitleParagraph);

            foreach ($exportProfile->educations as $edu) {
                $title = $edu->degree.($edu->field_of_study ? ' — '.$edu->field_of_study : '');
                $section->addText($title, $itemTitleStyle, ['spaceBefore' => 120]);
                $section->addText($edu->institution, $itemSubtitleStyle);

                $dates = optional($edu->start_date)->format('M Y').' — '.
                    ($edu->is_current ? 'En curso' : optional($edu->end_date)->format('M Y'));
                $section->addText($dates, $itemDatesStyle);

                if ($edu->description) {
                    $section->addText($edu->description, $bodyStyle);
                }
            }
        }

        // --- HABILIDADES ---
        if ($exportProfile->skills->isNotEmpty()) {
            $section->addText('HABILIDADES', $sectionTitleStyle, $sectionTitleParagraph);

            $skillsLine = $exportProfile->skills->map(function ($skill) {
                return $skill->name.($skill->level ? ' ('.$skill->level.')' : '');
            })->implode('  ·  ');

            $section->addText($skillsLine, $bodyStyle);
        }

        // --- IDIOMAS ---
        if ($exportProfile->languages->isNotEmpty()) {
            $section->addText('IDIOMAS', $sectionTitleStyle, $sectionTitleParagraph);

            $langLine = $exportProfile->languages->map(function ($lang) {
                return $lang->name.($lang->level ? ' ('.$lang->level.')' : '');
            })->implode('  ·  ');

            $section->addText($langLine, $bodyStyle);
        }

        $filename = Str::slug($exportProfile->full_name ?: 'cv').'.docx';
        $tempPath = tempnam(sys_get_temp_dir(), 'cv_docx_');

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        CvExport::create([
            'profile_id' => $profile->id,
            'type' => 'docx',
            'file_path' => null,
        ]);

        return response()->streamDownload(function () use ($tempPath) {
            echo file_get_contents($tempPath);
            unlink($tempPath);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /**
     * dompdf no siempre resuelve bien rutas /storage/... como imágenes remotas,
     * así que convertimos la foto a base64 embebido, garantizando que
     * siempre aparezca en el PDF sin depender de rutas públicas.
     */
    protected function photoAsBase64(Profile $profile): ?string
    {
        if (! $profile->photo_ats_path || ! Storage::disk('public')->exists($profile->photo_ats_path)) {
            return null;
        }

        $contents = Storage::disk('public')->get($profile->photo_ats_path);
        $mime = Storage::disk('public')->mimeType($profile->photo_ats_path) ?? 'image/jpeg';

        $contents = (string) Image::decode($contents)
            ->cover(400, 500)
            ->encodeUsingFormat(Format::JPEG, quality: 85);
        $mime = 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }
}
