<?php

namespace Tests\Unit;

use App\Models\Experience;
use App\Models\Profile;
use App\Services\CvTextCorrector;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class CvTextCorrectorTest extends TestCase
{
    public function test_it_corrects_common_errors_without_changing_the_original_profile(): void
    {
        $profile = new Profile([
            'full_name' => 'maria lopez',
            'summary' => 'desarollo de software. administracion y comunicacion.',
        ]);
        $experience = new Experience([
            'company' => 'hogar mariano',
            'position' => 'Desarollador Backend',
            'description' => 'Implementacion de servicios.',
        ]);
        $profile->setRelation('experiences', new Collection([$experience]));
        $profile->setRelation('educations', new Collection);
        $profile->setRelation('skills', new Collection);
        $profile->setRelation('languages', new Collection);

        $corrected = (new CvTextCorrector)->prepare($profile);

        $this->assertSame('maria lopez', $profile->full_name);
        $this->assertSame('desarollo de software. administracion y comunicacion.', $profile->summary);
        $this->assertSame('Maria Lopez', $corrected->full_name);
        $this->assertSame('Desarrollo de software. Administración y comunicación.', $corrected->summary);
        $this->assertSame('Desarrollador Backend', $corrected->experiences->first()->position);
        $this->assertSame('Hogar mariano', $corrected->experiences->first()->company);
        $this->assertSame('Implementación de servicios.', $corrected->experiences->first()->description);
    }
}
