<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_export_records_the_original_profile_id(): void
    {
        $user = User::factory()->create();
        $profile = Profile::create([
            'user_id' => $user->id,
            'full_name' => 'maria lopez',
        ]);

        $response = $this->actingAs($user)->get(route('cv.export.pdf'));

        $response->assertOk();
        $this->assertDatabaseHas('cv_exports', [
            'profile_id' => $profile->id,
            'type' => 'pdf',
        ]);
    }
}
