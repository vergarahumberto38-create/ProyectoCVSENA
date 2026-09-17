<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl leading-tight">
            {{ __('Creador CV') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="sena-card overflow-hidden p-6">
                <h3 class="text-lg font-medium">Tu hoja de vida</h3>

                @php
                    $profile = auth()->user()->profile;
                @endphp

                @if ($profile)
                    <p class="text-sm text-gray-500 mt-1">
                        Ya tienes datos guardados. Puedes seguir editándolos o ver el resultado.
                    </p>

                    <div class="flex gap-3 mt-4">
                        <a href="{{ route('cv.wizard') }}"
                            class="sena-btn-secondary inline-flex items-center px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest">
                            Editar mi CV
                        </a>
                        <a href="{{ route('cv.preview') }}"
                            class="sena-btn-primary inline-flex items-center px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest">
                            Ver mi CV
                        </a>
                    </div>
                @else
                    <p class="text-sm text-gray-500 mt-1">
                        Aún no has creado tu hoja de vida. Empieza ahora, toma solo unos minutos.
                    </p>

                    <div class="mt-4">
                        <a href="{{ route('cv.wizard') }}"
                            class="sena-btn-primary inline-flex items-center px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest">
                            Crear mi CV
                        </a>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
