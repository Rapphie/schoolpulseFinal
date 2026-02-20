<?php

namespace Tests\Feature;

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CsrfSessionRecoveryTest extends TestCase
{
    public function test_csrf_token_endpoint_returns_the_current_session_token(): void
    {
        $firstResponse = $this->get(route('csrf.token'));
        $firstResponse->assertOk();
        $firstResponse->assertJsonStructure(['token']);

        $token = $firstResponse->json('token');
        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        $secondResponse = $this->get(route('csrf.token'));
        $secondResponse->assertOk();
        $secondResponse->assertJson([
            'token' => $token,
        ]);
    }

    public function test_token_mismatch_returns_json_for_ajax_requests(): void
    {
        $uri = $this->registerTokenMismatchRoute();
        $response = $this->postJson($uri);

        $response->assertStatus(419);
        $response->assertJson([
            'message' => 'Your session has expired. Please retry your request.',
        ]);
    }

    public function test_token_mismatch_redirects_back_with_warning_for_web_requests(): void
    {
        $uri = $this->registerTokenMismatchRoute();
        $response = $this->from('/dashboard')->post($uri, [
            'example' => 'value',
            'password' => 'top-secret',
            'password_confirmation' => 'top-secret',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('warning', 'Your session expired. Please try again.');
        $response->assertSessionHas('_old_input.example', 'value');
        $response->assertSessionMissing('_old_input.password');
        $response->assertSessionMissing('_old_input.password_confirmation');
    }

    private function registerTokenMismatchRoute(): string
    {
        $uri = '/testing/csrf-mismatch/'.uniqid();

        Route::post($uri, function () {
            throw new TokenMismatchException;
        });

        return $uri;
    }
}
