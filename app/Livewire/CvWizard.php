<?php

namespace App\Livewire;

use App\Models\Education;
use App\Models\Experience;
use App\Models\Language;
use App\Models\Profile;
use App\Models\Skill;
use Livewire\Component;

class CvWizard extends Component
{
    public int $step = 1;
    public int $totalSteps = 4;

    // Paso 1: datos personales / contacto
    public string $full_name = '';
    public string $phone = '';
    public string $address = '';
    public string $city = '';
    public string $country = '';
    public string $linkedin_url = '';
    public string $portfolio_url = '';

    // Paso 2: estudios (array de arrays)
    public array $educations = [];

    // Paso 3: experiencia laboral (array de arrays)
    public array $experiences = [];

    // Paso 4: habilidades, idiomas y perfil profesional
    public string $headline = '';
    public string $summary = '';
    public array $skills = [];
    public array $languages = [];

    protected Profile $profile;

    public function mount(): void
    {
        $profile = auth()->user()->profile;

        if ($profile) {
            $this->profile = $profile;
            $this->fillFromProfile($profile);
        } else {
            // Al menos una fila vacía para que el usuario vea el formulario
            $this->educations = [$this->emptyEducation()];
            $this->experiences = [$this->emptyExperience()];
            $this->skills = [$this->emptySkill()];
            $this->languages = [$this->emptyLanguage()];
        }
    }

    protected function fillFromProfile(Profile $profile): void
    {
        $this->full_name = $profile->full_name ?? '';
        $this->phone = $profile->phone ?? '';
        $this->address = $profile->address ?? '';
        $this->city = $profile->city ?? '';
        $this->country = $profile->country ?? '';
        $this->linkedin_url = $profile->linkedin_url ?? '';
        $this->portfolio_url = $profile->portfolio_url ?? '';
        $this->headline = $profile->headline ?? '';
        $this->summary = $profile->summary ?? '';

        $this->educations = $profile->educations->map(fn ($e) => [
            'id' => $e->id,
            'institution' => $e->institution,
            'degree' => $e->degree,
            'field_of_study' => $e->field_of_study,
            'start_date' => optional($e->start_date)->format('Y-m-d'),
            'end_date' => optional($e->end_date)->format('Y-m-d'),
            'is_current' => $e->is_current,
            'description' => $e->description,
        ])->toArray() ?: [$this->emptyEducation()];

        $this->experiences = $profile->experiences->map(fn ($e) => [
            'id' => $e->id,
            'company' => $e->company,
            'position' => $e->position,
            'location' => $e->location,
            'start_date' => optional($e->start_date)->format('Y-m-d'),
            'end_date' => optional($e->end_date)->format('Y-m-d'),
            'is_current' => $e->is_current,
            'description' => $e->description,
            'achievements' => $e->achievements,
        ])->toArray() ?: [$this->emptyExperience()];

        $this->skills = $profile->skills->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'level' => $s->level,
        ])->toArray() ?: [$this->emptySkill()];

        $this->languages = $profile->languages->map(fn ($l) => [
            'id' => $l->id,
            'name' => $l->name,
            'level' => $l->level,
        ])->toArray() ?: [$this->emptyLanguage()];
    }

    // --- Helpers para filas vacías ---

    protected function emptyEducation(): array
    {
        return [
            'id' => null,
            'institution' => '',
            'degree' => '',
            'field_of_study' => '',
            'start_date' => '',
            'end_date' => '',
            'is_current' => false,
            'description' => '',
        ];
    }

    protected function emptyExperience(): array
    {
        return [
            'id' => null,
            'company' => '',
            'position' => '',
            'location' => '',
            'start_date' => '',
            'end_date' => '',
            'is_current' => false,
            'description' => '',
            'achievements' => '',
        ];
    }

    protected function emptySkill(): array
    {
        return ['id' => null, 'name' => '', 'level' => ''];
    }

    protected function emptyLanguage(): array
    {
        return ['id' => null, 'name' => '', 'level' => ''];
    }

    // --- Navegación entre pasos ---

    public function nextStep(): void
    {
        $this->validateStep();
        $this->saveStep();

        if ($this->step < $this->totalSteps) {
            $this->step++;
        }
    }

    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step <= $this->totalSteps) {
            $this->step = $step;
        }
    }

    protected function validateStep(): void
    {
        match ($this->step) {
            1 => $this->validate([
                'full_name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:50',
                'address' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'linkedin_url' => 'nullable|url|max:255',
                'portfolio_url' => 'nullable|url|max:255',
            ]),
            2 => $this->validate([
                'educations.*.institution' => 'required|string|max:255',
                'educations.*.degree' => 'nullable|string|max:255',
                'educations.*.field_of_study' => 'nullable|string|max:255',
                'educations.*.start_date' => 'nullable|date',
                'educations.*.end_date' => 'nullable|date|after_or_equal:educations.*.start_date',
                'educations.*.description' => 'nullable|string',
            ]),
            3 => $this->validate([
                'experiences.*.company' => 'required|string|max:255',
                'experiences.*.position' => 'required|string|max:255',
                'experiences.*.start_date' => 'nullable|date',
                'experiences.*.end_date' => 'nullable|date|after_or_equal:experiences.*.start_date',
                'experiences.*.description' => 'nullable|string',
                'experiences.*.achievements' => 'nullable|string',
            ]),
            4 => $this->validate([
                'headline' => 'nullable|string|max:255',
                'summary' => 'nullable|string|max:2000',
                'skills.*.name' => 'required_with:skills|string|max:255',
                'languages.*.name' => 'required_with:languages|string|max:255',
            ]),
            default => null,
        };
    }

    // --- Persistencia ---

    protected function saveStep(): void
    {
        $profile = auth()->user()->profile ?? new Profile(['user_id' => auth()->id()]);

        if ($this->step === 1) {
            $profile->fill([
                'full_name' => $this->full_name,
                'phone' => $this->phone,
                'address' => $this->address,
                'city' => $this->city,
                'country' => $this->country,
                'linkedin_url' => $this->linkedin_url,
                'portfolio_url' => $this->portfolio_url,
            ]);
            $profile->save();
        }

        $profile = $profile->fresh() ?? auth()->user()->profile;

        if ($this->step === 2) {
            $this->syncCollection($profile, Education::class, 'educations');
        }

        if ($this->step === 3) {
            $this->syncCollection($profile, Experience::class, 'experiences');
        }

        if ($this->step === 4) {
            $profile->fill([
                'headline' => $this->headline,
                'summary' => $this->summary,
            ])->save();

            $this->syncCollection($profile, Skill::class, 'skills');
            $this->syncCollection($profile, Language::class, 'languages');
        }

        $this->profile = $profile;
    }

    /**
     * Sincroniza una colección de items (educations, experiences, skills, languages)
     * contra la base de datos: actualiza los que tienen id, crea los nuevos,
     * y borra los que el usuario quitó del formulario.
     */
    protected function syncCollection(Profile $profile, string $modelClass, string $property): void
    {
        $items = $this->{$property};
        $keptIds = [];

        foreach ($items as $index => $data) {
            unset($data['id']);
            $id = $items[$index]['id'] ?? null;

            // Evita guardar filas completamente vacías
            $isEmpty = collect($data)->filter(fn ($v) => $v !== '' && $v !== false && $v !== null)->isEmpty();
            if ($isEmpty) {
                continue;
            }

            $data['order'] = $index;

            if ($id) {
                $model = $modelClass::where('profile_id', $profile->id)->find($id);
                if ($model) {
                    $model->update($data);
                    $keptIds[] = $model->id;
                    continue;
                }
            }

            $model = $modelClass::create(array_merge($data, ['profile_id' => $profile->id]));
            $items[$index]['id'] = $model->id;
            $keptIds[] = $model->id;
        }

        // Elimina los que ya no están en el array (el usuario los quitó)
        $modelClass::where('profile_id', $profile->id)
            ->whereNotIn('id', $keptIds ?: [0])
            ->delete();

        $this->{$property} = $items;
    }

    // --- Manejo dinámico de filas repetibles ---

    public function addEducation(): void
    {
        $this->educations[] = $this->emptyEducation();
    }

    public function removeEducation(int $index): void
    {
        unset($this->educations[$index]);
        $this->educations = array_values($this->educations);
    }

    public function addExperience(): void
    {
        $this->experiences[] = $this->emptyExperience();
    }

    public function removeExperience(int $index): void
    {
        unset($this->experiences[$index]);
        $this->experiences = array_values($this->experiences);
    }

    public function addSkill(): void
    {
        $this->skills[] = $this->emptySkill();
    }

    public function removeSkill(int $index): void
    {
        unset($this->skills[$index]);
        $this->skills = array_values($this->skills);
    }

    public function addLanguage(): void
    {
        $this->languages[] = $this->emptyLanguage();
    }

    public function removeLanguage(int $index): void
    {
        unset($this->languages[$index]);
        $this->languages = array_values($this->languages);
    }

    public function finish()
    {
        $this->validateStep();
        $this->saveStep();

        session()->flash('success', 'Tu CV se guardó correctamente.');

        return redirect()->route('cv.preview');
    }

    public function render()
    {
        return view('livewire.cv-wizard');
    }
}
