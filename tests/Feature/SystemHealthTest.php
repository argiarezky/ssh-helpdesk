<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_engineer_can_check_application_and_database_health(): void
    {
        $engineer = User::factory()->engineer()->create();

        $this->actingAs($engineer)->getJson(route('system.health'))
            ->assertOk()
            ->assertJsonPath('application', 'online')
            ->assertJsonPath('database', 'online');
    }

    public function test_dashboard_shows_live_server_health_indicator(): void
    {
        $engineer = User::factory()->engineer()->create();

        $this->actingAs($engineer)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('serverHealth')
            ->assertSee('healthUrl');
    }

    public function test_database_failure_is_reported_as_unavailable(): void
    {
        $engineer = User::factory()->engineer()->create();
        DB::shouldReceive('select')->once()->andThrow(new RuntimeException('Database unavailable.'));

        $this->actingAs($engineer)->getJson(route('system.health'))
            ->assertStatus(503)
            ->assertJsonPath('application', 'online')
            ->assertJsonPath('database', 'offline');
    }

    public function test_guest_cannot_check_system_health(): void
    {
        $this->get(route('system.health'))->assertRedirect(route('login'));
    }
}
