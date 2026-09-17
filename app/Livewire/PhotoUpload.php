<?php

namespace App\Livewire;

use App\Jobs\EnhancePhotoWithAi;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Format;
use Intervention\Image\Laravel\Facades\Image;
use Livewire\Component;
use Livewire\WithFileUploads;

class PhotoUpload extends Component
{
    use WithFileUploads;

    public $photo;

    public ?string $currentPhotoPath = null;
    public ?string $currentAtsPhotoPath = null;
    public bool $isProcessing = false;

    public ?string $aiStatus = null;
    public ?string $aiError = null;

    protected int $targetWidth = 400;
    protected int $targetHeight = 500;

    public function mount(): void
    {
        $profile = auth()->user()->profile;

        if ($profile) {
            $this->currentPhotoPath = $profile->photo_path;
            $this->currentAtsPhotoPath = $profile->photo_ats_path;
            $this->aiStatus = $profile->photo_ai_status;
            $this->aiError = $profile->photo_ai_error;
        }
    }

    protected function rules(): array
    {
        return [
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }

    public function updatedPhoto(): void
    {
        $this->validateOnly('photo');
        $this->processPhoto();
    }

    protected function processPhoto(): void
    {
        $this->isProcessing = true;

        $profile = auth()->user()->profile;

        if (! $profile) {
            $profile = auth()->user()->profile()->create([]);
        }

        $originalPath = $this->photo->store('profiles/'.$profile->id.'/original', 'public');

        $processedRelativePath = 'profiles/'.$profile->id.'/cv/'.uniqid('photo_').'.jpg';

        $image = Image::decode($this->photo->getRealPath())
            ->cover($this->targetWidth, $this->targetHeight)
            ->encodeUsingFormat(Format::JPEG, quality: 85);

        Storage::disk('public')->put($processedRelativePath, (string) $image);

        if ($profile->photo_path) {
            Storage::disk('public')->delete($profile->photo_path);
        }
        if ($profile->photo_ats_path) {
            Storage::disk('public')->delete($profile->photo_ats_path);
        }

        $profile->update([
            'photo_path' => $originalPath,
            'photo_ats_path' => $processedRelativePath,
            'photo_ai_status' => 'pending',
            'photo_ai_error' => null,
        ]);

        $this->currentPhotoPath = $originalPath;
        $this->currentAtsPhotoPath = $processedRelativePath;
        $this->aiStatus = 'pending';
        $this->aiError = null;
        $this->photo = null;
        $this->isProcessing = false;

        EnhancePhotoWithAi::dispatch($profile);

        session()->flash('photo_success', 'Foto actualizada. Generando tu foto profesional con IA...');
    }

    public function pollStatus(): void
    {
        $profile = auth()->user()->profile;

        if (! $profile) {
            return;
        }

        $profile->refresh();

        $this->currentAtsPhotoPath = $profile->photo_ats_path;
        $this->aiStatus = $profile->photo_ai_status;
        $this->aiError = $profile->photo_ai_error;
    }

    public function retryAiEnhancement(): void
    {
        $profile = auth()->user()->profile;

        if (! $profile || ! $profile->photo_ats_path) {
            return;
        }

        $profile->update(['photo_ai_status' => 'pending', 'photo_ai_error' => null]);
        $this->aiStatus = 'pending';
        $this->aiError = null;

        EnhancePhotoWithAi::dispatch($profile);
    }

    public function removePhoto(): void
    {
        $profile = auth()->user()->profile;

        if ($profile) {
            if ($profile->photo_path) {
                Storage::disk('public')->delete($profile->photo_path);
            }
            if ($profile->photo_ats_path) {
                Storage::disk('public')->delete($profile->photo_ats_path);
            }

            $profile->update([
                'photo_path' => null,
                'photo_ats_path' => null,
                'photo_ai_status' => null,
                'photo_ai_error' => null,
            ]);
        }

        $this->currentPhotoPath = null;
        $this->currentAtsPhotoPath = null;
        $this->aiStatus = null;
        $this->aiError = null;
    }

    public function render()
    {
        return view('livewire.photo-upload');
    }
}