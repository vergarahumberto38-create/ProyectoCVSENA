<?php

namespace App\Services;

use App\Models\Profile;
use Illuminate\Support\Collection;

class CvTextCorrector
{
    private const TEXT_FIELDS = [
        'full_name',
        'headline',
        'summary',
        'city',
        'country',
    ];

    private const ITEM_FIELDS = [
        'institution',
        'degree',
        'field_of_study',
        'description',
        'company',
        'position',
        'location',
        'achievements',
        'name',
    ];

    private const COMMON_ERRORS = [
        'administracion' => 'administración',
        'analisis' => 'análisis',
        'area' => 'área',
        'comunicacion' => 'comunicación',
        'desarollo' => 'desarrollo',
        'desarollador' => 'desarrollador',
        'educacion' => 'educación',
        'experiencia' => 'experiencia',
        'implementacion' => 'implementación',
        'informacion' => 'información',
        'organizacion' => 'organización',
        'programacion' => 'programación',
        'responsablidades' => 'responsabilidades',
        'tecnologia' => 'tecnología',
        'trabajao' => 'trabajo',
        'utilizacion' => 'utilización',
    ];

    public function prepare(Profile $profile): Profile
    {
        $exportProfile = $profile->replicate();

        foreach (self::TEXT_FIELDS as $field) {
            $value = $profile->{$field};
            $exportProfile->{$field} = $field === 'full_name'
                ? $this->correctName($value)
                : $this->correct($value);
        }

        foreach (['educations', 'experiences', 'skills', 'languages'] as $relation) {
            $exportProfile->setRelation($relation, $this->correctItems($profile->{$relation}));
        }

        return $exportProfile;
    }

    public function correct(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        $text = preg_replace('/[ \t]+/u', ' ', str_replace(["\r\n", "\r"], "\n", $text));
        $text = preg_replace('/[ \t]*([,.;:!?])[ \t]*/u', '$1 ', $text);
        $text = preg_replace('/[ \t]+\n/u', "\n", $text);
        $text = preg_replace('/\n[ \t]+/u', "\n", $text);
        $text = trim($text);

        foreach (self::COMMON_ERRORS as $incorrect => $correct) {
            $text = preg_replace_callback(
                '/\b'.preg_quote($incorrect, '/').'\b/iu',
                fn (array $matches): string => $this->preserveCase($matches[0], $correct),
                $text
            );
        }

        $text = preg_replace_callback(
            '/(^|[\n.!?]\s+)([\p{Ll}])/u',
            fn (array $matches): string => $matches[1].mb_strtoupper($matches[2], 'UTF-8'),
            $text
        );

        return preg_replace_callback(
            '/^(\p{Ll})/u',
            fn (array $matches): string => mb_strtoupper($matches[1], 'UTF-8'),
            $text
        );
    }

    private function correctName(?string $name): ?string
    {
        $name = $this->correct($name);

        return $name === null ? null : mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }

    private function correctItems(Collection $items): Collection
    {
        return $items->map(function ($item) {
            $copy = $item->replicate();

            foreach (self::ITEM_FIELDS as $field) {
                if (array_key_exists($field, $item->getAttributes())) {
                    $copy->{$field} = $this->correct($item->{$field});
                }
            }

            return $copy;
        });
    }

    private function preserveCase(string $original, string $correct): string
    {
        if (mb_strtoupper($original, 'UTF-8') === $original) {
            return mb_strtoupper($correct, 'UTF-8');
        }

        if (mb_strtoupper(mb_substr($original, 0, 1, 'UTF-8'), 'UTF-8') === mb_substr($original, 0, 1, 'UTF-8')) {
            return mb_strtoupper(mb_substr($correct, 0, 1, 'UTF-8'), 'UTF-8').mb_substr($correct, 1, null, 'UTF-8');
        }

        return $correct;
    }
}
