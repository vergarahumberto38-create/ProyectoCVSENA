<div class="border rounded-lg p-4 bg-gray-50">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="font-medium text-sm">Enlace público</h3>
            <p class="text-xs text-gray-500">
                @if ($isPublic)
                    Cualquier persona con el enlace puede ver tu CV.
                @else
                    Tu CV es privado. Actívalo para compartirlo con un enlace.
                @endif
            </p>
        </div>

        <button type="button" wire:click="toggle"
            class="relative inline-flex h-6 w-11 items-center rounded-full transition {{ $isPublic ? 'bg-indigo-600' : 'bg-gray-300' }}">
            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition {{ $isPublic ? 'translate-x-6' : 'translate-x-1' }}"></span>
        </button>
    </div>

    @if (session('link_status'))
        <div class="mt-2 text-xs text-green-700">{{ session('link_status') }}</div>
    @endif

    @if ($isPublic && $publicUrl)
        <div class="mt-3 flex gap-2">
            <input type="text" readonly value="{{ $publicUrl }}"
                id="public-cv-url"
                class="flex-1 text-sm border-gray-300 rounded bg-white" onclick="this.select()">

            <button type="button"
                onclick="navigator.clipboard.writeText(document.getElementById('public-cv-url').value); this.innerText='¡Copiado!'; setTimeout(() => this.innerText='Copiar', 1500)"
                class="px-3 py-1 text-sm rounded border bg-white">
                Copiar
            </button>

            <a href="{{ $publicUrl }}" target="_blank"
                class="px-3 py-1 text-sm rounded border bg-white">
                Ver
            </a>
        </div>
    @endif
</div>
