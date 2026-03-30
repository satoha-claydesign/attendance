<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_example()
    {
    $response = $this->get('/');

    // root redirects to /attendance (302) in this app environment
    $response->assertStatus(302);
    }
}
