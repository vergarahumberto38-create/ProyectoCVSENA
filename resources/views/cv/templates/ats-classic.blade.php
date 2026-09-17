{{-- Plantilla ATS de una sola columna, reutilizada por la vista previa y el PDF. --}}
<style>
    .ats-cv {
        font-family: 'Helvetica', 'Arial', sans-serif;
        color: #1a1a1a;
        font-size: 14px;
        line-height: 1.5;
        max-width: 750px;
        margin: 0 auto;
    }

    .ats-cv * { box-sizing: border-box; }
    .ats-header { border-bottom: 2px solid #333; padding-bottom: 12px; margin-bottom: 16px; }
    .ats-header::after { content: ""; display: table; clear: both; }
    .ats-header-content { min-height: 112px; }
    .ats-header.has-photo .ats-header-content { margin-right: 106px; }
    .ats-header-photo-frame { float: right; width: 90px; height: 112px; overflow: hidden; border: 1px solid #ccc; margin-left: 16px; }
    .ats-header-photo { display: block; width: 100%; height: 100%; max-width: none; object-fit: cover; }
    .ats-name { font-size: 24px; font-weight: bold; margin: 0 0 4px 0; }
    .ats-headline { font-size: 15px; color: #444; margin: 0 0 8px 0; }
    .ats-contact-line { font-size: 13px; color: #333; margin: 0; overflow-wrap: anywhere; }
    .ats-contact-line span:not(:last-child)::after { content: " | "; color: #999; }
    .ats-section { margin-bottom: 18px; }
    .ats-section-title { font-size: 15px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #999; padding-bottom: 3px; margin-bottom: 8px; }
    .ats-item { margin-bottom: 12px; }
    .ats-item:last-child { margin-bottom: 0; }
    .ats-item-title-row { font-weight: bold; font-size: 14px; overflow-wrap: anywhere; }
    .ats-item-subtitle-row { font-size: 13px; color: #444; margin-bottom: 2px; overflow-wrap: anywhere; }
    .ats-item-dates { font-size: 12px; color: #666; font-style: italic; }
    .ats-item-description { font-size: 13px; margin-top: 4px; white-space: pre-line; overflow-wrap: anywhere; }
    .ats-summary-text { font-size: 13.5px; white-space: pre-line; overflow-wrap: anywhere; }
    .ats-inline-list { font-size: 13px; overflow-wrap: anywhere; }
    .ats-inline-list span:not(:last-child)::after { content: " · "; color: #999; }
</style>

<div class="ats-cv">
    <div class="ats-header {{ $profile->photo_ats_path ? 'has-photo' : '' }}">
        @if ($profile->photo_ats_path)
            <div class="ats-header-photo-frame">
                <img src="{{ $photoSrc ?? Storage::url($profile->photo_ats_path) }}" alt="Foto de perfil" class="ats-header-photo">
            </div>
        @endif

        <div class="ats-header-content">
            <p class="ats-name">{{ $profile->full_name }}</p>
            @if ($profile->headline)<p class="ats-headline">{{ $profile->headline }}</p>@endif
            <p class="ats-contact-line">
                @if ($profile->phone) <span>{{ $profile->phone }}</span> @endif
                @if ($profile->email ?? auth()->user()->email ?? null) <span>{{ $profile->email ?? auth()->user()->email }}</span> @endif
                @if ($profile->city || $profile->country) <span>{{ trim(($profile->city ?? '').(($profile->city && $profile->country) ? ', ' : '').($profile->country ?? '')) }}</span> @endif
                @if ($profile->linkedin_url) <span>{{ $profile->linkedin_url }}</span> @endif
                @if ($profile->portfolio_url) <span>{{ $profile->portfolio_url }}</span> @endif
            </p>
        </div>
    </div>

    @if ($profile->summary)
        <div class="ats-section"><div class="ats-section-title">Perfil profesional</div><p class="ats-summary-text">{{ $profile->summary }}</p></div>
    @endif

    @if ($profile->experiences->isNotEmpty())
        <div class="ats-section">
            <div class="ats-section-title">Experiencia laboral</div>
            @foreach ($profile->experiences as $exp)
                <div class="ats-item">
                    <div class="ats-item-title-row">{{ $exp->position }} — {{ $exp->company }}</div>
                    <div class="ats-item-subtitle-row">@if ($exp->location) {{ $exp->location }} @endif</div>
                    <div class="ats-item-dates">{{ optional($exp->start_date)->format('M Y') }} — {{ $exp->is_current ? 'Actualidad' : optional($exp->end_date)->format('M Y') }}</div>
                    @if ($exp->description)<div class="ats-item-description">{{ $exp->description }}</div>@endif
                    @if ($exp->achievements)<div class="ats-item-description">{{ $exp->achievements }}</div>@endif
                </div>
            @endforeach
        </div>
    @endif

    @if ($profile->educations->isNotEmpty())
        <div class="ats-section">
            <div class="ats-section-title">Educación</div>
            @foreach ($profile->educations as $edu)
                <div class="ats-item">
                    <div class="ats-item-title-row">{{ $edu->degree }} @if ($edu->field_of_study) — {{ $edu->field_of_study }} @endif</div>
                    <div class="ats-item-subtitle-row">{{ $edu->institution }}</div>
                    <div class="ats-item-dates">{{ optional($edu->start_date)->format('M Y') }} — {{ $edu->is_current ? 'En curso' : optional($edu->end_date)->format('M Y') }}</div>
                    @if ($edu->description)<div class="ats-item-description">{{ $edu->description }}</div>@endif
                </div>
            @endforeach
        </div>
    @endif

    @if ($profile->skills->isNotEmpty())
        <div class="ats-section"><div class="ats-section-title">Habilidades</div><p class="ats-inline-list">@foreach ($profile->skills as $skill)<span>{{ $skill->name }}{{ $skill->level ? ' ('.$skill->level.')' : '' }}</span>@endforeach</p></div>
    @endif

    @if ($profile->languages->isNotEmpty())
        <div class="ats-section"><div class="ats-section-title">Idiomas</div><p class="ats-inline-list">@foreach ($profile->languages as $lang)<span>{{ $lang->name }}{{ $lang->level ? ' ('.$lang->level.')' : '' }}</span>@endforeach</p></div>
    @endif
</div>
