<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApplicationBootTest extends TestCase
{
    public function test_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_api_status_endpoint_uses_consistent_response_shape(): void
    {
        $this->getJson('/api/status')
            ->assertOk()
            ->assertJsonPath('data.name', 'Nuvio')
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('meta.api_version', 'v1');
    }
}
