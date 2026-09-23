<div class="max-w-3xl mx-auto py-8">

    {{-- Indicador de progreso --}}
    <div class="flex items-center justify-between mb-8">
        @foreach (['Datos personales', 'Estudios', 'Experiencia', 'Perfil y habilidades'] as $i => $label)
            @php $num = $i + 1; @endphp
            <button
                wire:click="goToStep({{ $num }})"
                type="button"
                class="flex-1 text-center text-sm border-b-4 pb-2 {{ $step === $num ? 'border-indigo-600 font-semibold text-indigo-600' : 'border-gray-200 text-gray-400' }}"
            >
                {{ $num }}. {{ $label }}
            </button>
        @endforeach
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
    @endif

    <form wire:submit.prevent="{{ $step === $totalSteps ? 'finish' : 'nextStep' }}" class="space-y-6">

        {{-- PASO 1: Datos personales / contacto --}}
        @if ($step === 1)
            <div class="space-y-4">
                <livewire:photo-upload />

                <div>
                    <label class="block text-sm font-medium">Nombre completo</label>
                    <input type="text" wire:model="full_name" class="w-full border-gray-300 rounded">
                    @error('full_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium">Teléfono</label>
                        <input type="text" wire:model="phone" class="w-full border-gray-300 rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Ciudad</label>
                        <input type="text" wire:model="city" class="w-full border-gray-300 rounded">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium">Dirección</label>
                        <input type="text" wire:model="address" class="w-full border-gray-300 rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">País</label>
                        <input type="text" wire:model="country" class="w-full border-gray-300 rounded">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium">LinkedIn</label>
                        <input type="url" wire:model="linkedin_url" placeholder="https://linkedin.com/in/..." class="w-full border-gray-300 rounded">
                        @error('linkedin_url') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Portafolio / web</label>
                        <input type="url" wire:model="portfolio_url" placeholder="https://..." class="w-full border-gray-300 rounded">
                    </div>
                </div>
            </div>
        @endif

        {{-- PASO 2: Estudios --}}
        @if ($step === 2)
            <div class="space-y-6">
                @foreach ($educations as $index => $edu)
                    <div class="border rounded p-4 space-y-3 relative">
                        @if (count($educations) > 1)
                            <button type="button" wire:click="removeEducation({{ $index }})"
                                class="absolute top-2 right-2 text-red-500 text-sm">Eliminar</button>
                        @endif

                        <div>
                            <label class="block text-sm font-medium">Institución</label>
                            <input type="text" wire:model="educations.{{ $index }}.institution" class="w-full border-gray-300 rounded">
                            @error("educations.$index.institution") <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium">Título obtenido</label>
                                <input type="text" wire:model="educations.{{ $index }}.degree" class="w-full border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Área de estudio</label>
                                <input type="text" wire:model="educations.{{ $index }}.field_of_study" class="w-full border-gray-300 rounded">
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4 items-end">
                            <div>
                                <label class="block text-sm font-medium">Inicio</label>
                                <input type="date" wire:model="educations.{{ $index }}.start_date" class="w-full border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Fin</label>
                                <input type="date" wire:model="educations.{{ $index }}.end_date"
                                    @if($edu['is_current']) disabled @endif
                                    class="w-full border-gray-300 rounded">
                            </div>
                            <div class="flex items-center gap-2 pb-2">
                                <input type="checkbox" wire:model="educations.{{ $index }}.is_current">
                                <label class="text-sm">En curso</label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Descripción (opcional)</label>
                            <textarea wire:model="educations.{{ $index }}.description" rows="2" class="w-full border-gray-300 rounded"></textarea>
                        </div>
                    </div>
                @endforeach

                <button type="button" wire:click="addEducation" class="text-indigo-600 text-sm font-medium">
                    + Agregar otro estudio
                </button>
            </div>
        @endif

        {{-- PASO 3: Experiencia laboral --}}
        @if ($step === 3)
            <div class="space-y-6">
                @foreach ($experiences as $index => $exp)
                    <div class="border rounded p-4 space-y-3 relative">
                        @if (count($experiences) > 1)
                            <button type="button" wire:click="removeExperience({{ $index }})"
                                class="absolute top-2 right-2 text-red-500 text-sm">Eliminar</button>
                        @endif

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium">Empresa</label>
                                <input type="text" wire:model="experiences.{{ $index }}.company" class="w-full border-gray-300 rounded">
                                @error("experiences.$index.company") <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Cargo</label>
                                <input type="text" wire:model="experiences.{{ $index }}.position" class="w-full border-gray-300 rounded">
                                @error("experiences.$index.position") <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Ubicación</label>
                            <input type="text" wire:model="experiences.{{ $index }}.location" class="w-full border-gray-300 rounded">
                        </div>

                        <div class="grid grid-cols-3 gap-4 items-end">
                            <div>
                                <label class="block text-sm font-medium">Inicio</label>
                                <input type="date" wire:model="experiences.{{ $index }}.start_date" class="w-full border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Fin</label>
                                <input type="date" wire:model="experiences.{{ $index }}.end_date"
                                    @if($exp['is_current']) disabled @endif
                                    class="w-full border-gray-300 rounded">
                            </div>
                            <div class="flex items-center gap-2 pb-2">
                                <input type="checkbox" wire:model="experiences.{{ $index }}.is_current">
                                <label class="text-sm">Trabajo actual</label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Descripción de funciones</label>
                            <textarea wire:model="experiences.{{ $index }}.description" rows="2" class="w-full border-gray-300 rounded"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">
                                Logros medibles <span class="text-gray-400">(recomendado para ATS, ej: "Reduje X en 20%")</span>
                            </label>
                            <textarea wire:model="experiences.{{ $index }}.achievements" rows="2" class="w-full border-gray-300 rounded"></textarea>
                        </div>
                    </div>
                @endforeach

                <button type="button" wire:click="addExperience" class="text-indigo-600 text-sm font-medium">
                    + Agregar otra experiencia
                </button>
            </div>
        @endif

        {{-- PASO 4: Perfil profesional, habilidades e idiomas --}}
        @if ($step === 4)
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium">Título profesional (headline)</label>
                    <input type="text" wire:model="headline" placeholder="Ej: Desarrollador Backend Junior" class="w-full border-gray-300 rounded">
                </div>

                <div>
                    <label class="block text-sm font-medium">Perfil / resumen profesional</label>
                    <textarea wire:model="summary" rows="4" class="w-full border-gray-300 rounded"
                        placeholder="Resume tu experiencia y objetivo profesional en 3-4 líneas, usando palabras clave del cargo al que aplicas."></textarea>
                    @error('summary') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <h3 class="font-medium mb-2">Habilidades</h3>
                    <div class="space-y-2">
                        @foreach ($skills as $index => $skill)
                            <div class="flex gap-2 items-center">
                                <input type="text" wire:model="skills.{{ $index }}.name" placeholder="Ej: Laravel"
                                    class="flex-1 border-gray-300 rounded">
                                <select wire:model="skills.{{ $index }}.level" class="border-gray-300 rounded">
                                    <option value="">Nivel (opcional)</option>
                                    <option value="Básico">Básico</option>
                                    <option value="Intermedio">Intermedio</option>
                                    <option value="Avanzado">Avanzado</option>
                                    <option value="Experto">Experto</option>
                                </select>
                                @if (count($skills) > 1)
                                    <button type="button" wire:click="removeSkill({{ $index }})" class="text-red-500 text-sm">✕</button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <button type="button" wire:click="addSkill" class="text-indigo-600 text-sm font-medium mt-2">
                        + Agregar habilidad
                    </button>
                </div>

                <div>
                    <h3 class="font-medium mb-2">Idiomas</h3>
                    <div class="space-y-2">
                        @foreach ($languages as $index => $lang)
                            <div class="flex gap-2 items-center">
                                <input type="text" wire:model="languages.{{ $index }}.name" placeholder="Ej: Inglés"
                                    class="flex-1 border-gray-300 rounded">
                                <input type="text" wire:model="languages.{{ $index }}.level" placeholder="Ej: B2, Avanzado"
                                    class="border-gray-300 rounded">
                                @if (count($languages) > 1)
                                    <button type="button" wire:click="removeLanguage({{ $index }})" class="text-red-500 text-sm">✕</button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <button type="button" wire:click="addLanguage" class="text-indigo-600 text-sm font-medium mt-2">
                        + Agregar idioma
                    </button>
                </div>
            </div>
        @endif

        {{-- Navegación --}}
        <div class="flex justify-between pt-4 border-t">
            <button type="button" wire:click="previousStep"
                class="sena-btn-outline px-4 py-2 rounded {{ $step === 1 ? 'opacity-0 pointer-events-none' : '' }}"
                aria-label="Volver al paso anterior">
                <span aria-hidden="true">←</span> Atrás
            </button>

            <button type="submit" class="sena-btn-primary px-6 py-2 rounded" wire:loading.attr="disabled">
                {{ $step === $totalSteps ? 'Guardar y finalizar' : 'Siguiente' }}
            </button>
        </div>
    </form>
</div>
