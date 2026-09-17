<div class="max-w-md mx-auto space-y-4">

    <h3 class="font-medium text-lg">Foto de perfil</h3>

    @if (session('photo_success'))
        <div class="p-2 bg-green-100 text-green-800 rounded text-sm">{{ session('photo_success') }}</div>
    @endif

    <div class="flex items-center gap-6">
        <div class="w-32 h-40 rounded overflow-hidden border bg-gray-100 flex items-center justify-center">
            @if ($currentAtsPhotoPath)
                <img src="{{ Storage::url($currentAtsPhotoPath) }}" alt="Foto de perfil" class="w-full h-full object-cover">
            @else
                <span class="text-gray-400 text-xs text-center px-2">Sin foto</span>
            @endif
        </div>

        <div class="flex-1 space-y-2">
            <label class="block">
                <span class="sr-only">Elegir foto</span>
                <input type="file" wire:model="photo" accept="image/png, image/jpeg, image/webp"
                    class="block w-full text-sm text-gray-600
                           file:mr-4 file:py-2 file:px-4 file:rounded file:border-0
                           file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            </label>

            <div wire:loading wire:target="photo" class="text-sm text-gray-500">
                Subiendo y recortando imagen...
            </div>

            @error('photo') <span class="text-red-600 text-sm block">{{ $message }}</span> @enderror

            <p class="text-xs text-gray-400">
                JPG, PNG o WEBP. Máx. 5MB. Se recortará automáticamente a formato
                carnet (400x500px), y luego una IA generará una versión con
                fondo blanco y vestimenta formal.
            </p>

            @if (in_array($aiStatus, ['pending', 'processing']))
                <div wire:poll.2s="pollStatus" class="flex items-center gap-2 text-sm text-indigo-600">
                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    Generando tu foto profesional con IA (esto puede tardar hasta 30-60 segundos)...
                </div>
            @elseif ($aiStatus === 'completed')
                <div class="text-sm text-green-600">✓ Foto profesional generada con IA.</div>
            @elseif ($aiStatus === 'failed')
                <div class="text-sm text-amber-600">
                    No se pudo generar la foto profesional con IA. Se está usando un ajuste de calidad local sobre tu foto original.
                    <button type="button" wire:click="retryAiEnhancement" class="underline ml-1">Reintentar</button>
                    @if ($aiError)
                        <div class="text-xs text-gray-400 mt-1">{{ $aiError }}</div>
                    @endif
                </div>
            @endif

            @if ($currentPhotoPath)
                <button type="button" wire:click="removePhoto"
                    wire:confirm="¿Seguro que quieres eliminar la foto?"
                    class="text-red-500 text-sm">
                    Eliminar foto
                </button>
            @endif
        </div>
    </div>
</div>