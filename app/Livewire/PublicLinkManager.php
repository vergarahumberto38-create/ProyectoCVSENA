<?php

namespace App\Livewire;

use Livewire\Component;

class PublicLinkManager extends Component
{
    public bool $isPublic = false;
    public ?string $publicUrl = null;

    public function mount(): void
    {
        $profile = auth()->user()->profile;

        if ($profile) {
            $this->isPublic = $profile->is_public;
            $this->publicUrl = $profile->public_url;
        }
    }

    public function toggle(): void
    {
        $profile = auth()->user()->profile;

        if (! $profile) {
            return;
        }

        $profile->update(['is_public' => ! $profile->is_public]);

        $this->isPublic = $profile->is_public;
        $this->publicUrl = $profile->public_url;

        session()->flash(
            'link_status',
            $this->isPublic ? 'Tu CV ahora es público.' : 'Tu CV ahora es privado.'
        );
    }

    public function render()
    {
        return view('livewire.public-link-manager');
    }
}
