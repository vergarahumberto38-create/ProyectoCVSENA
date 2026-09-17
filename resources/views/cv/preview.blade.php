<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">Previsualización de tu CV</h2>

            <div class="flex gap-2">
                <a href="{{ route('cv.wizard') }}"
                    class="sena-btn-outline px-4 py-2 rounded text-sm">
                    Editar datos
                </a>

                {{-- El enlace público se conecta en la próxima fase --}}
                <a href="{{ route('cv.export.pdf') }}"
                    class="sena-btn-primary px-4 py-2 rounded text-sm">
                    Descargar PDF
                </a>
                <a href="{{ route('cv.export.docx') }}"
                    class="sena-btn-secondary px-4 py-2 rounded text-sm">
                    Descargar DOCX
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-[850px] mx-auto mb-4">
            <livewire:public-link-manager />
        </div>

        <div class="sena-card p-8 mx-auto" style="max-width: 850px;">
            @include('cv.templates.ats-classic', ['profile' => $profile])
        </div>
    </div>
</x-app-layout>
