<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiConnectionTestRouteTest extends TestCase
{
    public function test_ai_connection_test_route_requires_authentication(): void
    {
        $this->getJson('/api/test-ai')->assertUnauthorized();
    }

    public function test_ai_connection_test_route_is_not_available_outside_local_environment(): void
    {
        Sanctum::actingAs(User::factory()->make());

        $this->getJson('/api/test-ai')->assertNotFound();
    }
}
