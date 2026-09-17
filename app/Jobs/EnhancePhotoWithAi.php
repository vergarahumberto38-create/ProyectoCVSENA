<?php

namespace App\Jobs;

use App\Models\Profile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Format;
use Intervention\Image\Laravel\Facades\Image;
use Throwable;

/**
 * Genera una versión "profesional" de la foto de perfil usando un modelo
 * de IA generativa (OpenAI gpt-image-1, endpoint /v1/images/edits):
 * fondo blanco, vestimenta formal y vista al frente.
 *
 * Requiere OPENAI_API_KEY configurada en .env. Si no está configurada,
 * o si la llamada a la API falla por cualquier motivo, el usuario NUNCA
 * se queda sin foto: se conserva el recorte estándar de la Fase 3
 * (sin la edición generativa) y se marca el estado como 'failed' con
 * un mensaje claro, para que pueda reintentar.
 *
 * Limitación conocida: el modelo edita la imagen completa (no usamos una
 * máscara que proteja solo el rostro), así que el resultado depende de
 * qué tan bien el modelo respete "mantener el rostro igual" en el prompt.
 * No hay garantía perfecta de identidad facial 1:1 con este enfoque.
 */
class EnhancePhotoWithAi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 90;

    protected int $targetWidth = 400;
    protected int $targetHeight = 500;

    protected string $prompt = 'Edita esta fotografía de una persona para que sea apta como foto de currículum profesional: coloca un fondo blanco liso y uniforme, viste a la persona con ropa formal de oficina (traje y corbata si es hombre, o blazer/traje formal si es mujer, según corresponda a la persona en la foto), y ajusta el encuadre para que la persona mire de frente a la cámara, con los hombros centrados. Mantén el rostro, los rasgos, la identidad y la expresión de la persona exactamente iguales a la foto original; solo cambia el fondo, la vestimenta y el encuadre.';

    public function __construct(public Profile $profile)
    {
    }

    public function handle(): void
    {
        $profile = $this->profile->fresh();

        if (! $profile || ! $profile->photo_ats_path) {
            return;
        }

        $profile->update(['photo_ai_status' => 'processing', 'photo_ai_error' => null]);

        $apiKey = config('services.openai.key');

        if (empty($apiKey)) {
            $this->fallbackToLocalEnhancement($profile, 'La generación con IA no está configurada (falta OPENAI_API_KEY). Se aplicó solo un ajuste de calidad local.');
            return;
        }

        try {
            $imageContents = Storage::disk('public')->get($profile->photo_ats_path);

            $response = Http::withToken($apiKey)
                ->timeout(90)
                ->attach('image[]', $imageContents, 'photo.jpg')
                ->asMultipart()
                ->post('https://api.openai.com/v1/images/edits', [
                    ['name' => 'model', 'contents' => 'gpt-image-1'],
                    ['name' => 'prompt', 'contents' => $this->prompt],
                    ['name' => 'size', 'contents' => '1024x1536'],
                    ['name' => 'n', 'contents' => '1'],
                    ['name' => 'quality', 'contents' => 'high'],
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    'La API de OpenAI respondió con error: '.$response->status().' '.$response->body()
                );
            }

            $base64 = $response->json('data.0.b64_json');

            if (! $base64) {
                throw new \RuntimeException('La respuesta de OpenAI no incluyó una imagen (b64_json vacío).');
            }

            $generatedBytes = base64_decode($base64);

            // Recortamos al tamaño estándar de foto de CV para que quede
            // consistente con el resto de la interfaz y con el PDF/DOCX.
            $finalImage = Image::decode($generatedBytes)
                ->cover($this->targetWidth, $this->targetHeight)
                ->encodeUsingFormat(Format::JPEG, quality: 90);

            $enhancedPath = 'profiles/'.$profile->id.'/cv/'.uniqid('photo_ai_').'.jpg';
            Storage::disk('public')->put($enhancedPath, (string) $finalImage);

            $previousPath = $profile->photo_ats_path;

            $profile->update([
                'photo_ats_path' => $enhancedPath,
                'photo_ai_status' => 'completed',
                'photo_ai_error' => null,
            ]);

            if ($previousPath && $previousPath !== $enhancedPath) {
                Storage::disk('public')->delete($previousPath);
            }
        } catch (Throwable $e) {
            Log::warning('EnhancePhotoWithAi (OpenAI) falló para profile '.$profile->id.': '.$e->getMessage());

            $this->fallbackToLocalEnhancement($profile, 'No se pudo generar la foto profesional con IA. Se aplicó un ajuste de calidad local sobre tu foto original.');
        }
    }

    /**
     * Si la generación con IA no está disponible o falla, en vez de dejar
     * al usuario sin ninguna mejora, aplicamos localmente el ajuste de
     * brillo/contraste/nitidez (sin depender de ninguna API).
     */
    protected function fallbackToLocalEnhancement(Profile $profile, string $message): void
    {
        try {
            $contents = Storage::disk('public')->get($profile->photo_ats_path);

            $enhanced = Image::decode($contents)
                ->brightness(5)
                ->contrast(8)
                ->sharpen(8)
                ->encodeUsingFormat(Format::JPEG, quality: 90);

            $enhancedPath = 'profiles/'.$profile->id.'/cv/'.uniqid('photo_local_').'.jpg';
            Storage::disk('public')->put($enhancedPath, (string) $enhanced);

            $previousPath = $profile->photo_ats_path;

            $profile->update([
                'photo_ats_path' => $enhancedPath,
                'photo_ai_status' => 'failed',
                'photo_ai_error' => $message,
            ]);

            if ($previousPath && $previousPath !== $enhancedPath) {
                Storage::disk('public')->delete($previousPath);
            }
        } catch (Throwable $e) {
            Log::warning('Fallback local también falló para profile '.$profile->id.': '.$e->getMessage());

            $profile->update([
                'photo_ai_status' => 'failed',
                'photo_ai_error' => $message,
            ]);
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->profile->fresh()?->update([
            'photo_ai_status' => 'failed',
            'photo_ai_error' => 'No se pudo procesar la foto tras varios intentos.',
        ]);
    }
}