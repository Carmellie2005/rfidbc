<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardQuickHelpTest extends TestCase
{
    use RefreshDatabase;

    public function test_guard_can_view_quick_help_in_three_languages(): void
    {
        $guard = User::factory()->create(['role' => 'guard']);

        $this->actingAs($guard)
            ->get(route('guard.quick-help'))
            ->assertOk()
            ->assertSee('English')
            ->assertSee('Tagalog')
            ->assertSee('Bisaya')
            ->assertSee('Take and Save Evidence Photo')
            ->assertSee('Kung malapit sa checkpoint')
            ->assertSee('Simulan ang patrol')
            ->assertSee('Pagsugod sa patrol');
    }

    public function test_supervisor_cannot_open_guard_quick_help(): void
    {
        $supervisor = User::factory()->create(['role' => 'admin']);

        $this->actingAs($supervisor)
            ->get(route('guard.quick-help'))
            ->assertForbidden();
    }
}
