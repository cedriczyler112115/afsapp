<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProfileUpdateLevelRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed some user levels, divisions, and sections so lookups pass validation
        DB::table('user_level')->insert([
            ['id' => 1, 'level_name' => 'Administrator'],
            ['id' => 2, 'level_name' => 'Staff'],
            ['id' => 60, 'level_name' => 'Field Officer'],
        ]);

        DB::table('lib_division')->insert([
            ['id' => 10, 'division_name' => 'Admin Division'],
        ]);

        DB::table('lib_section')->insert([
            ['id' => 20, 'section_name' => 'Storage Section', 'division_id' => 10],
        ]);
    }

    public function test_admin_user_can_update_user_level(): void
    {
        $admin = User::factory()->create([
            'level_id' => 1,
            'division_id' => 10,
            'section_id' => 20,
        ]);

        $this->actingAs($admin);

        $response = $this->post(route('profile.update'), [
            'name' => 'Admin, Test User',
            'level_id' => 2, // changing from 1 to 2
            'division_id' => 10,
            'section_id' => 20,
        ]);

        $response->assertRedirect();
        $admin->refresh();
        $this->assertEquals(2, $admin->level_id);
    }

    public function test_non_admin_user_cannot_update_user_level(): void
    {
        $user = User::factory()->create([
            'level_id' => 60, // Field Officer
            'division_id' => 10,
            'section_id' => 20,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('profile.update'), [
            'name' => 'User, Test Field',
            'level_id' => 2, // Attempting to change to 2 (Staff)
            'division_id' => 10,
            'section_id' => 20,
        ]);

        $response->assertRedirect();
        $user->refresh();
        
        // level_id should remain 60 (Field Officer)
        $this->assertEquals(60, $user->level_id);
        $this->assertEquals('User, Test Field', $user->name);
    }
}
